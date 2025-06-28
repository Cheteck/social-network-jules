<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
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

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

## Project Specific Information

This project is a social networking application built using Laravel and a modular package-based approach for its features.

### Core Features (Packages)

The application is composed of several key packages found under the `packages/ijideals/` directory:

*   **`social-posts`**: Manages creation and display of text-based posts.
*   **`followable`**: Implements a follow/unfollow system between users.
*   **`likeable`**: Allows users to like/unlike likeable models (e.g., posts).
*   **`commentable`**: Enables commenting on models, with support for nested replies.
*   **`media-uploader`**: Handles file uploads (images initially) and associates them with models (e.g., user avatars, post images).
*   **`news-feed-generator`**: Generates a personalized news feed for users, ranking content by relevance.
*   **`notification-system`**: Provides a database-backed notification system for user actions (likes, comments, follows).
*   **`user-profile`**: Basic user profile data management.
*   **`search-engine`**: Implements global search functionality across specified models (e.g., Users, Posts, Shops, Products) using Laravel Scout (defaulting to database driver).
*   **`shop-manager`**: Allows users to create and manage shop/business pages, with role-based member management (admins, editors) using `spatie/laravel-permission` (teams feature). Shops can have logos, covers, and publish their own posts.
*   **`catalog-manager`**: Enables shops to manage a catalog of products, including categories, pricing, stock, and images. Products are searchable.
*   **`hashtag-system`**: Allows associating hashtags with models (e.g., posts) and retrieving models by hashtag.

Refer to the `README.md` file within each package directory for specific details on that package.

### Internationalization (L10n) & Localization

The application is configured to support multiple languages for static text and API messages.

*   **Supported Locales:** Currently `en` (English) and `fr` (French). Default is `en`.
*   **Configuration:**
    *   Supported locales are defined in `config/app.php` under the `supported_locales` key.
    *   The default and fallback locales are also set in `config/app.php`.
*   **Locale Detection:**
    *   The active locale for a request is determined by the `App\Http\Middleware\SetLocale` middleware.
    *   It prioritizes locale from the session, then the `Accept-Language` HTTP header, and finally falls back to the default application locale.
*   **Package Translations:**
    *   Each custom package (`ijideals/*`) contains its own language files in its `resources/lang` directory.
    *   To customize these translations, publish the language files for the specific package using:
        ```bash
        php artisan vendor:publish --provider="Ijideals\[PackageName]\Providers\[PackageName]ServiceProvider" --tag="[package-name]-lang"
        ```
        (Replace `[PackageName]` and `[package-name]` accordingly, e.g., `Likeable` and `likeable`).
        This will copy the language files to `lang/vendor/[package-name]/{locale}/[package-name].php`.

### Running the Application

(Standard Laravel setup instructions would go here - e.g., clone, composer install, .env setup, key generate, migrate, serve)
For development using local packages, ensure `composer install` correctly links them from the `packages/` directory as defined in the main `composer.json` repositories section.

### Database Seeding

The application includes a comprehensive `DatabaseSeeder` that populates the database with a rich set of sample data across all implemented packages. This is useful for development and testing.

To seed your database after running migrations:
```bash
php artisan db:seed
```
This will:
- Create platform roles (superadmin, admin) and default shop roles (shop_owner, shop_admin, shop_editor).
- Create default product options (like Color, Size) and their values.
- Create sample users, including platform admins, shop owners, and regular users.
- Create sample shops, assigning owners and members with specific roles.
- Create sample product categories.
- Create sample products for shops, associating them with categories, options, and generating variants.
- Simulate social interactions like posts (by users and shops), likes, comments, and follows.
- Set some specific user settings for notification preferences.

Refer to `database/seeders/DatabaseSeeder.php` for the exact details of the seeded data. Model factories for all core models are also available in their respective packages or in the main application's `database/factories` directory.
