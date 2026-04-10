<?php
/**
 * db.php - Database Connection
 * Establishes a PDO connection to MySQL.
 * Include this file in every page that needs DB access.
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Change to your MySQL username
define('DB_PASS', '');           // Change to your MySQL password
define('DB_NAME', 'attendance_db');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("<div style='font-family:sans-serif;padding:40px;color:#c0392b;background:#fdf2f2;border-left:4px solid #c0392b;'>
        <strong>Database Connection Failed:</strong> " . htmlspecialchars($e->getMessage()) . "<br><br>
        Please check your credentials in <code>db.php</code> and ensure MySQL is running.
    </div>");
}
