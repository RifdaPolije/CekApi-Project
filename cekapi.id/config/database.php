
<?php

require_once __DIR__ . '/app.php';

const DB_HOST = '127.0.0.1';
const DB_NAME = 'cekapi';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {

        $dsn = 'mysql:host=' . DB_HOST .
               ';port=3306' .
               ';dbname=' . DB_NAME .
               ';charset=utf8mb4';

        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND =>
                "SET time_zone = '" . DB_TZ_OFFSET . "'",
        ]);
    }

    return $pdo;
}
