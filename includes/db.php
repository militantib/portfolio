<?php
$databaseUrl = getenv('DATABASE_URL') ?: 'postgresql://portfolio_db_mhf5_user:Vf886wzwPcbkHtLv7oErCmr7Ro75uHFY@dpg-d817nljeo5us7385q73g-a/portfolio_db_mhf5';

$db = parse_url($databaseUrl);
$host = $db['host'] ?? 'localhost';
$port = $db['port'] ?? '5432';
$dbname = ltrim($db['path'] ?? 'portfolio_db', '/');
$username = $db['user'] ?? 'root';
$password = $db['pass'] ?? '';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname;", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        subject VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS page_visits (
        id SERIAL PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT,
        page_url VARCHAR(500) NOT NULL,
        visit_date DATE DEFAULT CURRENT_DATE,
        visit_time TIME DEFAULT CURRENT_TIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_visits_date ON page_visits(visit_date)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_visits_ip ON page_visits(ip_address)");
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
