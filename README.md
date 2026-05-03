# Budget App

A small **Laravel** web app for tracking **income and expenses in GBP**, aimed at students (or anyone) who want a clear view of the **current month**: what has already happened, what is still scheduled, and where money goes by category.

I built this as a **portfolio piece** to show full-stack Laravel work: auth, validation, Blade UI, Tailwind, and automated tests—not a production SaaS.

---

## Highlights

- **Dashboard** — Current balance (cash basis to today), month stats (actual vs scheduled income/expenses), projected end-of-month balance, daily budget hint, month-end outlook, **spending breakdown** by category (includes upcoming bills), recent expenses, quick **add income / add expense** forms.
- **Records** — Tabular history with status (paid/upcoming), **edit** expenses, **delete** with confirmation dialogs for expenses and income.
- **Profile & security** — Laravel Breeze: profile details, password change, account deletion (with confirmation).
- **UX** — Shared layout across app and auth screens, responsive nav, flash messages, inline validation, GBP formatting via a small money helper and `@money` Blade directive.

---

## Tech stack

| Area        | Choice                          |
|------------|----------------------------------|
| Backend    | PHP **8.3+**, **Laravel 13**     |
| Auth UI    | **Laravel Breeze** (Blade)     |
| Frontend   | **Tailwind CSS**, **Vite**     |
| JS         | **Alpine.js** (modals, mobile menu) |
| Database   | **SQLite** by default (see `.env.example`; MySQL/Postgres work too) |
| Tests      | **PHPUnit** (feature + unit)   |

---

## Requirements

- PHP **8.3+** with common extensions (`openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath` recommended)
- [Composer](https://getcomposer.org/)
- [Node.js](https://nodejs.org/) **20+** (or current LTS) and npm

---

## Quick start (local)

```bash
git clone https://github.com/JackGaskell/BudgetApp.git
cd BudgetApp

composer install

cp .env.example .env
php artisan key:generate

# SQLite (default in .env.example)
touch database/database.sqlite
php artisan migrate

npm install
npm run build

php artisan serve
```

Open **http://127.0.0.1:8000** — register a user, then use **Dashboard** and **Records**.

> **Tip:** `composer run setup` runs install, `.env` copy (if missing), key generation, migrate, and `npm run build` (see `composer.json`). With **SQLite**, create the file first: `touch database/database.sqlite`, then run setup.

### Optional: MySQL / Postgres

Set `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env`, remove or ignore the SQLite file, then run `php artisan migrate`.

---

## Tests

```bash
php artisan test
```

The suite covers auth flows, expense categories and validation, expense updates, profile actions, and a few unit checks (e.g. money formatting).

---

## Project structure (skim)

| Path | Role |
|------|------|
| `app/Http/Controllers/` | Dashboard, records, income/expense CRUD, profile |
| `app/Models/` | `User`, `Income`, `Expense` |
| `app/Support/Money.php` | GBP display formatting |
| `resources/views/` | Blade layouts (`layouts/budget*`, `layouts/app`), dashboard, records, auth |
| `routes/web.php` | Authenticated routes + Breeze auth |
| `tests/Feature/` | HTTP / integration tests |

---

## Acknowledgements

Built with [Laravel](https://laravel.com) and [Laravel Breeze](https://laravel.com/docs/starter-kits#laravel-breeze).
