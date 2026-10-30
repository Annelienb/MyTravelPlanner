<?php
require_once __DIR__ . '/../config/helpers.php';
startAppSession();

$error = '';
$dbError = '';
$pdo = null;
$isFirstRun = false;
$csrfToken = csrfToken();

try {
    require '../config/database.php';
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
    $isFirstRun = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0;
} catch (PDOException $e) {
    error_log('Travelly database error: ' . $e->getMessage());
    $pdo = null;
    $dbError = 'The database connection failed. Check your database settings and make sure the MySQL container is running.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    if (!validCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Refresh the page and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($isFirstRun) {
            if (strlen($username) < 3 || strlen($username) > 100) {
                $error = 'Choose a username between 3 and 100 characters.';
            } elseif (strlen($password) < 12) {
                $error = 'Choose a password with at least 12 characters for the first admin account.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
                $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
                $_SESSION['user_id'] = (int)$pdo->lastInsertId();
                $_SESSION['username'] = $username;
                session_regenerate_id(true);
                header('Location: dashboard.php');
                exit;
            }
        } else {
            $stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['username'] = $user['username'];
                session_regenerate_id(true);
                header('Location: dashboard.php');
                exit;
            }

            $error = 'The username or password is incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travelly CMS - Login</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body class="admin-body">
    <main class="login-card">
        <h1><?= $isFirstRun ? 'Create your admin account' : 'Travelly Admin Login' ?></h1>
        <?php if ($dbError): ?><p class="error"><?= h($dbError) ?></p><?php endif; ?>
        <?php if ($isFirstRun): ?>
            <p>This is your first time here. Create an admin account to get started.</p>
        <?php endif; ?>
        <?php if ($error): ?><p class="error"><?= h($error) ?></p><?php endif; ?>
        <?php if ($pdo): ?><form method="POST" action="login.php">
            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
            <label for="username">Username</label>
            <input id="username" type="text" name="username" autocomplete="username" minlength="3" maxlength="100" required>
            <label for="password">Password</label>
            <input id="password" type="password" name="password" autocomplete="<?= $isFirstRun ? 'new-password' : 'current-password' ?>" <?= $isFirstRun ? 'minlength="12"' : '' ?> required>
            <button type="submit" class="btn"><?= $isFirstRun ? 'Create admin account' : 'Log in' ?></button>
        </form><?php endif; ?>
    </main>
</body>
</html>
