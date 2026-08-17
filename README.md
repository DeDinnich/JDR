# Le Fil d’Ambre — compagnon JDR

Application Laravel 13 de compagnon de partie médiéval-fantasy, avec espaces joueur et maître du jeu strictement séparés.

## Fonctionnalités

### Joueur

- tableau de bord avec état, zone actuelle et repères immédiats ;
- fiche de personnage, caractéristiques et compétences ;
- inventaire groupé, équipement, poids et monnaie ;
- cartes et lieux découverts uniquement ;
- journal privé avec notes épinglées ;
- glossaire des PNJ rencontrés, relation perçue et notes personnelles ;
- messages privés du MJ en temps réel avec overlay prioritaire et accusé de lecture.

### Maître du jeu

- vue synthétique des joueurs inscrits ;
- édition complète des fiches, caractéristiques et états ;
- attribution/retrait de compétences et objets, y compris en mode secret ;
- création et édition des cartes, lieux et PNJ ;
- notes MJ jamais exposées aux joueurs ;
- révélations individuelles ou globales avec propagation cohérente des parents ;
- messages Reverb ciblés sur le canal privé d’un seul joueur.

## Démarrage avec Sail

Le `.env`, Docker, Supervisord, MySQL, Reverb et les workers de queue proviennent du template d’origine et sont conservés.

```bash
./vendor/bin/sail up -d --build
./vendor/bin/sail artisan migrate:fresh --seed
```

L’application est ensuite accessible sur [http://localhost](http://localhost). Vite, Reverb et les workers sont lancés par Supervisord dans le conteneur applicatif.

Pour arrêter la stack :

```bash
./vendor/bin/sail down
```

## Comptes et inscription

Le seed crée uniquement le compte maître du jeu à partir des variables obligatoires :

```dotenv
ADMIN_MAIL=mj@example.com
ADMIN_PASSWORD=un-mot-de-passe-robuste
```

Les joueurs rejoignent ensuite la campagne depuis `/inscription` avec le nom de leur personnage, leur e-mail et leur mot de passe. Une fiche initiale est créée automatiquement, puis le MJ peut la compléter.

## Vérification

```bash
composer test
npm run build
npm audit
```

La suite couvre la séparation des rôles, la navigation des deux espaces, la confidentialité des notes, les révélations individuelles/globales, le ciblage des messages et les accusés de lecture.

## Architecture

- contrôleurs HTTP fins et validation via `FormRequest` ;
- logique de mutation dans `CharacterManagementService`, `WorldContentService`, `SecretMessageService` et `PlayerRegistrationService` ;
- événements synchrones `SecretMessageSent` / `SecretMessageRead` sur canaux privés Reverb ;
- relations de découverte par joueur pour les cartes, lieux et PNJ ;
- fallback de relève périodique des messages non lus si la connexion WebSocket est momentanément indisponible ;
- vues Blade, Tailwind CSS 4 et JavaScript léger, sans framework front additionnel.

## Production

- utiliser un mot de passe MJ unique et robuste dans `ADMIN_PASSWORD` ;
- positionner `APP_ENV=production`, `APP_DEBUG=false` et les variables Reverb publiques/privées adaptées au domaine ;
- servir les assets compilés via `npm run build` ;
- conserver un reverse proxy TLS devant Laravel/Reverb et restreindre les ports internes ;
- exécuter `php artisan optimize` pendant le déploiement.
