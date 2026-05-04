# Budget App

A small **Laravel** web app for tracking **income and expenses in GBP**, aimed at students (or anyone) who want a clear view of the **current month**: what has already happened, what is still scheduled, and where money goes by category.

I put it together as a **portfolio project** — auth, validation, Blade + Tailwind, and a few PHPUnit tests.

In the repo you will find the usual bits: CRUD, server-side validation, Blade layouts/components, a dashboard that rolls up user data, and feature tests for the main HTTP flows.

---

## Live demo

**Try it:** [https://budgetapp-main-4fnmmd.free.laravel.cloud](https://budgetapp-main-4fnmmd.free.laravel.cloud)  

You can poke around there, or run it locally with **Quick start** below if you prefer.

---

## How the numbers work

Quick notes if you are reading the code or clicking around:

- **Current balance** — Cash-style view **up to today**: income and expenses **on or before** today, so you see what should be “in hand” now.
- **Actual vs scheduled** — **Actual** is dated on or before today; **scheduled** is dated **later this month**, so upcoming bills and future pay still shape the monthly picture.
- **Projected end-of-month balance** — Combines what has happened with what is still scheduled **in the current calendar month**.
- **Insights** (e.g. daily budget, month-end outlook) — Derived from that same month window and the split between past and future-dated items.

All presentation amounts use a small **GBP** formatting helper and the `@money` Blade directive (see `app/Support/Money.php`).

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
| Backend    | PHP **8.4+**, **Laravel 13**     |
| Auth UI    | **Laravel Breeze** (Blade)     |
| Frontend   | **Tailwind CSS**, **Vite**     |
| JS         | **Alpine.js** (modals, mobile menu) |
| Database   | **SQLite** by default (see `.env.example`; MySQL/Postgres work too) |
| Tests      | **PHPUnit** (feature + unit)   |

---

## Requirements

- PHP **8.4+** with common extensions (`openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath` recommended)
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

> **Dev assets:** For hot-reload while you change CSS/JS, run `npm run dev` in a second terminal alongside `php artisan serve` (instead of or after `npm run build`).

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
