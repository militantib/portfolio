<?php
session_start();
require_once __DIR__ . '/includes/db.php';

$password = 'admin123'; // Change this after first login

$error = '';
$loggedIn = false;

// Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === $password) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = 'Wrong password';
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

$loggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Fetch messages
$messages = [];
if ($loggedIn) {
    $stmt = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC");
    $messages = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Messages</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0a0a23; color: #e8e8f0; padding: 40px 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { font-size: 2rem; margin-bottom: 30px; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .login-box { max-width: 400px; margin: 100px auto; padding: 40px; background: rgba(255,255,255,0.04); border-radius: 16px; border: 1px solid rgba(255,255,255,0.08); }
        .login-box h2 { margin-bottom: 20px; }
        input[type="password"] { width: 100%; padding: 14px; border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; background: rgba(255,255,255,0.04); color: #fff; font-size: 1rem; outline: none; margin-bottom: 16px; }
        input[type="password"]:focus { border-color: #6c63ff; }
        button { padding: 14px 32px; border-radius: 12px; border: none; background: linear-gradient(135deg, #6c63ff, #00d4aa); color: #fff; font-weight: 600; font-size: 1rem; cursor: pointer; width: 100%; }
        button:hover { opacity: 0.9; }
        .error { color: #ff4757; margin-bottom: 16px; font-size: 0.9rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header a { color: #a0a0b8; text-decoration: none; font-size: 0.9rem; }
        .header a:hover { color: #ff4757; }
        table { width: 100%; border-collapse: collapse; background: rgba(255,255,255,0.02); border-radius: 12px; overflow: hidden; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.9rem; }
        th { background: rgba(255,255,255,0.05); font-weight: 600; color: #a0a0b8; }
        td { color: #e8e8f0; }
        .msg-preview { max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .date { font-size: 0.8rem; color: #6c6c8a; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 50px; font-size: 0.75rem; background: rgba(0,212,170,0.1); color: #00d4aa; }
        .empty { text-align: center; padding: 60px; color: #6c6c8a; }
        .empty i { font-size: 3rem; margin-bottom: 16px; opacity: 0.3; }
        @media (max-width: 768px) { table { font-size: 0.8rem; } th, td { padding: 10px 8px; } .msg-preview { max-width: 120px; } }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php if (!$loggedIn): ?>
            <div class="login-box">
                <h2>Admin Login</h2>
                <?php if ($error): ?><div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div><?php endif; ?>
                <form method="post">
                    <input type="password" name="password" placeholder="Enter password" required>
                    <button type="submit"><i class="fas fa-lock"></i> Login</button>
                </form>
            </div>
        <?php else: ?>
            <div class="header">
                <h1><i class="fas fa-envelope"></i> Messages</h1>
                <a href="?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
            <?php if (count($messages) > 0): ?>
                <div style="margin-bottom: 16px; color: #a0a0b8; font-size: 0.9rem;">
                    <i class="fas fa-inbox"></i> <?php echo count($messages); ?> message(s)
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $msg): ?>
                        <tr>
                            <td class="date"><?php echo date('M j, Y', strtotime($msg['created_at'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($msg['name']); ?></strong></td>
                            <td><a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>" style="color: #6c63ff;"><?php echo htmlspecialchars($msg['email']); ?></a></td>
                            <td><span class="badge"><?php echo htmlspecialchars($msg['subject']); ?></span></td>
                            <td class="msg-preview" title="<?php echo htmlspecialchars($msg['message']); ?>"><?php echo htmlspecialchars($msg['message']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty">
                    <i class="fas fa-inbox"></i>
                    <p>No messages yet.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
