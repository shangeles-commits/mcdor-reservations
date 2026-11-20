# Team workflow

## Suggested division

- **Registration system:** `registration/` (signup, login, logout).
- **Reservation/time and date system:** `reservation/` (choose slot, lookup, reschedule, cancel).
- **Menu:** `menu/` (package and extra selection).
- **Review/payment:** `review-payment/` (review, simulated checkout, success receipt).
- **Admin view and CRUD:** `admin/` (reservation search/actions and package/table CRUD). The request listed admin CRUD twice; these belong together in one admin folder to avoid duplicate implementations.
- **Front end:** `frontend/css/` and `frontend/layout/` (shared stylesheet, header/footer, and receipt view).
- **Database and shared functions:** `database/`, `includes/db.php`, `includes/functions.php`, and `includes/bootstrap.php`.
- **Integration/maintenance:** root `index.php`, `scripts/`, `README.md`, and this document.

Expected project layout:

```text
registration/       signup.php, login.php, logout.php
reservation/        booking.php, my_reservation.php, edit_reservation.php, cancel_reservation.php
menu/               menu.php
review-payment/     review.php, checkout.php, success.php
admin/              reservation list, package CRUD, table CRUD
frontend/
  css/              style.css
  layout/           header.php, footer.php, receipt.php
includes/           db.php, bootstrap.php, functions.php
database/           setup.sql, seed.sql
```

Coordinate before editing shared files: `includes/functions.php`, `includes/db.php`, `includes/bootstrap.php`, `frontend/layout/`, `database/setup.sql`, and `config.example.php`. Page routes live in their feature folder; avoid editing another feature folder unless you coordinate its owner. Agree on fields and status names before changing them.

## Branch and pull request workflow

1. Pull the latest `main` and create a feature branch for one coherent task.
2. Keep commits focused and run the relevant manual checklist below.
3. Open a pull request against `main`, complete `.github/pull_request_template.md`, and ask a teammate to review it.
4. Resolve review feedback and merge only after the integration checklist passes. For cross-feature route or shared-layout changes, coordinate with the affected feature owners first.

Do not edit `database/setup.sql` to quietly change an already-used schema. Add a new numbered migration such as `database/migrations/001_add_example_field.sql`, document its order and effect, and coordinate before applying it.

## Shared function contracts

- `db(): PDO` uses ignored root `config.local.php` when present, otherwise the shared `config.example.php` defaults. It checks `pdo_mysql`, creates the database if needed, and initializes tables and sample catalog on first use. It returns a shared exception-mode PDO connection and throws a helpful `RuntimeException` if MySQL is unavailable or setup fails.
- `checkAvailability(PDO $connection, string $date, string $time, int $guestCount, ?int $excludeReservationId = null): ?array` accepts a Manila `YYYY-MM-DD` date, one of `11:00`, `13:00`, `15:00`, `17:00`, or `19:00`, and 1–8 guests. It returns the smallest suitable free active table (`id`, `table_number`, `capacity`) or `null`. The optional ID is used for rescheduling; the database unique constraint remains the final concurrency guard.
- `calculateTotal(PDO $connection, int $packageId, array $extraIds, int $guestCount): array` validates active package/extra IDs from the database, and returns `package`, `extras`, and `total`. `total` is `(package price + extras per-guest prices) × guests`, rounded to two decimals. It throws `InvalidArgumentException` for invalid/inactive selections.

## Shared fields and status values

- Sign-up: `name`, `phone`, `password`. Public registration always creates a `customer`.
- Booking draft: `date`, `time`, `guests`, `package_id`, `extra_ids`.
- Payment methods: `pay_at_restaurant`, `demo_simulated`. Payment statuses: `unpaid`, `simulated_paid`.
- Reservation statuses: `active`, `cancelled`, `no_show`. Only active reservations occupy slots.

## Manual feature checklist

- **Accounts:** register, reject duplicate phone, log in with the phone/password, log out, and verify a customer cannot open admin pages.
- **Booking:** test date limits, each fixed slot, guest counts 1–8, smallest suitable table, package and extra selection, and a slot with no suitable table.
- **Reservations:** confirm receipt ID and snapshots; look up an owned ID; try another user's ID and confirm no details appear; reschedule, reject a conflict without losing the old slot, then cancel and reuse the released slot.
- **Admin:** search by ID and phone, update payment status, mark no-show and confirm the slot is released, edit/deactivate a package and a table, and confirm neither is physically deleted.
- **Integration:** complete signup → login → date/time/guests → package/extras → review → simulated or pay-at-restaurant confirmation → success receipt → lookup → reschedule/cancel.
