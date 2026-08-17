import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
const userId = document.body.dataset.userId;
const userRole = document.body.dataset.userRole;

window.Pusher = Pusher;

if (userId && import.meta.env.VITE_REVERB_APP_KEY) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
        wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 80),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}

document.querySelector('[data-nav-toggle]')?.addEventListener('click', (event) => {
    event.stopPropagation();
    document.body.classList.toggle('nav-open');
});
document.querySelector('.main')?.addEventListener('click', () => document.body.classList.remove('nav-open'));

const overlay = document.querySelector('[data-secret-overlay]');
const messageQueue = [];
let activeMessage = null;

function showNextMessage() {
    if (!overlay || activeMessage || messageQueue.length === 0) return;
    activeMessage = messageQueue.shift();
    overlay.querySelector('[data-secret-body]').textContent = activeMessage.body;
    overlay.querySelector('[data-secret-priority]').textContent = activeMessage.priority === 'urgent' ? 'Alerte du maître du jeu' : 'Message du maître du jeu';
    overlay.querySelector('.secret-scroll').dataset.priority = activeMessage.priority;
    overlay.classList.add('visible');
}

function enqueueMessage(message) {
    if (activeMessage?.id === message.id || messageQueue.some((queued) => queued.id === message.id)) return;
    messageQueue.push(message);
    showNextMessage();
}

overlay?.querySelector('[data-secret-dismiss]')?.addEventListener('click', async () => {
    if (!activeMessage) return;
    const messageId = activeMessage.id;

    try {
        await fetch(`/messages/${messageId}/lecture`, {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json'},
        });
        overlay.classList.remove('visible');
        activeMessage = null;
        window.setTimeout(showNextMessage, 250);
    } catch (error) {
        console.error('Impossible d’accuser réception du message.', error);
    }
});

async function fetchUnreadMessages() {
    if (!userId || userRole !== 'player') return;

    try {
        const response = await fetch('/messages/non-lus', {headers: {'Accept': 'application/json'}});
        if (!response.ok) return;
        (await response.json()).forEach(enqueueMessage);
    } catch (error) {
        console.debug('Relève des messages différée.', error);
    }
}

/*
 * Révélation d'une donnée de fiche.
 *
 * Réutilise le canal privé déjà ouvert pour les messages secrets : aucune
 * infrastructure supplémentaire n'est introduite. Le payload ne contient que
 * ce qui vient d'être révélé — le filtrage reste fait côté serveur.
 */
const revealOverlay = document.querySelector('[data-reveal-overlay]');

const revealDefaultUrl = revealOverlay?.querySelector('[data-reveal-action]')?.getAttribute('href');
const revealDefaultLabel = revealOverlay?.querySelector('[data-reveal-action]')?.textContent;

function showReveal(event) {
    if (!revealOverlay) return;

    revealOverlay.querySelector('[data-reveal-kind]').textContent = event.kind ?? 'Révélation';
    revealOverlay.querySelector('[data-reveal-headline]').textContent = event.headline ?? '';
    revealOverlay.querySelector('[data-reveal-description]').textContent = event.description ?? '';

    // Le bouton d'action suit ce qui vient d'être révélé : la fiche par défaut,
    // le glossaire quand il s'agit d'un PNJ. Même composant pour les deux.
    const action = revealOverlay.querySelector('[data-reveal-action]');
    if (action) {
        action.setAttribute('href', event.url ?? revealDefaultUrl);
        action.textContent = event.action_label ?? revealDefaultLabel;
    }

    revealOverlay.classList.add('visible');
}

revealOverlay?.querySelector('[data-reveal-dismiss]')?.addEventListener('click', () => {
    revealOverlay.classList.remove('visible');
});

if (window.Echo && userRole === 'player') {
    window.Echo.private(`users.${userId}`)
        .listen('.secret-message.sent', enqueueMessage)
        .listen('.character-sheet.revealed', showReveal)
        .listen('.npc.revealed', showReveal);
}

if (window.Echo && userRole === 'game_master') {
    window.Echo.private('game-masters').listen('.secret-message.read', (event) => {
        const status = document.querySelector(`[data-message-id="${event.id}"]`);
        if (status) {
            status.textContent = 'Lu';
            status.className = 'badge badge-green';
        }
    });
}

fetchUnreadMessages();
if (userRole === 'player') window.setInterval(fetchUnreadMessages, 12000);

/*
 * Mémorisation de l'onglet courant.
 *
 * Les onglets sont des <input type="radio"> pilotés en CSS pur : il suffit donc
 * de retenir l'id du bouton coché et de le recocher au chargement. La clé est
 * dérivée du name du groupe, ce qui fait fonctionner la fiche joueur et la
 * fiche MJ sans configuration.
 */
document.querySelectorAll('input.sheet-tabs').forEach((tab) => {
    const storageKey = `jdr.tab.${tab.name}`;

    tab.addEventListener('change', () => {
        if (tab.checked) window.localStorage.setItem(storageKey, tab.id);
    });

    const remembered = window.localStorage.getItem(storageKey);

    // On ne restaure que si l'onglet mémorisé existe encore sur cette page :
    // un onglet supprimé ne doit pas laisser la fiche sans panneau actif.
    if (remembered === tab.id) tab.checked = true;
});

/*
 * Éditeur de notes du journal.
 *
 * Chaque note s'enregistre seule : on attend que le joueur ait cessé d'écrire
 * pendant un court instant (debounce) plutôt que d'émettre une requête par
 * frappe. La sauvegarde est un PUT JSON classique — c'est le contrôleur qui
 * répond en JSON quand la requête l'attend.
 */
document.querySelectorAll('.note-editor').forEach((editor) => {
    const url = editor.dataset.noteUrl;
    const status = editor.querySelector('.note-status');
    const title = editor.querySelector('.note-title');
    const content = editor.querySelector('.note-content');
    const pinned = editor.querySelector('.note-pinned');
    const relationship = editor.querySelector('.note-relationship');
    let timer = null;

    /*
     * Le même éditeur sert le journal (titre + épingle) et les notes de
     * glossaire (relation). On n'envoie donc que les champs réellement
     * présents, plutôt que d'inventer des valeurs pour ceux qui manquent.
     */
    function payload() {
        const body = {content: content.innerHTML};

        if (title) body.title = title.value || 'Sans titre';
        if (pinned) body.pinned = pinned.checked ? 1 : 0;
        if (relationship) body.relationship = relationship.value;

        return body;
    }

    async function save() {
        status.textContent = 'Enregistrement…';

        try {
            const response = await fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload()),
            });

            if (!response.ok) throw new Error(response.statusText);

            status.textContent = `Enregistrée à ${(await response.json()).saved_at}`;
        } catch (error) {
            // On le dit au joueur : une note perdue sans avertissement serait pire.
            status.textContent = 'Échec de l’enregistrement';
            console.error('Note non enregistrée.', error);
        }
    }

    function scheduleSave() {
        window.clearTimeout(timer);
        status.textContent = 'Modifications en cours…';
        timer = window.setTimeout(save, 900);
    }

    content.addEventListener('input', scheduleSave);
    title?.addEventListener('input', scheduleSave);
    pinned?.addEventListener('change', save);
    relationship?.addEventListener('change', save);

    // Un onglet fermé au milieu d'une phrase ne doit pas perdre la frappe.
    window.addEventListener('beforeunload', () => {
        if (timer) {
            window.clearTimeout(timer);
            navigator.sendBeacon?.(url, new Blob([JSON.stringify({
                _method: 'PUT',
                _token: csrfToken,
                ...payload(),
            })], {type: 'application/json'}));
        }
    });

    editor.querySelectorAll('[data-command]').forEach((button) => {
        // mousedown plutôt que click : on agit avant que le champ perde le focus,
        // sinon la sélection à mettre en forme est déjà retombée.
        button.addEventListener('mousedown', (event) => {
            event.preventDefault();
            content.focus();
            document.execCommand(button.dataset.command, false, button.dataset.value ?? null);
            scheduleSave();
        });
    });
});

/*
 * Cartes quadrillées.
 *
 * Trois interactions : le MJ ouvre/referme une case au clic, tout le monde
 * pose des repères, le MJ filtre les repères par joueur. Les cases fermées
 * n'ont pas d'image en page — révéler une case charge donc sa tuile à ce
 * moment-là, ce qui est exactement le comportement voulu.
 */
const mapGrid = document.querySelector('[data-map-grid]');

if (mapGrid) {
    const pointLayer = mapGrid.querySelector('[data-map-points]');
    const cellUrl = mapGrid.dataset.cellUrl;
    const pointUrl = mapGrid.dataset.pointUrl;
    const mapSlug = mapGrid.dataset.mapSlug;

    async function send(url, method, payload) {
        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: payload ? JSON.stringify(payload) : null,
        });

        if (!response.ok) throw new Error(response.statusText);

        return response.status === 204 ? null : response.json();
    }

    // ── Ouverture / fermeture d'une case (MJ) ─────────────────────────────
    if (cellUrl) {
        mapGrid.addEventListener('click', async (event) => {
            const cell = event.target.closest('.map-cell');
            if (!cell || event.shiftKey) return;

            const column = Number(cell.dataset.column);
            const row = Number(cell.dataset.row);

            try {
                const {revealed} = await send(cellUrl, 'POST', {column, row});

                cell.classList.toggle('is-revealed', revealed);
                cell.classList.toggle('is-dark', !revealed);

                if (revealed && !cell.querySelector('img')) {
                    const image = document.createElement('img');
                    image.src = `/cartes/${mapSlug}/tuiles/${row}/${column}`;
                    image.alt = '';
                    cell.appendChild(image);
                } else if (!revealed) {
                    cell.querySelector('img')?.remove();
                }
            } catch (error) {
                console.error('Case non modifiée.', error);
            }
        });
    }

    // ── Pose d'un repère ──────────────────────────────────────────────────
    if (pointUrl) {
        const modeButton = document.querySelector('[data-point-mode]');
        const colorInput = document.querySelector('[data-point-color]');
        let placing = false;

        modeButton?.addEventListener('click', () => {
            placing = !placing;
            modeButton.setAttribute('aria-pressed', String(placing));
            modeButton.classList.toggle('btn-primary', placing);
            mapGrid.classList.toggle('is-placing', placing);
        });

        mapGrid.addEventListener('click', async (event) => {
            // Côté MJ le clic simple sert à ouvrir une case : on réserve donc
            // Maj + clic à la pose de repère.
            const wantsPoint = placing || (cellUrl && event.shiftKey);
            if (!wantsPoint) return;

            const bounds = mapGrid.getBoundingClientRect();
            const x = ((event.clientX - bounds.left) / bounds.width) * 100;
            const y = ((event.clientY - bounds.top) / bounds.height) * 100;

            const label = window.prompt('Nom du repère ?');
            if (!label) return;

            try {
                const point = await send(pointUrl, 'POST', {
                    label,
                    color: colorInput?.value ?? '#c9a227',
                    x_position: x.toFixed(2),
                    y_position: y.toFixed(2),
                });

                const marker = document.createElement('span');
                marker.className = 'map-point';
                marker.dataset.pointId = point.id;
                marker.style.left = `${point.x}%`;
                marker.style.top = `${point.y}%`;
                marker.style.setProperty('--point-color', point.color);
                marker.innerHTML = '<span class="map-point-dot"></span><span class="map-point-label"></span>';
                marker.querySelector('.map-point-label').textContent = point.label;
                pointLayer.appendChild(marker);
            } catch (error) {
                console.error('Repère non enregistré.', error);
            }
        });
    }

    // ── Suppression d'un repère ───────────────────────────────────────────
    document.querySelectorAll('[data-delete-point]').forEach((button) => {
        button.addEventListener('click', async () => {
            const row = button.closest('[data-point-row]');
            const id = row?.dataset.pointRow;

            try {
                await send(button.dataset.deletePoint, 'DELETE');
                row?.remove();
                pointLayer.querySelector(`[data-point-id="${id}"]`)?.remove();
            } catch (error) {
                console.error('Repère non supprimé.', error);
            }
        });
    });

    // ── Filtres d'affichage ───────────────────────────────────────────────
    document.querySelectorAll('[data-point-filter]').forEach((filter) => {
        filter.addEventListener('change', () => {
            pointLayer.querySelectorAll(`[data-owner="${filter.value}"]`)
                .forEach((point) => point.classList.toggle('is-hidden', !filter.checked));
        });
    });

    document.querySelector('[data-toggle-labels]')?.addEventListener('change', (event) => {
        mapGrid.classList.toggle('hide-labels', !event.target.checked);
    });
}

/*
 * Choix de l'origine : grisage en direct.
 *
 * Le canal `houses` est le seul partagé par toute la table. Il ne transporte
 * qu'un slug de maison : aucune donnée de jeu n'y circule. Ce grisage est un
 * confort d'affichage — l'exclusivité réelle est verrouillée en base au moment
 * du choix, donc un joueur qui rate l'événement se voit simplement refuser sa
 * maison et rejoue.
 */
const houseChoice = document.querySelector('[data-house-choice]');

if (houseChoice && window.Echo) {
    window.Echo.channel('houses').listen('.house.taken', (event) => {
        houseChoice.querySelector(`[data-house="${event.slug}"]`)?.classList.add('is-taken');
    });
}
