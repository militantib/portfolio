<?php
session_start();
require_once __DIR__ . '/includes/db.php';

$password = 'Ib522022024';

$error = '';
$loggedIn = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === $password) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = 'Wrong password';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

$loggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

$messages = [];
$totalMessages = 0;

if ($loggedIn) {
    $stmt = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC");
    $messages = $stmt->fetchAll();
    $totalMessages = count($messages);

    // Visit stats
    $totalVisits = $pdo->query("SELECT COUNT(*) FROM page_visits")->fetchColumn();
    $todayVisits = $pdo->query("SELECT COUNT(*) FROM page_visits WHERE visit_date = CURRENT_DATE")->fetchColumn();
    $weekVisits = $pdo->query("SELECT COUNT(*) FROM page_visits WHERE visit_date >= CURRENT_DATE - INTERVAL '7 days'")->fetchColumn();
    $uniqueVisitors = $pdo->query("SELECT COUNT(DISTINCT ip_address) FROM page_visits")->fetchColumn();

    // Daily visits for charts (last 30 days)
    $dailyStmt = $pdo->query("
        SELECT visit_date, COUNT(*) as count
        FROM page_visits
        WHERE visit_date >= CURRENT_DATE - INTERVAL '30 days'
        GROUP BY visit_date
        ORDER BY visit_date
    ");
    $dailyData = $dailyStmt->fetchAll();

    // Build date range for chart (fill missing days with 0)
    $chartDates = [];
    $chartCounts = [];
    $dataMap = [];
    foreach ($dailyData as $d) {
        $dataMap[$d['visit_date']] = $d['count'];
    }
    for ($i = 29; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $chartDates[] = date('M d', strtotime($date));
        $chartCounts[] = (int)($dataMap[$date] ?? 0);
    }

    // Page breakdown
    $pageStmt = $pdo->query("
        SELECT page_url, COUNT(*) as count
        FROM page_visits
        GROUP BY page_url
        ORDER BY count DESC
        LIMIT 6
    ");
    $pageData = $pageStmt->fetchAll();
    $pageLabels = [];
    $pageCounts = [];
    $pageColors = ['#6c63ff', '#00d4aa', '#764ba2', '#ff6b6b', '#ffa94d', '#4ecdc4'];
    foreach ($pageData as $p) {
        $label = $p['page_url'] === '/' ? 'Home' : trim($p['page_url'], '/');
        $pageLabels[] = ucfirst($label);
        $pageCounts[] = (int)$p['count'];
    }

    // Recent visits
    $recentStmt = $pdo->query("
        SELECT ip_address, page_url, visit_date, visit_time
        FROM page_visits
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $recentVisits = $recentStmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Ibrahim Senesie</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0a0a23; color: #e8e8f0; display: flex; min-height: 100vh; }
        a { text-decoration: none; color: inherit; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: rgba(255,255,255,0.02); }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }

        /* ===== Overlay ===== */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 99;
        }
        .sidebar-overlay.show { display: block; }

        /* ===== Hamburger ===== */
        .hamburger-menu {
            display: none;
            background: none;
            border: none;
            color: #e8e8f0;
            font-size: 1.4rem;
            cursor: pointer;
            padding: 8px;
        }

        /* ===== Sidebar ===== */
        .sidebar {
            width: 260px;
            background: rgba(255,255,255,0.03);
            border-right: 1px solid rgba(255,255,255,0.06);
            padding: 24px;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 100;
            transition: transform 0.3s ease;
        }
        .sidebar-brand {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 36px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar-brand i { -webkit-text-fill-color: #667eea; font-size: 1.3rem; }
        .sidebar-nav { flex: 1; display: flex; flex-direction: column; gap: 6px; }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 500;
            color: #a0a0b8;
            transition: all 0.3s;
        }
        .sidebar-nav a:hover {
            background: rgba(108,99,255,0.1);
            color: #e8e8f0;
        }
        .sidebar-nav a.active {
            background: linear-gradient(135deg, rgba(108,99,255,0.15), rgba(0,212,170,0.1));
            color: #fff;
            border: 1px solid rgba(108,99,255,0.2);
        }
        .sidebar-nav a i { width: 20px; font-size: 1rem; }
        .sidebar-nav .badge-count {
            margin-left: auto;
            background: #6c63ff;
            color: #fff;
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 50px;
            font-weight: 600;
        }
        .sidebar-user {
            padding: 16px;
            border-radius: 12px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.06);
            margin-top: auto;
        }
        .sidebar-user .user-info { display: flex; align-items: center; gap: 12px; }
        .sidebar-user .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.9rem; color: #fff;
        }
        .sidebar-user .name { font-size: 0.85rem; font-weight: 600; }
        .sidebar-user .role { font-size: 0.75rem; color: #6c6c8a; }

        /* ===== Main ===== */
        .main {
            margin-left: 260px;
            flex: 1;
            padding: 24px 32px 32px;
            min-width: 0;
        }

        /* ===== Topbar ===== */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            gap: 16px;
        }
        .topbar-left { display: flex; align-items: center; gap: 16px; }
        .topbar h1 { font-size: 1.4rem; font-weight: 700; }
        .topbar h1 span { color: #6c6c8a; font-weight: 400; font-size: 0.8rem; display: block; }
        .topbar-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
        .topbar-actions a {
            padding: 10px 18px;
            border-radius: 10px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.06);
            color: #a0a0b8;
            font-size: 0.82rem;
            transition: all 0.3s;
            white-space: nowrap;
        }
        .topbar-actions a:hover {
            background: rgba(255,71,87,0.1);
            border-color: rgba(255,71,87,0.2);
            color: #ff4757;
        }
        .topbar-actions .visit-btn {
            background: linear-gradient(135deg, #6c63ff, #00d4aa);
            color: #fff;
            border: none;
        }
        .topbar-actions .visit-btn:hover { opacity: 0.9; color: #fff; }

        /* ===== Stats Cards ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 16px;
            padding: 20px;
            transition: all 0.4s;
            opacity: 0;
            transform: translateY(20px);
        }
        .stat-card.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .stat-card:hover { transform: translateY(-3px); border-color: rgba(108,99,255,0.25); box-shadow: 0 8px 30px rgba(108,99,255,0.08); }
        .stat-card .stat-head { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; }
        .stat-card .stat-icon {
            width: 42px; height: 42px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
        }
        .stat-card .stat-icon.blue { background: rgba(108,99,255,0.15); color: #6c63ff; }
        .stat-card .stat-icon.green { background: rgba(0,212,170,0.15); color: #00d4aa; }
        .stat-card .stat-icon.purple { background: rgba(118,75,162,0.15); color: #764ba2; }
        .stat-card .stat-icon.orange { background: rgba(255,169,77,0.15); color: #ffa94d; }
        .stat-card .stat-number {
            font-size: 1.8rem; font-weight: 800; margin-bottom: 2px;
            font-variant-numeric: tabular-nums;
        }
        .stat-card .stat-label { font-size: 0.8rem; color: #6c6c8a; }
        .stat-card .stat-change {
            font-size: 0.7rem; padding: 2px 8px; border-radius: 50px;
            font-weight: 600;
        }
        .stat-card .stat-change.up { background: rgba(0,212,170,0.12); color: #00d4aa; }
        .stat-card .stat-change.down { background: rgba(255,71,87,0.12); color: #ff4757; }

        /* ===== Charts Row ===== */
        .charts-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 28px;
        }
        .chart-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 16px;
            padding: 20px;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s;
        }
        .chart-card.visible { opacity: 1; transform: translateY(0); }
        .chart-card .chart-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 16px;
        }
        .chart-card .chart-header h3 { font-size: 0.95rem; font-weight: 600; }
        .chart-card .chart-header span { font-size: 0.75rem; color: #6c6c8a; }
        .chart-card canvas { max-height: 220px; max-width: 100%; }

        /* ===== Tables ===== */
        .table-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 20px;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s;
        }
        .table-card.visible { opacity: 1; transform: translateY(0); }
        .table-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 18px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            flex-wrap: wrap; gap: 8px;
        }
        .table-header h2 { font-size: 1rem; font-weight: 600; }
        .table-header .count { font-size: 0.82rem; color: #6c6c8a; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th {
            text-align: left;
            padding: 12px 20px;
            font-size: 0.72rem;
            font-weight: 600;
            color: #6c6c8a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: rgba(255,255,255,0.02);
            border-bottom: 1px solid rgba(255,255,255,0.06);
            white-space: nowrap;
        }
        td {
            padding: 14px 20px;
            font-size: 0.85rem;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            color: #c8c8d8;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(108,99,255,0.04); }
        .td-name { font-weight: 600; color: #e8e8f0; }
        .td-email { color: #6c63ff; font-size: 0.82rem; }
        .td-subject {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 50px;
            font-size: 0.73rem;
            background: rgba(108,99,255,0.1);
            color: #8b85ff;
            font-weight: 500;
        }
        .td-message {
            max-width: 240px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #6c6c8a;
            font-size: 0.82rem;
        }
        .td-date { font-size: 0.8rem; color: #6c6c8a; white-space: nowrap; }
        .td-ip { font-family: monospace; font-size: 0.8rem; color: #6c6c8a; }
        .td-page { font-size: 0.82rem; color: #8b85ff; }
        .td-time { font-size: 0.78rem; color: #6c6c8a; }

        /* ===== Tab Buttons ===== */
        .tab-bar {
            display: flex; gap: 8px; padding: 0 24px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .tab-bar button {
            padding: 8px 18px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.06);
            background: transparent;
            color: #6c6c8a;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.3s;
        }
        .tab-bar button.active {
            background: rgba(108,99,255,0.12);
            border-color: rgba(108,99,255,0.2);
            color: #8b85ff;
        }
        .tab-bar button:hover { color: #e8e8f0; }

        /* ===== Login ===== */
        .login-page {
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; width: 100%;
        }
        .login-box {
            max-width: 400px; width: 100%;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 20px;
            padding: 40px;
        }
        .login-box .brand {
            font-size: 1.8rem; font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 4px;
        }
        .login-box .subtitle { color: #6c6c8a; font-size: 0.85rem; margin-bottom: 28px; }
        .login-box h2 { font-size: 1.2rem; margin-bottom: 6px; }
        .login-box .input-group { margin-bottom: 18px; }
        .login-box .input-group label {
            display: block;
            font-size: 0.82rem;
            color: #a0a0b8;
            margin-bottom: 6px;
            font-weight: 500;
        }
        .login-box .input-group input {
            width: 100%;
            padding: 13px 16px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            color: #fff;
            font-size: 0.9rem;
            font-family: inherit;
            outline: none;
            transition: all 0.3s;
        }
        .login-box .input-group input:focus {
            border-color: #6c63ff;
            box-shadow: 0 0 0 3px rgba(108,99,255,0.1);
        }
        .login-btn {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #6c63ff, #00d4aa);
            color: #fff;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.3s;
        }
        .login-btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .error-msg {
            background: rgba(255,71,87,0.1);
            border: 1px solid rgba(255,71,87,0.2);
            color: #ff4757;
            padding: 11px 16px;
            border-radius: 10px;
            font-size: 0.82rem;
            margin-bottom: 18px;
        }

        /* ===== Empty ===== */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state i {
            font-size: 3.5rem;
            color: rgba(108,99,255,0.2);
            margin-bottom: 18px;
        }
        .empty-state h3 { font-size: 1.1rem; margin-bottom: 6px; }
        .empty-state p { color: #6c6c8a; font-size: 0.85rem; }

        /* ===== Animations ===== */
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        @keyframes countUp {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }
        .stat-card:nth-child(1) { transition-delay: 0s; }
        .stat-card:nth-child(2) { transition-delay: 0.08s; }
        .stat-card:nth-child(3) { transition-delay: 0.16s; }
        .stat-card:nth-child(4) { transition-delay: 0.24s; }

        /* ===== Responsive ===== */
        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .charts-row { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .hamburger-menu { display: block; }
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; padding: 16px; }
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
            .stats-grid .stat-card { padding: 16px; }
            .stat-card .stat-number { font-size: 1.4rem; }
            .charts-row { gap: 16px; }
            .topbar { flex-direction: row; flex-wrap: wrap; }
            .topbar h1 { font-size: 1.1rem; }
            .topbar h1 span { font-size: 0.7rem; }
            .topbar-actions a { padding: 8px 14px; font-size: 0.75rem; }
            .table-header { padding: 14px 16px; }
            th, td { padding: 10px 14px; font-size: 0.78rem; }
            .td-message { max-width: 100px; }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .main { padding: 12px; }
        }
    </style>
</head>
<body>

<?php if (!$loggedIn): ?>

<div class="login-page">
    <div class="login-box">
        <div class="brand">IS.</div>
        <div class="subtitle">Admin Dashboard</div>
        <h2>Welcome back</h2>
        <p style="color:#6c6c8a; font-size:0.85rem; margin-bottom:20px;">Enter your password to access the dashboard.</p>
        <?php if ($error): ?><div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div><?php endif; ?>
        <form method="post">
            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter admin password" required>
            </div>
            <button type="submit" class="login-btn"><i class="fas fa-lock"></i> Sign In</button>
        </form>
    </div>
</div>

<?php else: ?>

<!-- Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand"><i class="fas fa-cube"></i> IS. Admin</div>
    <div class="sidebar-nav">
        <a href="#" class="active"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="#messages"><i class="fas fa-inbox"></i> Messages <span class="badge-count"><?php echo $totalMessages; ?></span></a>
        <a href="https://ibrahimsenesie.onrender.com" target="_blank"><i class="fas fa-external-link-alt"></i> View Site</a>
    </div>
    <div class="sidebar-user">
        <div class="user-info">
            <div class="avatar">IS</div>
            <div>
                <div class="name">Ibrahim Senesie</div>
                <div class="role">Administrator</div>
            </div>
        </div>
    </div>
</div>

<!-- Main -->
<div class="main">
    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-left">
            <button class="hamburger-menu" id="sidebarToggle" aria-label="Toggle sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <div>
                <h1>Dashboard <span><?php echo date('l, F j'); ?></span></h1>
            </div>
        </div>
        <div class="topbar-actions">
            <a href="https://ibrahimsenesie.onrender.com" target="_blank" class="visit-btn"><i class="fas fa-external-link-alt"></i> Visit Site</a>
            <a href="?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid" id="statsGrid">
        <div class="stat-card">
            <div class="stat-head">
                <div class="stat-icon blue"><i class="fas fa-globe"></i></div>
            </div>
            <div class="stat-number" data-target="<?php echo $totalVisits; ?>">0</div>
            <div class="stat-label">Total Visits</div>
        </div>
        <div class="stat-card">
            <div class="stat-head">
                <div class="stat-icon green"><i class="fas fa-calendar-day"></i></div>
                <span class="stat-change up">Today</span>
            </div>
            <div class="stat-number" data-target="<?php echo $todayVisits; ?>">0</div>
            <div class="stat-label">Visits Today</div>
        </div>
        <div class="stat-card">
            <div class="stat-head">
                <div class="stat-icon orange"><i class="fas fa-chart-line"></i></div>
                <span class="stat-change up">7 days</span>
            </div>
            <div class="stat-number" data-target="<?php echo $weekVisits; ?>">0</div>
            <div class="stat-label">This Week</div>
        </div>
        <div class="stat-card">
            <div class="stat-head">
                <div class="stat-icon purple"><i class="fas fa-users"></i></div>
            </div>
            <div class="stat-number" data-target="<?php echo $uniqueVisitors; ?>">0</div>
            <div class="stat-label">Unique Visitors</div>
        </div>
    </div>

    <!-- Charts -->
    <div class="charts-row">
        <div class="chart-card" id="chartDaily">
            <div class="chart-header">
                <h3><i class="fas fa-chart-bar" style="color:#6c63ff;"></i> Daily Visits (30 days)</h3>
            </div>
            <canvas id="dailyChart"></canvas>
        </div>
        <div class="chart-card" id="chartPages">
            <div class="chart-header">
                <h3><i class="fas fa-file-alt" style="color:#00d4aa;"></i> Pages</h3>
            </div>
            <canvas id="pageChart"></canvas>
        </div>
    </div>

    <!-- Recent Visits -->
    <div class="table-card" id="recentVisits">
        <div class="table-header">
            <h2><i class="fas fa-clock" style="color:#ffa94d;"></i> Recent Visits</h2>
            <span class="count">Last 10</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>IP Address</th>
                        <th>Page</th>
                        <th>Date</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recentVisits) > 0): ?>
                        <?php foreach ($recentVisits as $v): ?>
                        <tr>
                            <td class="td-ip"><?php echo htmlspecialchars($v['ip_address']); ?></td>
                            <td class="td-page"><?php echo $v['page_url'] === '/' ? 'Home' : htmlspecialchars($v['page_url']); ?></td>
                            <td class="td-date"><?php echo date('M j, Y', strtotime($v['visit_date'])); ?></td>
                            <td class="td-time"><?php echo date('g:i A', strtotime($v['visit_time'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center;padding:40px;color:#6c6c8a;">No visits recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Messages -->
    <div class="table-card" id="messages">
        <div class="table-header">
            <h2><i class="fas fa-inbox" style="color:#6c63ff;"></i> Messages</h2>
            <span class="count"><?php echo $totalMessages; ?> total</span>
        </div>

        <?php if (count($messages) > 0): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $msg): ?>
                    <tr>
                        <td class="td-name"><?php echo htmlspecialchars($msg['name']); ?></td>
                        <td><a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>" class="td-email"><?php echo htmlspecialchars($msg['email']); ?></a></td>
                        <td><span class="td-subject"><?php echo htmlspecialchars($msg['subject']); ?></span></td>
                        <td class="td-message" title="<?php echo htmlspecialchars($msg['message']); ?>"><?php echo htmlspecialchars($msg['message']); ?></td>
                        <td class="td-date"><?php echo date('M j, Y', strtotime($msg['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>No messages yet</h3>
            <p>When visitors send messages from your portfolio, they'll appear here.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// ===== Sidebar Toggle =====
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
const toggle = document.getElementById('sidebarToggle');

function openSidebar() {
    sidebar.classList.add('open');
    overlay.classList.add('show');
}
function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('show');
}
if (toggle) toggle.addEventListener('click', openSidebar);
if (overlay) overlay.addEventListener('click', closeSidebar);

// Close sidebar on window resize above breakpoint
window.addEventListener('resize', function() {
    if (window.innerWidth > 768 && sidebar.classList.contains('open')) {
        closeSidebar();
    }
});

// ===== Scroll Reveal Animations =====
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, { threshold: 0.1 });

document.querySelectorAll('.stat-card, .chart-card, .table-card').forEach(el => {
    observer.observe(el);
});

// ===== Animated Counters =====
function animateCounters() {
    document.querySelectorAll('.stat-number[data-target]').forEach(el => {
        const target = parseInt(el.getAttribute('data-target'));
        if (target === 0) { el.textContent = '0'; return; }
        const duration = 1000;
        const start = performance.now();

        function update(now) {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = Math.round(eased * target);
            el.textContent = current.toLocaleString();
            if (progress < 1) requestAnimationFrame(update);
        }
        requestAnimationFrame(update);
    });
}

// Trigger counters when stats grid is visible
const statsObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            animateCounters();
            statsObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.3 });
const statsGrid = document.getElementById('statsGrid');
if (statsGrid) statsObserver.observe(statsGrid);

// ===== Charts =====
<?php if ($loggedIn): ?>

// Daily Visits Line Chart
const dailyCtx = document.getElementById('dailyChart');
if (dailyCtx) {
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chartDates); ?>,
            datasets: [{
                label: 'Visits',
                data: <?php echo json_encode($chartCounts); ?>,
                borderColor: '#6c63ff',
                backgroundColor: 'rgba(108, 99, 255, 0.08)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 3,
                pointBackgroundColor: '#6c63ff',
                pointBorderColor: '#6c63ff',
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    ticks: { color: '#6c6c8a', font: { size: 10 }, maxTicksLimit: 10 },
                    grid: { color: 'rgba(255,255,255,0.04)' }
                },
                y: {
                    beginAtZero: true,
                    ticks: { color: '#6c6c8a', font: { size: 10 }, stepSize: 1 },
                    grid: { color: 'rgba(255,255,255,0.04)' }
                }
            },
            interaction: { mode: 'index', intersect: false }
        }
    });
}

// Page Distribution Doughnut Chart
const pageCtx = document.getElementById('pageChart');
if (pageCtx && <?php echo count($pageLabels); ?> > 0) {
    new Chart(pageCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($pageLabels); ?>,
            datasets: [{
                data: <?php echo json_encode($pageCounts); ?>,
                backgroundColor: <?php echo json_encode(array_slice($pageColors, 0, count($pageLabels))); ?>,
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#a0a0b8', font: { size: 10 }, padding: 12, boxWidth: 10 }
                }
            }
        }
    });
}
<?php endif; ?>
</script>

<?php endif; ?>
</body>
</html>
