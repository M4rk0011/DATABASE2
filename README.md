# University of Baguio Concern Report System

PHP 8.1+ / PDO / MySQL or MariaDB / XAMPP. No Angular or Node build required.

## Run in XAMPP (manual database import)

1. Extract the `DATABASE2` folder into `C:\xampp\htdocs\DATABASE2`.
2. Start **Apache** and **MySQL** in XAMPP.
3. Open `http://localhost/phpmyadmin`, select **Import**, choose the project's `database.sql`, and click **Go**. The SQL creates `concern_report_system`, tables and demo accounts. Existing matching records are retained.
4. Open **http://localhost/DATABASE2/**. This opens the login page. There is no web setup wizard.
5. Log in with `admin` / `admin123`, `student` / `student123`, or `security` / `security123`. These are local demo credentials; replace them before using real reports.

Default connection: host `127.0.0.1`, database `concern_report_system`, username `root`, blank password. For a different MySQL port/password, copy `config.example.php` to `config.php` and edit it. If keeping a previous installation's config.php, check its database name. SQL import is still required.

Alternative manual import from **XAMPP Shell**:

```bat
mysql -u root < C:\xampp\htdocs\DATABASE2\database.sql
```

## Make typing just localhost open this application

Keep the project in the folder above. Back up your existing `C:\xampp\htdocs\index.php`, then copy **localhost/index.php** from this project to **C:\xampp\htdocs\index.php**. Only copy that single launcher file. Open **http://localhost/**. It forwards to the app login, or to the dashboard if already logged in.

This optional change replaces XAMPP's default homepage. Other project folders and `/phpmyadmin` still have their own URLs. If Apache uses port 8080, include `:8080` in localhost URLs.

## Photos

Create or edit a concern and select photos under **Photos**. Up to five JPG, PNG or WebP photos, 2 MB each, maximum 20 megapixels. A preview appears before submission. Existing photos can be removed during editing. Saved photos appear in report details and can be opened at full size.

Files are validated on the server, assigned random names, stored in `storage/uploads`, and served through an authenticated endpoint. Report owners and staff can edit attachments. Deleting a report also removes its photos. Back up the database **and** storage/uploads together.

In XAMPP's `php.ini`, enable `fileinfo` and set:

```ini
file_uploads=On
upload_max_filesize=2M
post_max_size=16M
max_file_uploads=20
```

Restart Apache after changing php.ini. New file selections must be selected again after a validation error. Never replace your existing storage folder while updating an installation.

## Campus map

Leaflet is bundled locally. Street, alternate street, and satellite tiles load directly over HTTPS in the browser, avoiding PHP cURL/SSL/allow_url_fopen issues in XAMPP. No API key is required. The map starts at University of Baguio using the existing project's reporting boundary.

Click within the red boundary or drag the marker to save a location. The campus map displays saved reports and opens their details when a marker is selected. **Center campus** resets the view.

Actual street/satellite backgrounds require internet access to the tile provider. If both street providers are unavailable, the interface clearly labels an offline coordinate grid; pins still work, but that grid is not a campus floor plan. Use **Retry online map** after reconnecting. The red rectangle is the application's reporting area, not a surveyed campus property line.

## Development and verification

`python tests/integration.py` runs HTTP regression tests with a temporary SQLite database. Production/XAMPP uses the manually imported MySQL database. Test attachments use a temporary upload directory.

`php -S localhost:8000 -t public public/router.php` starts the PHP development server after the database has been imported and configured.

Do not expose app/, bin/, storage/, tests/, database.sql or config.php through a web server. XAMPP/Apache access rules are included; for other servers, use public/ as the document root.

## Map update (v5)

Street view now starts with Esri (the same host as satellite), followed by OpenStreetMap and CARTO if a provider fails. Alternate view tries CARTO, OpenStreetMap, then Esri. If all street sources fail, satellite is shown with an explicit notice. If no provider responds, a labeled coordinate grid remains available. Partial tile failures and stalled tile requests now trigger recovery, including after zooming or panning. Esri street tiles use native zoom 18 and scale at zoom 19.

**Existing installation:** back up then replace only `public/app.js` and `app/layout.php` with the copies in this archive. Press Ctrl+F5 in the browser. No SQL import or database change is needed for this map update. Keep your existing config.php and storage/uploads.

## Walkable team studio

The login/index page includes a 3D team studio. It is not loaded on the dashboard or other portal pages. Click **Enter the studio**, use WASD to walk, drag to look, and approach a character to read their contribution. Mobile visitors can hold the on-screen movement buttons. Escape or **Pause / Exit** releases the game controls.

The studio uses local WebGL geometry with rounded avatars, lighting, floor shadows, and a furnished interior. Select **Performance** if **High** graphics feels slow. If WebGL is unavailable, the text team credits remain accessible. No database changes or external assets are required.

Developer checks: `node tests/crew-game.cjs` exercises movement and lifecycle behavior; `python tests/crew-shaders.py` optionally compiles and links the lighting shaders on Linux with Mesa EGL.
