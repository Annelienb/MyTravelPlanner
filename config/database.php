<?php
$configuredHost = getenv('DB_HOST');
$socket = getenv('DB_SOCKET');
$hosts = $socket
    ? [null]
    : array_values(array_unique(array_filter([
        $configuredHost ?: null,
        'localhost',
    ])));
$port = getenv('DB_PORT') ?: '3306';
$db = getenv('DB_NAME') ?: 'u250848_travelly';
$user = getenv('DB_USER');
$password = getenv('DB_PASS');

if (!$user || !$password) {
    throw new PDOException('Set DB_USER and DB_PASS in the server environment.');
}

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_TIMEOUT => 3,
];

$pdo = null;
$lastError = null;
foreach ($hosts as $host) {
    try {
        $dsn = $socket
            ? "mysql:unix_socket={$socket};dbname={$db};charset=utf8mb4"
            : "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $password, $options);
        break;
    } catch (PDOException $e) {
        $lastError = $e;
    }
}

if (!$pdo) {
    throw $lastError ?: new PDOException('Database connection failed.');
}
