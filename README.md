# EventLoc — Location & vente de mobilier événementiel

Site vitrine + e-commerce pour une entreprise de location (et bientôt de vente) de mobilier événementiel au Togo : chaises, tables, nappes, glacières, etc.

## Fonctionnalités (V1)

- **Catalogue en ligne** multilingue (FR/EN) avec prix et stock affichés.
- **Moteur de disponibilité en temps réel** : le stock disponible est calculé pour la période exacte demandée par le client (et non un simple compteur global), en tenant compte de toutes les réservations qui se chevauchent.
- **Demande de devis** : le client choisit ses articles, une période, un mode de livraison/retrait (avec créneaux horaires), puis reçoit instantanément un **devis PDF** généré automatiquement.
- **Bouton WhatsApp** pré-rempli pour confirmer sa demande directement avec l'entreprise.
- **Suivi de devis** par référence.
- **Back-office admin** (Filament) pour gérer le catalogue, les stocks, les réservations, les créneaux de livraison et les clients.

### Roadmap (V2+)

- Vente ferme (achat définitif) en plus de la location.
- Paiement en ligne (Mobile Money Togo — T-Money, Flooz — via CinetPay/PayDunya/Flutterwave).
- API WhatsApp Business (confirmations et rappels automatiques).
- Application mobile (Flutter/React Native) réutilisant l'API existante.

## Stack technique

| Composant       | Choix                                                                 |
|-----------------|------------------------------------------------------------------------|
| API backend     | Laravel 13 (PHP 8.4), MySQL 8, Redis (cache/queue), Sanctum            |
| Back-office     | Filament 4                                                             |
| PDF             | barryvdh/laravel-dompdf                                                |
| Rôles/permissions | spatie/laravel-permission                                            |
| Frontend        | Next.js 16 (React 19, App Router), Tailwind CSS, next-intl (FR/EN)     |
| Files d'attente | Laravel Horizon                                                        |

**Pourquoi une API séparée du frontend ?** Le site consomme une API REST (`/api/v1/...`) plutôt qu'un monolithe Blade/Livewire, pour pouvoir réutiliser exactement la même API depuis une future application mobile sans dupliquer la logique métier.

**Le cœur technique : le moteur de disponibilité.** Un article n'a pas un "stock" au sens classique : il est indisponible seulement pendant la durée des réservations qui le concernent. `App\Services\AvailabilityService` calcule, pour une période donnée, le pic d'occupation jour par jour (algorithme de balayage/tableau de différences) et en déduit la quantité réellement disponible. La création d'un devis verrouille les lignes d'articles concernées (`lockForUpdate`) pendant la vérification + l'écriture, pour qu'une double réservation concurrente sur le même stock soit impossible.

## Structure du dépôt

```
backend/    API Laravel + back-office Filament
frontend/   Site vitrine Next.js (FR/EN)
docker-compose.yml   Environnement de dev (MySQL, Redis, PHP-FPM, Nginx, Horizon)
```

## Démarrage rapide

### Avec Docker (recommandé)

```bash
cp backend/.env.example backend/.env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
```

L'API est disponible sur `http://localhost:8000/api/v1`, le back-office sur `http://localhost:8000/admin` (identifiants créés par le seeder : `admin@eventloc.tg` / `password` — **à changer immédiatement**).

Puis, pour le frontend :

```bash
cd frontend
cp .env.example .env.local
npm install
npm run dev
```

Le site est disponible sur `http://localhost:3000`.

### Sans Docker (SQLite, pour un dev rapide)

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

## Tests

```bash
cd backend
php artisan test
```

Les tests couvrent en priorité le moteur de disponibilité (chevauchements de dates, annulations, expiration des devis non confirmés) et le flux complet de création de devis (y compris le rejet en cas de stock insuffisant).

## Notes de sécurité / production

- Le CORS de l'API est ouvert (`*`) par défaut pour le développement — définir `CORS_ALLOWED_ORIGINS` avec le(s) domaine(s) réel(s) en production.
- Le suivi de devis (`GET /reservations/{reference}`) ne demande que la référence ; celle-ci contient un suffixe aléatoire, mais une vérification d'identité (email/téléphone) est recommandée avant une mise en production.
- Un devis (`quote_sent`) bloque le stock pendant 48h (`Reservation::QUOTE_HOLD_HOURS`). La commande planifiée `reservations:expire-stale-quotes` (exécutée chaque heure via `routes/console.php`) annule automatiquement les devis expirés — en production, un cron doit appeler `php artisan schedule:run` chaque minute (ou `docker compose` doit lancer un conteneur scheduler dédié).
