<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/your-org/your-repo/actions/workflows/ci.yml"><img src="https://github.com/your-org/your-repo/actions/workflows/ci.yml/badge.svg" alt="CI"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Police API - Installation & Deployment

Pré-requis:
- PHP 8.1+
- Composer
- Node.js (pour frontend)
- MySQL ou SQLite

Installation locale:

```bash
composer install
cp .env.example .env
# ajuster DB_* et MAIL_* et BROADCAST_DRIVER, QUEUE_CONNECTION
php artisan key:generate
php artisan migrate
php artisan storage:link
```

Queue (recommandé):

- Pour production, utilisez `redis` ou `database` pour `QUEUE_CONNECTION`.
- Exemples:

```bash
php artisan queue:table
php artisan migrate
php artisan queue:work --sleep=3 --tries=3
```

Broadcast:

- Configurez `BROADCAST_DRIVER` à `pusher` ou `redis` et renseignez les variables PUSHER_*.
- Exemple d'utilisation avec Pusher et Laravel Echo côté client (voir `docs/queue_broadcast_setup.md`).

Environment variables importants:
- `QUEUE_CONNECTION` (sync | database | redis)
- `BROADCAST_DRIVER` (log | pusher | redis)
- `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_ID`, `PUSHER_APP_CLUSTER`
- `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`

Tester:

```bash
# exécuter la suite de tests
php artisan test
```

Frontend snippet (React):
- Voir `resources/js/components/Notifications.jsx` pour un exemple d'écoute en temps réel via Echo.

OpenAPI & Postman:
- Spécification minimal: `docs/openapi.yaml` et `docs/openapi.json`
- Collection Postman: `docs/postman_collection.json`

Notes:
- Les notifications sont stockées dans la table `notifications` (schéma user_id, type, data, read_at).
- Les événements en temps réel utilisent le canal privé `users.{id}`.
