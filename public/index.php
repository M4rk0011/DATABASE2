<?php
try {
 require __DIR__.'/../app/controller.php';
} catch (PDOException $e) {
 http_response_code(503);
 echo '<!doctype html><html><head><meta charset="utf-8"><title>Concern Report System</title><link rel="stylesheet" href="css/styles.css"><link rel="stylesheet" href="css/enhancements.css"></head><body><main class="setup-card"><h1>Concern Report System</h1><p>The database is not ready. Start MySQL in XAMPP, then import database.sql in phpMyAdmin (Import → Choose file → Go). If you use a custom MySQL port/password, update config.php.</p><p>Default database: <strong>concern_report_system</strong>. After importing, reload this page.</p></main></body></html>';
}
