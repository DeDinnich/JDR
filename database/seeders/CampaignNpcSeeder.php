<?php

namespace Database\Seeders;

use App\Models\House;
use App\Models\Npc;
use Illuminate\Database\Seeder;

/**
 * PNJ de la première séance : parents des joueurs, personnel du château et roi.
 *
 * Chaque PNJ est identifié par son nom complet, ce qui rend le seeder
 * idempotent. Les secrets partent dans npc_secrets (strictement MJ) et les
 * informations dévoilables dans npc_informations — non révélées par défaut,
 * donc invisibles au joueur tant que le MJ n'a rien ouvert.
 */
class CampaignNpcSeeder extends Seeder
{
    public function run(): void
    {
        $houses = House::query()->pluck('id', 'slug');

        foreach ($this->npcs() as $definition) {
            $secrets = $definition['secrets'] ?? [];
            $informations = $definition['informations'] ?? [];
            $houseSlug = $definition['house'] ?? null;

            unset($definition['secrets'], $definition['informations'], $definition['house']);

            $definition['house_id'] = $houseSlug ? $houses[$houseSlug] ?? null : null;
            $definition['name'] = trim($definition['first_name'].' '.($definition['last_name'] ?? ''));

            $npc = Npc::updateOrCreate(['name' => $definition['name']], $definition);

            foreach ($secrets as $index => $secret) {
                $npc->secrets()->updateOrCreate(
                    ['title' => $secret['title']],
                    ['content' => $secret['content'], 'sort_order' => $index],
                );
            }

            foreach ($informations as $index => $information) {
                $npc->informations()->updateOrCreate(
                    ['title' => $information['title']],
                    [
                        'content' => $information['content'],
                        'category' => $information['category'] ?? 'autre',
                        'sort_order' => $index,
                    ],
                );
            }
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function npcs(): array
    {
        return [
            // — Maison Valtheris ------------------------------------------------
            [
                'first_name' => 'Aldric', 'last_name' => 'Valtheris',
                'title' => 'Lord', 'age' => 31, 'gender' => 'Homme', 'race' => 'Humain',
                'profession' => 'Commandant dans l\'armée royale', 'family_role' => 'pere',
                'house' => 'valtheris', 'importance' => 'majeur', 'role' => 'Père (Valtheris)',
                'description' => "Un homme au maintien droit, au regard grave, que l'on croise plus souvent en tenue de campagne qu'en habit de cour.",
                'personality' => "Discipliné, exigeant, réservé, très attaché au devoir. Il n'est pas cruel : il aime réellement sa famille mais montre peu ses émotions.",
                'tags' => ['noble', 'armée', 'parent'],
                'game_master_notes' => 'Doute croissant sur la conduite de la guerre.',
                'secrets' => [
                    ['title' => 'Doutes sur la guerre', 'content' => 'Aldric pense que la guerre contre Rania évolue mal et commence à douter du commandement royal.'],
                ],
                'informations' => [
                    ['title' => 'Ton père', 'content' => "L'homme qui te porte parfois, dont la voix grave résonne dans le couloir.", 'category' => 'relation'],
                    ['title' => 'Fonction', 'content' => "Commandant dans l'armée royale d'Ashura.", 'category' => 'profession'],
                ],
            ],
            [
                'first_name' => 'Éléonore', 'last_name' => 'Valtheris',
                'title' => 'Dame', 'age' => 27, 'gender' => 'Femme', 'race' => 'Humain',
                'profession' => 'Dame de la maison Valtheris', 'family_role' => 'mere',
                'house' => 'valtheris', 'importance' => 'majeur', 'role' => 'Mère (Valtheris)',
                'description' => 'Une femme posée, attentive, qui semble toujours savoir qui parle à qui dans une pièce.',
                'personality' => 'Cultivée, calme, protectrice, affectueuse. Elle comprend très bien les jeux politiques de la cour.',
                'tags' => ['noble', 'cour', 'parent'],
                'informations' => [
                    ['title' => 'Ta mère', 'content' => 'La première voix que tu reconnais.', 'category' => 'relation'],
                ],
            ],

            // — Maison Aerendis -------------------------------------------------
            [
                'first_name' => 'Théodore', 'last_name' => 'Aerendis',
                'title' => 'Maître', 'age' => 35, 'gender' => 'Homme', 'race' => 'Humain',
                'profession' => 'Mage de cour', 'family_role' => 'pere',
                'house' => 'aerendis', 'importance' => 'majeur', 'role' => 'Père (Aerendis)',
                'description' => "Un homme distrait, souvent couvert d'encre, qui oublie de manger quand une expérience l'occupe.",
                'personality' => 'Passionné, brillant, curieux, parfois socialement maladroit ou totalement absorbé par ses recherches.',
                'tags' => ['noble', 'magie', 'parent'],
                'secrets' => [
                    ['title' => 'Fascination pour l\'enfant', 'content' => 'Il pourrait devenir obsessionnel si son enfant développe des capacités magiques inhabituelles.'],
                ],
                'informations' => [
                    ['title' => 'Ton père', 'content' => 'Il te parle déjà comme à un adulte, ce qui fait sourire ta mère.', 'category' => 'relation'],
                    ['title' => 'Fonction', 'content' => 'Mage de cour, spécialiste de magie élémentaire.', 'category' => 'profession'],
                ],
            ],
            [
                'first_name' => 'Lysandra', 'last_name' => 'Aerendis',
                'title' => 'Dame', 'age' => 30, 'gender' => 'Femme', 'race' => 'Humain',
                'profession' => 'Ancienne enseignante de magie', 'family_role' => 'mere',
                'house' => 'aerendis', 'importance' => 'majeur', 'role' => 'Mère (Aerendis)',
                'description' => "Une femme au calme méthodique, qui corrige un geste d'un simple regard.",
                'personality' => 'Pragmatique, douce mais exigeante.',
                'tags' => ['noble', 'magie', 'parent'],
                'secrets' => [
                    ['title' => 'Cercle d\'érudits', 'content' => "Elle appartient à un petit cercle convaincu que le potentiel de mana peut énormément évoluer pendant l'enfance grâce à une pratique très précoce."],
                ],
                'informations' => [
                    ['title' => 'Ta mère', 'content' => "Elle t'observe beaucoup, comme si elle attendait quelque chose.", 'category' => 'relation'],
                ],
            ],

            // — Maison Vaelmont -------------------------------------------------
            [
                'first_name' => 'Cassian', 'last_name' => 'Vaelmont',
                'title' => 'Conseiller', 'age' => 29, 'gender' => 'Homme', 'race' => 'Humain',
                'profession' => 'Diplomate et conseiller royal', 'family_role' => 'pere',
                'house' => 'vaelmont', 'importance' => 'majeur', 'role' => 'Père (Vaelmont)',
                'description' => 'Un homme élégant au regard assuré, dont la voix porte sans jamais être forte.',
                'personality' => 'Charismatique, très intelligent, élégant, manipulateur lorsque nécessaire. Il aime réellement sa famille.',
                'tags' => ['noble', 'diplomatie', 'parent'],
                'secrets' => [
                    ['title' => 'Correspondance avec Rania', 'content' => "Cassian entretient une correspondance secrète avec quelqu'un du royaume de Rania. Espionnage, tentative de paix, négociation ou trahison : la vérité reste volontairement indéterminée."],
                ],
                'informations' => [
                    ['title' => 'Ton père', 'content' => 'Un homme élégant que tu vois régulièrement auprès de ta mère. Tu commences à reconnaître sa voix.', 'category' => 'relation'],
                    ['title' => 'Fonction', 'content' => 'Conseiller de Sa Majesté.', 'category' => 'profession'],
                ],
            ],
            [
                'first_name' => 'Marianne', 'last_name' => 'Vaelmont',
                'title' => 'Dame', 'age' => 25, 'gender' => 'Femme', 'race' => 'Humain',
                'profession' => 'Dame de la cour', 'family_role' => 'mere',
                'house' => 'vaelmont', 'importance' => 'majeur', 'role' => 'Mère (Vaelmont)',
                'description' => 'Une femme souriante que tout le monde semble connaître.',
                'personality' => 'Sociable, élégante, empathique, très au courant des rumeurs de la cour.',
                'tags' => ['noble', 'cour', 'parent'],
                'informations' => [
                    ['title' => 'Ta mère', 'content' => 'Elle te présente à énormément de gens.', 'category' => 'relation'],
                ],
            ],

            // — Famille Veyre ---------------------------------------------------
            [
                'first_name' => 'Éléonora', 'last_name' => 'Veyre',
                'title' => 'Bibliothécaire en chef', 'age' => 32, 'gender' => 'Femme', 'race' => 'Humain',
                'profession' => 'Bibliothécaire en chef de la Grande Bibliothèque royale', 'family_role' => 'mere',
                'house' => 'veyre', 'importance' => 'central', 'role' => 'Mère (Veyre)',
                'description' => 'Une femme réservée, non noble, dont la fonction lui donne accès à des archives que bien des nobles ne verront jamais.',
                'personality' => 'Très cultivée, réservée, intelligente, protectrice.',
                'tags' => ['bibliothèque', 'parent', 'non-noble'],
                'game_master_notes' => 'Sait parfaitement qui est le père de son enfant.',
                'secrets' => [
                    ['title' => 'Le père de son enfant', 'content' => "Le père est le roi d'Ashura. Éléonora le sait. Le roi le sait. Très peu de personnes au château sont au courant."],
                ],
                'informations' => [
                    ['title' => 'Ta mère', 'content' => "Éléonora Veyre, bibliothécaire en chef de la Grande Bibliothèque royale d'Ashura.", 'category' => 'relation'],
                    // Catégorie « relation » : cette information est ouverte dès
                    // la naissance. Le joueur apprend que son père est inconnu —
                    // la vérité (le roi) reste un secret MJ, table npc_secrets.
                    ['title' => 'Ton père', 'content' => 'Inconnu.', 'category' => 'relation'],
                ],
            ],

            // — Le roi ----------------------------------------------------------
            [
                'first_name' => 'Alaric', 'last_name' => 'Ashura',
                'nickname' => 'Alaric III', 'title' => "Roi d'Ashura", 'age' => 41,
                'gender' => 'Homme', 'race' => 'Humain', 'profession' => 'Souverain du royaume d\'Ashura',
                'importance' => 'central', 'role' => 'Roi',
                'description' => "Le souverain d'Ashura, un homme que l'on aperçoit de loin et que l'on entend surtout à travers ses décrets.",
                'personality' => "Maîtrisé en public, difficile à lire. Porte le poids d'une guerre qu'il n'a pas choisie.",
                'tags' => ['royauté', 'politique'],
                'game_master_notes' => 'Ses sentiments réels envers Éléonora et ses intentions envers sa fille restent volontairement ouverts.',
                'secrets' => [
                    ['title' => 'Père biologique de l\'enfant Veyre', 'content' => "Alaric est le père biologique de la fille d'Éléonora Veyre. Il connaît son existence et a discrètement garanti une forme de protection à la mère. Amour, regret, reconnaissance future ou danger politique : rien n'est encore décidé."],
                ],
                'informations' => [
                    ['title' => 'Le roi', 'content' => "Alaric III, souverain du royaume d'Ashura.", 'category' => 'identite'],
                ],
            ],

            // — Personnel du château -------------------------------------------
            [
                'first_name' => 'Oren', 'title' => 'Maître', 'age' => 63,
                'gender' => 'Homme', 'race' => 'Humain', 'profession' => 'Médecin royal',
                'importance' => 'secondaire', 'role' => 'Médecin royal',
                'description' => 'Un vieil homme bourru aux mains sûres. Il a supervisé les quatre naissances.',
                'personality' => 'Bourru, expérimenté, extrêmement compétent.',
                'tags' => ['château', 'soin'],
                'informations' => [
                    ['title' => 'Le médecin', 'content' => 'Le premier visage penché sur toi.', 'category' => 'relation'],
                ],
            ],
            [
                'first_name' => 'Amélia', 'title' => 'Sœur', 'age' => 34,
                'gender' => 'Femme', 'race' => 'Humain', 'profession' => 'Soigneuse du château',
                'importance' => 'secondaire', 'role' => 'Soigneuse',
                'description' => 'Une soigneuse à la voix basse, spécialisée dans la magie curative légère.',
                'personality' => 'Douce, attentive, posée.',
                'tags' => ['château', 'soin', 'magie'],
                'informations' => [
                    ['title' => 'La soigneuse', 'content' => 'Une lumière tiède et une voix douce, tout près.', 'category' => 'relation'],
                ],
            ],
            [
                'first_name' => 'Marta', 'age' => 45, 'gender' => 'Femme', 'race' => 'Humain',
                'profession' => 'Nourrice du château', 'importance' => 'majeur', 'role' => 'Nourrice',
                'description' => "La nourrice qui s'occupe quotidiennement des quatre enfants.",
                'personality' => "Chaleureuse, mais autoritaire lorsqu'il le faut.",
                'tags' => ['château', 'enfance'],
                'informations' => [
                    ['title' => 'La nourrice', 'content' => 'Celle qui est toujours là quand tu pleures.', 'category' => 'relation'],
                ],
            ],
            [
                'first_name' => 'Edgar', 'age' => 52, 'gender' => 'Homme', 'race' => 'Humain',
                'profession' => 'Majordome', 'importance' => 'secondaire', 'role' => 'Majordome',
                'description' => "Majordome de l'aile où résident temporairement les grandes familles.",
                'personality' => 'Très protocolaire, rigoureux, discret.',
                'tags' => ['château', 'service'],
            ],
            [
                'first_name' => 'Kaelen', 'title' => 'Ser', 'age' => 23,
                'gender' => 'Homme', 'race' => 'Humain', 'profession' => 'Chevalier de la garde royale',
                'importance' => 'majeur', 'role' => 'Garde royal',
                'description' => "Un jeune chevalier chargé d'une partie de la sécurité de l'aile des familles.",
                'personality' => 'Sérieux, encore jeune, plutôt accessible.',
                'tags' => ['château', 'garde'],
            ],
            [
                'first_name' => 'Elira', 'last_name' => 'Ashura', 'title' => 'Princesse',
                'age' => 4, 'gender' => 'Femme', 'race' => 'Humain', 'profession' => 'Princesse d\'Ashura',
                'importance' => 'majeur', 'role' => 'Princesse',
                'description' => "La princesse du royaume, de quelques années votre aînée. On la croise plus souvent qu'on ne le croirait.",
                'personality' => 'Curieuse, fière, intelligente, et parfois franchement maladroite en société.',
                'tags' => ['royauté', 'enfance'],
            ],
        ];
    }
}
