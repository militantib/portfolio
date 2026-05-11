<?php
$databaseUrl = getenv('DATABASE_URL');

if ($databaseUrl) {
    $db = parse_url($databaseUrl);
    $host = $db['host'] ?? 'localhost';
    $port = $db['port'] ?? '5432';
    $dbname = ltrim($db['path'] ?? 'portfolio_db', '/');
    $username = $db['user'] ?? 'root';
    $password = $db['pass'] ?? '';
} else {
    $host = 'localhost';
    $port = '5432';
    $dbname = 'portfolio_db';
    $username = 'root';
    $password = 'Ib522022024';
}

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname;", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
