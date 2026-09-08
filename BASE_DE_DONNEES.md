# Base de données de développement

L'application tourne sur PostgreSQL. Seule la base est conteneurisée : Laravel
s'exécute sur la machine hôte via `php artisan serve`.

## Démarrer

```bash
docker compose -f docker-compose.dev.yml up -d
```

Deux conteneurs sont lancés :

| Service  | Rôle                        | Accès                     |
|----------|-----------------------------|---------------------------|
| postgres | PostgreSQL 16               | `127.0.0.1:15432`         |
| adminer  | Console SQL dans navigateur | <http://127.0.0.1:15433>  |

Les ports 5432 à 5443 étant déjà occupés sur le poste par d'autres projets, la
base est publiée sur **15432**. Le port interne au réseau Docker reste 5432.

Identifiants : base `egesco`, utilisateur `egesco`, mot de passe `egesco`.
Ils correspondent au bloc `DB_*` de `.env`.

## Créer le schéma et les données

```bash
php artisan migrate:fresh --seed
```

La commande est rejouable : elle vide la base, rejoue les 64 migrations puis la
chaîne de seeders.

## Comptes créés

| Adresse                    | Mot de passe     | Rôle          |
|----------------------------|------------------|---------------|
| `superadmin@ecole.com`     | `superadmin123`  | superadmin    |
| `admin@ecole.com`          | `admin123`       | admin         |
| `secretariat@ecole.com`    | `secretariat123` | secretary     |
| `enseignant@ecole.com`     | `enseignant123`  | teacher       |
| `parent@ecole.com`         | `parent123`      | parent        |

Ces mots de passe sont volontairement lisibles : ce jeu est destiné au
développement et à la démonstration, jamais à la production.

## Données d'exemple

`DatabaseSeeder` produit un établissement complet : 15 niveaux, 20 séries de
lycée, 37 classes, 37 matières, 28 enseignants, ~750 élèves et leurs
inscriptions, 50 parents rattachés, les frais par niveau, les passerelles de
paiement et les présences.

`DonneesDemoSeeder` remplit ensuite ce que les autres seeders laissaient vide,
et sans quoi le tableau de bord affichait des zéros :

- les liens parent-élève (un à deux responsables par élève, dont un principal) ;
- le chiffrage de chaque inscription à partir des frais de son niveau ;
- les paiements encaissés, répartis entre dossiers soldés, partiels et impayés ;
- les notes des trois trimestres pour les élèves du collège et du lycée.

## Arrêter

```bash
docker compose -f docker-compose.dev.yml down      # conserve les données
docker compose -f docker-compose.dev.yml down -v   # supprime le volume
```

## Note sur `docker-compose.yml`

Le fichier `docker-compose.yml` décrit la pile complète (application, Nginx,
queue, Redis) et n'est pas nécessaire pour développer. Sa base de données est
également passée à PostgreSQL, mais il reste inutilisable en l'état : il monte
`./docker/nginx` et `./docker/mysql`, deux répertoires absents du dépôt.
