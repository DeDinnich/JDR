import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
const userId = document.body.dataset.userId;
const userRole = document.body.dataset.userRole;
const ownedCharacterId = document.body.dataset.characterId;

function realtimeHeaders(extra = {}) {
    const socketId = window.Echo?.socketId();

    return {
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
        ...(socketId ? {'X-Socket-ID': socketId} : {}),
        ...extra,
    };
}

window.Pusher = Pusher;

if (userId && import.meta.env.VITE_REVERB_APP_KEY) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        // Le navigateur reprend l'hôte par lequel le joueur a ouvert le site :
        // localhost sur le poste MJ, IP LAN sur téléphone, domaine en prod.
        wsHost: window.location.hostname,
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

function removeSecretMessage(messageId) {
    const normalizedId = String(messageId);

    for (let index = messageQueue.length - 1; index >= 0; index -= 1) {
        if (String(messageQueue[index].id) === normalizedId) messageQueue.splice(index, 1);
    }

    if (activeMessage && String(activeMessage.id) === normalizedId) {
        overlay?.classList.remove('visible');
        activeMessage = null;
        window.setTimeout(showNextMessage, 250);
    }

    document.querySelector(`[data-secret-message-row="${CSS.escape(normalizedId)}"]`)?.remove();
}

async function deleteSecretMessage(url) {
    const response = await fetch(url, {
        method: 'DELETE',
        headers: realtimeHeaders(),
    });

    if (!response.ok) throw new Error(`Suppression refusée (${response.status}).`);
}

overlay?.querySelector('[data-secret-dismiss]')?.addEventListener('click', async () => {
    if (!activeMessage) return;
    const messageId = activeMessage.id;

    try {
        await fetch(`/messages/${messageId}/lecture`, {
            method: 'POST',
            headers: realtimeHeaders(),
        });
        overlay.classList.remove('visible');
        activeMessage = null;
        window.setTimeout(showNextMessage, 250);
    } catch (error) {
        console.error('Impossible d’accuser réception du message.', error);
    }
});

overlay?.querySelector('[data-secret-delete]')?.addEventListener('click', async (event) => {
    if (!activeMessage || !window.confirm('Supprimer définitivement ce message des deux côtés ?')) return;

    const button = event.currentTarget;
    button.disabled = true;

    try {
        const messageId = activeMessage.id;
        await deleteSecretMessage(activeMessage.delete_url ?? `/messages/${messageId}`);
        removeSecretMessage(messageId);
    } catch (error) {
        console.error('Impossible de supprimer le message.', error);
    } finally {
        button.disabled = false;
    }
});

document.querySelectorAll('[data-secret-message-delete-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!window.confirm('Supprimer définitivement ce message des deux côtés ?')) return;

        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;

        try {
            const row = form.closest('[data-secret-message-row]');
            await deleteSecretMessage(form.action);
            if (row) removeSecretMessage(row.dataset.secretMessageRow);
        } catch (error) {
            console.error('Impossible de supprimer le message.', error);
        } finally {
            button.disabled = false;
        }
    });
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
        .listen('.secret-message.deleted', (event) => removeSecretMessage(event.id))
        .listen('.character-sheet.revealed', showReveal)
        .listen('.npc.revealed', showReveal);
}

if (window.Echo && userRole === 'game_master') {
    window.Echo.private('game-masters')
        .listen('.secret-message.read', (event) => {
            const status = document.querySelector(`[data-message-id="${event.id}"]`);
            if (status) {
                status.textContent = 'Lu';
                status.className = 'badge badge-green';
            }
        })
        .listen('.secret-message.deleted', (event) => removeSecretMessage(event.id));
}

/*
 * Chat privé : l'événement utilisateur alimente le compteur global tandis
 * que le canal de conversation met à jour le fil actuellement ouvert.
 * L'indicateur de frappe est un whisper : il n'est ni stocké ni diffusé à un
 * utilisateur qui ne participe pas à la conversation.
 */
let globalUnreadCount = 0;
const globalChatBadge = document.querySelector('[data-global-chat-unread]');

function renderGlobalUnread(value) {
    globalUnreadCount = Math.max(0, Number(value) || 0);
    if (!globalChatBadge) return;
    globalChatBadge.textContent = globalUnreadCount;
    globalChatBadge.classList.toggle('is-hidden', globalUnreadCount === 0);
}

if (window.Echo && userId) {
    window.Echo.private(`users.${userId}`).listen('.chat-message.sent', (event) => {
        const selectedConversation = document.querySelector('[data-chat]')?.dataset.conversationId;
        if (String(event.conversation_id) !== String(selectedConversation)) {
            renderGlobalUnread(globalUnreadCount + 1);
            const contactBadge = document.querySelector(`[data-chat-unread="${event.conversation_id}"]`);
            if (contactBadge) {
                contactBadge.textContent = Number(contactBadge.textContent || 0) + 1;
                contactBadge.classList.remove('is-hidden');
            }
        }
    });
}

if (userId) {
    fetch('/chat/non-lus/compteur', {headers: {'Accept': 'application/json'}})
        .then((response) => response.ok ? response.json() : null)
        .then((payload) => { if (payload) renderGlobalUnread(payload.count); })
        .catch(() => null);
}

const chat = document.querySelector('[data-chat][data-conversation-id]');

if (chat) {
    const conversationId = chat.dataset.conversationId;
    const list = chat.querySelector('[data-chat-messages]');
    const form = chat.querySelector('[data-chat-form]');
    const input = chat.querySelector('[data-chat-input]');
    const typingIndicator = chat.querySelector('[data-typing-indicator]');
    const seenMessageIds = new Set([...list.querySelectorAll('[data-message-id]')].map((item) => item.dataset.messageId));
    let typingTimer = null;
    let whisperTimer = null;

    function scrollChat() {
        list.scrollTop = list.scrollHeight;
    }

    function appendChatMessage(message) {
        if (seenMessageIds.has(String(message.id))) return;
        seenMessageIds.add(String(message.id));

        const article = document.createElement('article');
        article.className = `chat-message${String(message.sender_id) === String(userId) ? ' is-mine' : ''}`;
        article.dataset.messageId = message.id;
        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble';
        bubble.textContent = message.body;
        const meta = document.createElement('span');
        meta.textContent = `${String(message.sender_id) === String(userId) ? 'Vous' : message.sender_name} · ${message.sent_at_label}`;
        article.append(bubble, meta);
        list.appendChild(article);
        scrollChat();
    }

    async function markConversationRead() {
        await fetch(chat.dataset.readUrl, {
            method: 'POST',
            headers: realtimeHeaders(),
        }).catch(() => null);
        document.querySelector(`[data-chat-unread="${conversationId}"]`)?.classList.add('is-hidden');
        renderGlobalUnread(Math.max(0, globalUnreadCount - 1));
    }

    const channel = window.Echo?.private(`conversations.${conversationId}`);
    channel?.listen('.chat-message.sent', (message) => {
        appendChatMessage(message);
        if (String(message.sender_id) !== String(userId)) markConversationRead();
    });
    channel?.listenForWhisper('typing', (event) => {
        typingIndicator.textContent = event.name ? `${event.name} écrit…` : '';
        window.clearTimeout(typingTimer);
        typingTimer = window.setTimeout(() => { typingIndicator.textContent = ''; }, 1500);
    });

    input?.addEventListener('input', () => {
        window.clearTimeout(whisperTimer);
        channel?.whisper('typing', {name: document.body.dataset.userName || 'Quelqu’un'});
        whisperTimer = window.setTimeout(() => channel?.whisper('typing', {name: ''}), 1200);
    });

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const body = input.value.trim();
        if (!body) return;
        input.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: realtimeHeaders({'Content-Type': 'application/json'}),
                body: JSON.stringify({body}),
            });
            if (!response.ok) throw new Error(response.statusText);
            appendChatMessage(await response.json());
            input.value = '';
        } catch (error) {
            console.error('Message non envoyé.', error);
        } finally {
            input.disabled = false;
            input.focus();
        }
    });

    scrollChat();
}

/* Ressources de fiche : mise à jour optimiste, sauvegarde temporisée et
 * synchronisation MJ/joueur sur le canal privé du personnage. */
function renderResourcesPanel(panel, resources) {
    const health = panel.querySelector('[data-resource="health"]');
    const mana = panel.querySelector('[data-resource="mana_current"]');
    if (health) {
        health.max = resources.max_health;
        health.value = resources.health;
        health.style.setProperty('--resource-progress', `${resources.health_percentage}%`);
        panel.querySelector('[data-resource-value="health"]').textContent = `${resources.health} / ${resources.max_health}`;
    }
    if (mana) {
        mana.max = Math.max(1, resources.mana_max);
        mana.value = resources.mana;
        mana.disabled = resources.mana_max === 0;
        mana.style.setProperty('--resource-progress', `${resources.mana_percentage}%`);
        panel.querySelector('[data-resource-value="mana_current"]').textContent = `${resources.mana} / ${resources.mana_max}`;
    }

    const total = document.querySelector('[data-group-health-total]');
    const critical = document.querySelector('[data-group-critical]');
    if (total || critical) {
        const healthRanges = [...document.querySelectorAll('[data-character-resources] [data-resource="health"]')];
        if (total) total.textContent = healthRanges.reduce((sum, range) => sum + Number(range.value), 0);
        if (critical) critical.textContent = healthRanges.filter((range) => Number(range.value) / Math.max(1, Number(range.max)) < .35).length;
    }
}

function renderCharacterResources(resources) {
    document.querySelectorAll(`[data-character-resources="${CSS.escape(String(resources.character_id))}"]`)
        .forEach((panel) => renderResourcesPanel(panel, resources));

    document.querySelectorAll(`[data-character-resource-summary="${CSS.escape(String(resources.character_id))}"]`)
        .forEach((summary) => {
            const health = summary.querySelector('[data-summary-health]');
            const mana = summary.querySelector('[data-summary-mana]');
            const healthGauge = summary.querySelector('[data-summary-health-gauge]');
            const manaGauge = summary.querySelector('[data-summary-mana-gauge]');

            if (health) health.textContent = `${resources.health} / ${resources.max_health}${health.classList.contains('badge') ? ' PV' : ''}`;
            if (mana) mana.textContent = `${resources.mana} / ${resources.mana_max}${mana.classList.contains('badge') ? ' mana' : ''}`;
            if (healthGauge) healthGauge.style.width = `${resources.health_percentage}%`;
            if (manaGauge) manaGauge.style.width = `${resources.mana_percentage}%`;
        });
}

document.querySelectorAll('[data-character-resources]').forEach((panel) => {
    const characterId = panel.dataset.characterResources;
    const status = panel.querySelector('[data-resource-status]');
    const timers = new Map();

    panel.querySelectorAll('[data-resource]').forEach((range) => {
        range.addEventListener('input', () => {
            const maximum = Number(range.max);
            const percentage = maximum > 0 ? (Number(range.value) / maximum) * 100 : 0;
            range.style.setProperty('--resource-progress', `${Math.max(0, Math.min(100, percentage))}%`);
            panel.querySelector(`[data-resource-value="${range.dataset.resource}"]`).textContent = `${range.value} / ${maximum}`;
            status.textContent = 'Modification…';
            window.clearTimeout(timers.get(range.dataset.resource));
            timers.set(range.dataset.resource, window.setTimeout(async () => {
                try {
                    const response = await fetch(panel.dataset.resourceUrl, {
                        method: 'PUT',
                        headers: realtimeHeaders({'Content-Type': 'application/json'}),
                        body: JSON.stringify({resource: range.dataset.resource, value: Number(range.value)}),
                    });
                    if (!response.ok) throw new Error(response.statusText);
                    renderResourcesPanel(panel, await response.json());
                    status.textContent = 'Enregistré';
                } catch (error) {
                    status.textContent = 'Échec de l’enregistrement';
                    console.error('Ressource non enregistrée.', error);
                }
            }, 180));
        });
    });

});

// Ressources publiques de la tablée : chaque page authentifiée reste à jour,
// y compris le dashboard MJ, les fiches joueur et les vues d'alliés.
window.Echo?.private('table')
    .listen('.character-resources.updated', renderCharacterResources);

// Les autres événements de fiche restent privés au propriétaire et au MJ.
const visibleCharacterIds = new Set(
    [...document.querySelectorAll('[data-character-resources]')]
        .map((panel) => panel.dataset.characterResources),
);
if (ownedCharacterId) visibleCharacterIds.add(ownedCharacterId);

visibleCharacterIds.forEach((characterId) => {
    window.Echo?.private(`characters.${characterId}`)
        .listen('.character-skill.updated', updateSkillRow)
        .listen('.character-sheet.updated', (sheet) => applySheetUpdate(sheet));
});

/* Modale de bonus de compétence côté joueur. */
const skillModal = document.querySelector('[data-skill-modal]');
let activeSkillButton = null;

function signed(value) {
    const numeric = Number(value);
    return numeric > 0 ? `+${numeric}` : String(numeric);
}

function updateSkillRow(skill) {
    const row = document.querySelector(`[data-skill-id="${skill.id}"]`);
    if (!row) return;
    const display = row.querySelector('[data-skill-display]');
    if (display) display.textContent = skill.display;
    const breakdown = row.querySelector('[data-skill-breakdown]');
    if (breakdown) {
        const sign = skill.bonus >= 0 ? '+' : '−';
        breakdown.textContent = `(${skill.base_value} ${sign} ${Math.abs(skill.bonus)})`;
    }
    const opener = row.querySelector('[data-skill-open]');
    if (opener) {
        opener.dataset.base = skill.base_value;
        opener.dataset.gmBonus = skill.gm_bonus;
        opener.dataset.playerBonus = skill.player_bonus;
        if (skill.gm_notes !== undefined) opener.dataset.gmNotes = skill.gm_notes || '';
        if (skill.reveal_state !== undefined) opener.dataset.revealState = skill.reveal_state;
    }
}

function updateAttributeCard(attribute) {
    const card = document.querySelector(`[data-attribute-id="${attribute.id}"]`);
    if (!card) return;
    card.querySelector('.stat-value').textContent = attribute.display;
    const modifier = card.querySelector('[data-attribute-modifier]');
    if (modifier) modifier.textContent = attribute.modifier === 0 ? '' : `${attribute.modifier > 0 ? '+' : ''}${attribute.modifier} de modificateur`;
    const opener = card.querySelector('[data-attribute-open]');
    if (opener) {
        opener.dataset.value = attribute.value;
        opener.dataset.modifier = attribute.modifier;
    }
}

function applySheetUpdate(sheet) {
    sheet.attributes?.forEach(updateAttributeCard);
    sheet.skills?.forEach(updateSkillRow);
    if (sheet.resources) {
        document.querySelectorAll(`[data-character-resources="${sheet.character_id}"]`)
            .forEach((panel) => renderResourcesPanel(panel, sheet.resources));
    }
}

function renderSkillModal() {
    if (!skillModal || !activeSkillButton) return;
    const base = Number(activeSkillButton.dataset.base);
    const gmInput = skillModal.querySelector('[data-skill-gm-input]');
    const gm = Number(gmInput ? gmInput.value : activeSkillButton.dataset.gmBonus);
    const player = Number(skillModal.querySelector('[data-skill-player]').value);
    skillModal.querySelector('[data-skill-base]').textContent = base;
    skillModal.querySelector('[data-skill-gm]').textContent = signed(gm);
    skillModal.querySelector('[data-skill-player-label]').textContent = signed(player);
    skillModal.querySelector('[data-skill-total]').textContent = `${Math.max(0, Math.min(100, base + gm + player))} %`;
}

document.querySelectorAll('[data-skill-open]').forEach((button) => button.addEventListener('click', () => {
    activeSkillButton = button;
    skillModal.querySelector('[data-skill-name]').textContent = button.dataset.name;
    skillModal.querySelector('[data-skill-player]').value = button.dataset.playerBonus;
    const gmInput = skillModal.querySelector('[data-skill-gm-input]');
    if (gmInput) gmInput.value = button.dataset.gmBonus;
    const notes = skillModal.querySelector('[data-skill-notes]');
    if (notes) notes.value = button.dataset.gmNotes || '';
    const reveal = skillModal.querySelector('[data-skill-reveal]');
    if (reveal) reveal.value = button.dataset.revealState || 'revealed';
    skillModal.querySelector('[data-skill-status]').textContent = '';
    renderSkillModal();
    skillModal.classList.add('is-open');
    skillModal.querySelector('[data-skill-player]').focus();
}));

skillModal?.querySelectorAll('[data-skill-close]').forEach((button) => button.addEventListener('click', () => skillModal.classList.remove('is-open')));
skillModal?.querySelector('[data-skill-player]')?.addEventListener('input', renderSkillModal);
skillModal?.querySelector('[data-skill-gm-input]')?.addEventListener('input', renderSkillModal);
skillModal?.querySelector('[data-skill-form]')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const status = skillModal.querySelector('[data-skill-status]');
    status.textContent = 'Enregistrement…';
    try {
        const payload = {player_bonus: Number(skillModal.querySelector('[data-skill-player]').value)};
        const gmInput = skillModal.querySelector('[data-skill-gm-input]');
        if (gmInput) {
            payload.bonus = Number(gmInput.value);
            payload.gm_notes = skillModal.querySelector('[data-skill-notes]').value;
            payload.reveal_state = skillModal.querySelector('[data-skill-reveal]').value;
        }
        const response = await fetch(activeSkillButton.dataset.url, {
            method: 'PUT',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken},
            body: JSON.stringify(payload),
        });
        if (!response.ok) throw new Error(response.statusText);
        const skill = await response.json();
        updateSkillRow(skill);
        status.textContent = 'Enregistré';
        window.setTimeout(() => skillModal.classList.remove('is-open'), 300);
    } catch (error) {
        status.textContent = 'Échec de l’enregistrement';
        console.error('Bonus non enregistré.', error);
    }
});

/* Caractéristiques principales : édition MJ en modale, puis propagation de
 * la fiche recalculée (compétences et mana compris) sur le canal du personnage. */
const attributeModal = document.querySelector('[data-attribute-modal]');
let activeAttributeButton = null;

document.querySelectorAll('[data-attribute-open]').forEach((button) => button.addEventListener('click', () => {
    activeAttributeButton = button;
    attributeModal.querySelector('[data-attribute-name]').textContent = button.dataset.name;
    attributeModal.querySelector('[data-attribute-value]').value = button.dataset.value;
    attributeModal.querySelector('[data-attribute-modifier-input]').value = button.dataset.modifier;
    attributeModal.querySelector('[data-attribute-status]').textContent = '';
    attributeModal.classList.add('is-open');
    attributeModal.querySelector('[data-attribute-value]').focus();
}));

attributeModal?.querySelectorAll('[data-attribute-close]').forEach((button) => button.addEventListener('click', () => attributeModal.classList.remove('is-open')));
attributeModal?.querySelector('[data-attribute-form]')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const status = attributeModal.querySelector('[data-attribute-status]');
    status.textContent = 'Enregistrement…';
    try {
        const response = await fetch(activeAttributeButton.dataset.url, {
            method: 'PUT',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken},
            body: JSON.stringify({
                value: Number(attributeModal.querySelector('[data-attribute-value]').value),
                modifier: Number(attributeModal.querySelector('[data-attribute-modifier-input]').value),
            }),
        });
        if (!response.ok) throw new Error(response.statusText);
        applySheetUpdate(await response.json());
        status.textContent = 'Enregistré';
        window.setTimeout(() => attributeModal.classList.remove('is-open'), 300);
    } catch (error) {
        status.textContent = 'Échec de l’enregistrement';
        console.error('Caractéristique non enregistrée.', error);
    }
});

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
        const body = editor.querySelector('.note-relationship')
            ? {personal_notes: content.innerHTML}
            : {content: content.innerHTML};

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
            if (event.target.closest('.map-point')) return;
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
                marker.dataset.deleteUrl = point.delete_url;
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

    pointLayer.addEventListener('click', async (event) => {
        const marker = event.target.closest('.map-point[data-delete-url]');
        if (!marker || !window.confirm(`Supprimer le repère « ${marker.querySelector('.map-point-label')?.textContent.trim()} » ?`)) return;

        try {
            await send(marker.dataset.deleteUrl, 'DELETE');
            document.querySelector(`[data-point-row="${marker.dataset.pointId}"]`)?.remove();
            marker.remove();
        } catch (error) {
            console.error('Repère non supprimé.', error);
        }
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
