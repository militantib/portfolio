<?php
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

$skipPaths = ['admin.php', '/admin', '/includes/', '/assets/'];
$shouldSkip = false;
foreach ($skipPaths as $path) {
    if (strpos($requestUri, $path) !== false || strpos($scriptName, $path) !== false) {
        $shouldSkip = true;
        break;
    }
}

if (!$shouldSkip) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $pageUrl = parse_url($requestUri, PHP_URL_PATH) ?: '/';

    try {
        $stmt = $pdo->prepare("INSERT INTO page_visits (ip_address, user_agent, page_url) VALUES (?, ?, ?)");
        $stmt->execute([$ip, $ua, $pageUrl]);
    } catch (Exception $e) {
        // Silently fail
    }
}
