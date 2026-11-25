# mcdor-reservations

**WEB APPLICATION - FINALPROJECT**

## McDor Restaurant Reservation Prototype

A lightweight PHP 8 and MySQL/MariaDB prototype for XAMPP. It uses plain PHP pages, PDO_MYSQL, and simple HTML/CSS. Demo payment is only a status label: this app does not collect card data or move money.

## Requirements

- PHP 8.0+ with `pdo_mysql` enabled.
- XAMPP Apache and MySQL (MariaDB is also supported).
- A browser; phpMyAdmin is included with XAMPP for inspecting the database.

In XAMPP Control Panel, start Apache and MySQL. Check that the `pdo_mysql` extension is enabled in the `php.ini` used by Apache (the usual XAMPP location is `C:\xampp\php\php.ini`), then restart Apache. The default XAMPP MySQL account is often `root` with an empty password.

## Fresh clone setup

1. Start Apache and MySQL in the XAMPP Control Panel.
2. Visit the site and open **Sign up**. On the first database-backed page, the app automatically creates the `mcdor_reservations` database, its tables, and the example catalog. No database import or manual table creation is needed.
3. If your MySQL username or password differs from XAMPP's common `root`/blank-password default, copy `config.example.php` to `config.local.php` and change the settings. `config.local.php` is ignored by Git.
4. To confirm the sample data, open phpMyAdmin at `http://localhost/phpmyadmin/`, select `mcdor_reservations`, and run:

   ```sql
   SHOW TABLES;
   SELECT COUNT(*) AS PACKAGE_COUNT FROM menu_packages;
   SELECT COUNT(*) AS TABLE_COUNT FROM dining_tables;
   SELECT COUNT(*) AS EXTRA_COUNT FROM extras;
   ```

The shared PDO connection uses these settings from `config.example.php`:

```php
'dsn' => 'mysql:host=127.0.0.1;port=3306;charset=utf8mb4',
'database' => 'mcdor_reservations',
```

If there is no `config.local.php`, the app uses the example settings. MySQL must be running, and the configured MySQL account needs permission to create a database and tables. The SQL files are also kept in `database/` for review or manual setup.

## Run locally

Place the project under `C:\xampp\htdocs` and open its local URL, for example `http://localhost/php/final/`. You can also use PHP's built-in development server from the project directory:

```powershell
php -S localhost:8000
```

Open `http://localhost:8000/`. Do not expose this prototype or PHP's development server to the public internet.

## Admin access (local only)

Admin credentials are intentionally not included in this repository. Each teammate should create an account on their own local database, then promote that account. Start MySQL, sign up in the website with the phone number you want to use, and run this command from the project directory:

```powershell
php scripts/promote_admin.php YOUR_REGISTERED_PHONE
```

Replace `YOUR_REGISTERED_PHONE` with the phone number used during signup. The script runs only in the terminal, promotes an existing account, and does not create public admin registration. Sign out and back in to load the admin role. Do not commit or share local account passwords.

## Main folders and files

- `config.example.php`: shared XAMPP defaults; copy to ignored `config.local.php` only when local credentials differ.
- `database/setup.sql`: MySQL table definitions, keys, constraints, and indexes, run automatically on first use.
- `database/seed.sql`: example tables, sides, and all 11 editable packages, inserted automatically when the catalog is empty.
- `includes/db.php`: shared PDO_MYSQL connection and first-use database initialization.
- `includes/functions.php`: escaping, sessions/auth guards, CSRF, date/time rules, availability, pricing, and owned-receipt lookup.
- `registration/`: signup, login, and logout pages.
- `reservation/`: date/time selection and customer reservation lookup, reschedule, and cancellation.
- `menu/`: package and optional-side selection.
- `review-payment/`: review, simulated checkout, and success receipt.
- `admin/`: role-protected reservation view plus package and table CRUD.
- `frontend/css/` and `frontend/layout/`: shared styles, header, footer, and receipt.
- `scripts/promote_admin.php`: one-time local CLI role promotion.
- `docs/TEAM_WORKFLOW.md`: teammate work split, shared contracts, statuses, and checklists.

## Database and booking behavior

Use the shared-function contracts in [docs/TEAM_WORKFLOW.md](docs/TEAM_WORKFLOW.md). Dates use `Asia/Manila`; slots are 11:00, 13:00, 15:00, 17:00, and 19:00, at least two hours ahead and no more than 30 days out. There are 1–8 guests and the smallest available active table that fits is assigned. Final checkout recalculates current prices and checks availability again in an InnoDB transaction. A unique constraint prevents concurrent bookings for the same table/date/time. Reservation and extra price snapshots retain historical receipt amounts if menu prices change.

Only active bookings have an `occupied_slots` row. Reschedule, cancellation, and no-show actions update the status and occupied slot transactionally. Customers can see only reservations owned by their account.

## Manual test checklist

- [ ] Sign up, reject duplicate phone, log in, and log out.
- [ ] Complete a booking with a valid future slot, guests, package, and optional extras.
- [ ] Attempt to book the same table/date/time twice; the second confirmation must be rejected with a choose-another-slot message.
- [ ] Verify assigned table and calculated receipt total; choose both payment dropdown options and confirm no card data is requested.
- [ ] Select a reservation from the account's reservation dropdown and verify its ID, date/time, payment method, and statuses; confirm another customer's reservations are not listed.
- [ ] Reschedule to a free slot, then test a full slot and confirm the original booking remains intact.
- [ ] Cancel a booking and verify its slot becomes available; test the admin no-show action too.
- [ ] Promote a local account, verify admin-only access, search by ID and phone, update payment status, edit/deactivate a package and table.
- [ ] Run the complete signup-to-booking integration checklist in `docs/TEAM_WORKFLOW.md`.

Future database changes should use new numbered SQL migrations instead of silently editing a schema already used by teammates.
