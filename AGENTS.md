# OpenCode Agent Instructions

This repository is an API-focused Laravel 12 application (Pharmacy domain). Follow these architectural patterns and commands when working here.

## 🏗 Architecture & Patterns

- **Controller -> Service -> Repository Pattern:**
  - **Controllers:** `App\Http\Controllers\Api\*`. Handle routing, validation (using FormRequests), and HTTP responses. Do not put business logic here.
  - **Services:** `App\Services\*`. Handle business logic, database transactions (`DB::beginTransaction()`, `DB::commit()`, `DB::rollBack()`), and file manipulation (e.g., using `saveImage()` global helper).
  - **Repositories:** `App\Repositories\*`. Handle all Eloquent interactions. 
- **Soft Deletes Convention:** Instead of Laravel's built-in `SoftDeletes` trait, deletes are currently handled manually in Repositories by setting `'active' => 0` and updating `'user_updated' => Auth::id()`.
- **API Responses:** ALWAYS use `App\Helpers\ResponseHelper::success()` and `ResponseHelper::error()` for consistent JSON structures. Do not return standard `response()->json()` from controllers.
- **Tracking Users:** Populate `user_created` and `user_updated` fields manually in controllers/services using `Auth::id()` before database inserts/updates.
- **Combos:** Many entities have a `-combo` route (e.g., `api/v1/categories-combo`) that returns a simplified `label`/`value` structure for frontend dropdowns.

## 🛠 Commands & Execution

- **Development Server:** Run `composer dev` (this concurrently starts `php artisan serve`, `queue:listen`, `pail` for logs, and `npm run dev` for Vite).
- **Docker Environment:** The project is configured for Laravel Sail with PHP 8.4 and PostgreSQL 17. Use `./vendor/bin/sail up -d` if working with the containerized setup.
- **Testing:** Run `composer test` (clears config and runs `php artisan test`).
- **Code Generation (IDE Helper):** `barryvdh/laravel-ide-helper` is installed. After modifying migrations/models, it is a good practice to run `php artisan ide-helper:models` (or let the user do it) to update docblocks.

## 📌 Technical Stack specifics
- PHP 8.4
- Laravel 12.0
- Laravel Sanctum (Authentication)
- Vite 6 + Tailwind CSS 4
