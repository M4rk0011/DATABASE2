# Verification of this revision

- PHP 8.3 syntax checks passed for all PHP source files.
- JavaScript syntax check passed.
- 41 HTTP regression checks passed using temporary SQLite storage: authentication, registration, CRUD, roles, CSRF, photo upload/serving, type and size rejection, attachment count, non-owner rejection, file cleanup and deletion.
- database.sql was accepted by MariaDB 10.11 bootstrap. A persistent MariaDB/PDO connection could not be tested because this execution environment does not permit Unix sockets.
- Browser verification was attempted but the browser runtime download was unavailable. Live map tiles and visual rendering must be checked on the target XAMPP computer.

Map implementation uses local Leaflet assets, direct HTTPS tile URLs, fixed map dimensions, a provider fallback and an explicitly labeled offline grid. Online street/satellite imagery requires internet.

The source supports local XAMPP installation; see README.md for manual SQL import and the optional localhost launcher.

## Map v5 regression results

- Actual campus tile requests returned HTTP 200 and image bytes from Esri street, OpenStreetMap, CARTO, and Esri satellite at zoom 18 in this environment. These checks do not establish connectivity from the user's PC.
- 10 Node regression tests passed for complete/partial tile batches, stalled tiles, fallback order, zoom/pan failures, stale events, retry, and offline state. Run `node tests/map-background.cjs`.
- Browser rendering remains unverified due to the environment's browser process restrictions.
