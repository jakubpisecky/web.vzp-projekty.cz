<?php
// /public/includes/db.php – frontend (READ-ONLY)
// Uprav heslo níže a měj v MySQL uživatele s právy pouze SELECT:
//   CREATE USER 'web_ro'@'localhost' IDENTIFIED BY 'silne-heslo';
//   GRANT SELECT ON cms.* TO 'web_ro'@'localhost';
//   FLUSH PRIVILEGES;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

define('DB_HOST', 'uvds59.active24.cz');   // stejný server
define('DB_NAME', 'cmsvzpproj');         // tvoje DB
define('DB_USER', 'cmsvzpproj');      // RO uživatel
define('DB_PASS', 'U6BDK4VM'); // ← změň

/**
 * Vrátí singleton mysqli připojení.
 */
function db(): mysqli {
    static $conn = null;
    if ($conn instanceof mysqli) return $conn;

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');

    // Volitelné: pokud máš nahrané timezone tabulky v MySQL:
    // $conn->query("SET time_zone = 'Europe/Prague'");

    return $conn;
}

// Pro kompatibilitu s tvým stylem použití ($conn) rovnou vytvořím globál:
$conn = db();
