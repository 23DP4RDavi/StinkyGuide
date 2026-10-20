# StinkyGuide

A player-powered game discovery and guide site built with **Laravel 13**, **Vue 3**, **Vite**, and **SQLite**.

## Get started

Requirements: PHP 8.3+ with PDO SQLite, Composer, and Node.js 22.13+.

```powershell
composer install
npm install
php artisan key:generate
# Set ADMIN_INITIAL_PASSWORD in .env to a unique password (at least 16 characters).
php artisan migrate --seed
npm run dev

```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000). `npm run dev` starts the Laravel web server and Vite together. For a production asset build, run `npm run build`; serve the app with your preferred PHP/Laravel hosting setup.

## Database and migrated content

The Laravel app uses SQLite at `database/database.sqlite`. Migrations create the account, game, recommendation, game-library, guide, helpful-vote, saved-guide, guide-comment, review, session, cache, and queue tables.

The first `php artisan db:seed` imports the existing games, guides, helpful counts, author names, reviews, and timestamps from `data/stinkyguide.sqlite`, then expands the catalogue to 97 games across shooters, RPGs, strategy, racing, sports, simulation, co-op, and more. It falls back to the bundled original sample games if the old database is missing. Seeding is safe to repeat and will not create duplicate sample content; run it again after updating the app to add new catalogue entries. The original database stays untouched; after the import, new accounts and community posts live in `database/database.sqlite`.

Back up `database/database.sqlite` to retain your player accounts and posts. Database and environment files are excluded from Git.

## Admin account and traffic monitoring

Set `ADMIN_EMAIL` (defaults to `admin@admin.lv`) and a unique `ADMIN_INITIAL_PASSWORD` of at least 16 characters in `.env` before running `php artisan db:seed`. The seeder creates that admin account on a new install. If the email already belongs to an account, seeding promotes it only when the secure initial password is configured, and does not change its existing password. Keep the password in `.env` or your deployment secret store; do not commit it.

Admins can open **Admin** in the navigation to review recent guides, reviews, and comments, delete inappropriate community posts, and see request totals, active signed-in accounts, popular route templates, and response statuses. Traffic monitoring stores the route template, HTTP method, status, optional signed-in user ID, and timestamps only. It does not collect IP addresses, full URLs/query strings, or request bodies. Traffic records are automatically pruned after 90 days by Laravel's daily scheduler; ensure `php artisan schedule:run` is invoked each minute in production, or run `php artisan traffic:prune` manually.

## What players can do

- Create accounts, log in, edit their player name and profile bio, and log out. Laravel hashes passwords and manages session cookies.
- Search games, reviews, and guides; browse the seeded game catalogue with real Steam store artwork and player-driven review scores.
- Choose up to five interests—including shooters, racing, sports, simulation, RPG, cozy, strategy, adventure, and indie—to get games ranked by matching tags; browse all matches or search the full catalogue.
- Add games to a backlog, mark them as playing, completed, or dropped, and manage them from **My games**.
- Write and filter game reviews, share playtime, and explore reviews on game details.
- Publish guides for any game, including ones outside the catalogue, in categories such as walkthroughs, builds, boss strategies, collectibles, achievements, and tips; comment, save guides for later, and vote on whether a guide helped.

Use `php artisan test` to run the Laravel feature tests.

Game cover art is served by Steam’s public artwork CDN, so viewing images requires an internet connection.
