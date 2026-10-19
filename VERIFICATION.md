# Verification — Concern Report System, XAMPP revision

## Environment

PHP 8.3, SQLite and MariaDB 10.11.14; Chromium browser automation. The MariaDB tests used real PDO MySQL connections to a dedicated local database, not a database mock. Windows XAMPP itself was not available in this environment; the app uses compatible PHP/PDO/MySQL and Apache-relative URLs.

## Passed

- All application/vendor PHP files passed PHP syntax checks; application JavaScript passed syntax validation.
- Full HTTP integration suite passed on SQLite and on MariaDB: authentication gate, registration, duplicate rejection, persistent accounts, page rendering, report creation, HTML escaping, search/status filtering, campus-boundary validation, owner/staff permissions, CSRF rejection, report editing, status updates, staff assignment, administrator user edits/deletion, administrator protection, anonymous reporting, report deletion and logout/session handling.
- Included SQL file imported successfully into MariaDB and produced six demo accounts and three original sample reports.
- Browser installer successfully initialized the MySQL database without a terminal.
- Chromium verified that the dashboard, list, report, map and details screens contain no old “Incident” labels.
- PHP-generated PDF download verified as a genuine PDF document.
- Same-origin PHP tile endpoint successfully retrieved actual online street-map imagery; a real image tile rendered in Chromium. Concern pins opened their corresponding reports.
- Chromium also passed offline pin selection, automatic offline fallback with tile requests blocked, administrator report creation and Add User; no JavaScript errors occurred.
- Original four media assets remain byte-identical to the first conversion. No Angular/TypeScript runtime or build files are included.

## Operating limits

- Live map imagery still requires internet access from the XAMPP machine. The alternate provider and labeled offline coordinate grid handle blocked/unavailable imagery. The grid is not a substitute for aerial imagery or a surveyed campus floor plan.
- Share opens an email draft and requires a reachable server URL for recipients; no messages are sent automatically.
- Existing SQLite records are retained on disk but are not automatically migrated to MySQL.
- Legacy art-commission pages keep the original demonstration content and are separate from concern reports.
- No GitHub push or public deployment was performed.

## October 19 interface redesign
- PHP syntax: 21 files passed; app.js syntax passed.
- Existing HTTP integration suite passed with PHP 8.3 / SQLite, including login, registration, roles, CSRF, report CRUD and account management.
- Chromium checked login, dashboard, reports, report form, map and registration at desktop and 390px mobile width: no horizontal overflow or JavaScript page errors.
- Removed video markup and all MP4 assets. Login uses the existing University of Baguio photo.
- This redesign did not rerun Windows XAMPP or MySQL testing; backend configuration and SQL schema are unchanged from the previous package.
