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
$unreadCount = 0;

if ($loggedIn) {
    $stmt = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC");
    $messages = $stmt->fetchAll();
    $totalMessages = count($messages);
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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0a0a23; color: #e8e8f0; display: flex; min-height: 100vh; }
        a { text-decoration: none; color: inherit; }

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
        }

        /* ===== Header ===== */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }
        .topbar h1 { font-size: 1.6rem; font-weight: 700; }
        .topbar h1 span { color: #6c6c8a; font-weight: 400; font-size: 0.9rem; }
        .topbar-actions { display: flex; align-items: center; gap: 16px; }
        .topbar-actions a {
            padding: 10px 20px;
            border-radius: 10px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.06);
            color: #a0a0b8;
            font-size: 0.85rem;
            transition: all 0.3s;
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
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 16px;
            padding: 24px;
            transition: all 0.3s;
        }
        .stat-card:hover { transform: translateY(-2px); border-color: rgba(108,99,255,0.2); }
        .stat-card .stat-icon {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; margin-bottom: 16px;
        }
        .stat-card .stat-icon.blue { background: rgba(108,99,255,0.15); color: #6c63ff; }
        .stat-card .stat-icon.green { background: rgba(0,212,170,0.15); color: #00d4aa; }
        .stat-card .stat-icon.purple { background: rgba(118,75,162,0.15); color: #764ba2; }
        .stat-card .stat-number {
            font-size: 1.8rem; font-weight: 800; margin-bottom: 4px;
        }
        .stat-card .stat-label { font-size: 0.85rem; color: #6c6c8a; }

        /* ===== Messages Table ===== */
        .table-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 16px;
            overflow: hidden;
        }
        .table-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .table-header h2 { font-size: 1.1rem; font-weight: 600; }
        .table-header .count { font-size: 0.85rem; color: #6c6c8a; }
        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left;
            padding: 14px 24px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #6c6c8a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: rgba(255,255,255,0.02);
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        td {
            padding: 16px 24px;
            font-size: 0.88rem;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            color: #c8c8d8;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(108,99,255,0.04); }
        .td-name { font-weight: 600; color: #e8e8f0; }
        .td-email { color: #6c63ff; font-size: 0.82rem; }
        .td-subject {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            background: rgba(108,99,255,0.1);
            color: #8b85ff;
            font-weight: 500;
        }
        .td-message {
            max-width: 280px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #6c6c8a;
            font-size: 0.85rem;
        }
        .td-date { font-size: 0.82rem; color: #6c6c8a; white-space: nowrap; }

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
            margin-bottom: 8px;
        }
        .login-box .subtitle { color: #6c6c8a; font-size: 0.9rem; margin-bottom: 32px; }
        .login-box h2 { font-size: 1.3rem; margin-bottom: 8px; }
        .login-box .input-group {
            margin-bottom: 20px;
        }
        .login-box .input-group label {
            display: block;
            font-size: 0.85rem;
            color: #a0a0b8;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .login-box .input-group input {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            color: #fff;
            font-size: 0.95rem;
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
            padding: 14px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #6c63ff, #00d4aa);
            color: #fff;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.3s;
        }
        .login-btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .error-msg {
            background: rgba(255,71,87,0.1);
            border: 1px solid rgba(255,71,87,0.2);
            color: #ff4757;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 20px;
        }

        /* ===== Empty ===== */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }
        .empty-state i {
            font-size: 4rem;
            color: rgba(108,99,255,0.2);
            margin-bottom: 20px;
        }
        .empty-state h3 { font-size: 1.2rem; margin-bottom: 8px; }
        .empty-state p { color: #6c6c8a; font-size: 0.9rem; }

        /* ===== Responsive ===== */
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main { margin-left: 0; padding: 20px; }
            .stats-grid { grid-template-columns: 1fr; }
            .topbar { flex-direction: column; gap: 16px; align-items: flex-start; }
            td, th { padding: 12px 16px; }
            .td-message { max-width: 120px; }
            .td-subject { white-space: nowrap; max-width: 80px; overflow: hidden; text-overflow: ellipsis; }
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
        <p style="color:#6c6c8a; font-size:0.9rem; margin-bottom:24px;">Enter your password to access messages.</p>
        <?php if ($error): ?><div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div><?php endif; ?>
        <form method="post">
            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter admin password" required>
            </div>
            <button type="submit" class="login-btn"><i class="fas fa-lock"></i> Login</button>
        </form>
    </div>
</div>

<?php else: ?>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-brand"><i class="fas fa-cube"></i> IS. Admin</div>
    <div class="sidebar-nav">
        <a href="#" class="active"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="#"><i class="fas fa-inbox"></i> Messages <span class="badge-count"><?php echo $totalMessages; ?></span></a>
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
    <div class="topbar">
        <div>
            <h1>Dashboard <span>| Messages</span></h1>
        </div>
        <div class="topbar-actions">
            <a href="https://ibrahimsenesie.onrender.com" target="_blank" class="visit-btn"><i class="fas fa-external-link-alt"></i> Visit Site</a>
            <a href="?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-envelope"></i></div>
            <div class="stat-number"><?php echo $totalMessages; ?></div>
            <div class="stat-label">Total Messages</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div class="stat-number"><?php echo $totalMessages; ?></div>
            <div class="stat-label">Read</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fas fa-users"></i></div>
            <div class="stat-number"><?php echo $totalMessages; ?></div>
            <div class="stat-label">Unique Senders</div>
        </div>
    </div>

    <!-- Messages -->
    <div class="table-card">
        <div class="table-header">
            <h2><i class="fas fa-inbox" style="color:#6c63ff;"></i> All Messages</h2>
            <span class="count"><?php echo $totalMessages; ?> total</span>
        </div>

        <?php if (count($messages) > 0): ?>
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
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>No messages yet</h3>
            <p>When visitors send messages from your portfolio, they'll appear here.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>
</body>
</html>
