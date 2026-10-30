<?php
require_once __DIR__ . '/../config/helpers.php';
requireAdmin();

require_once __DIR__ . '/../config/database.php';

$error = '';
$success = '';
$csrfToken = csrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Refresh the page and try again.';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $currentHash = $stmt->fetchColumn();

        if (!$currentHash || !password_verify($currentPassword, $currentHash)) {
            $error = 'The current password is incorrect.';
        } elseif (strlen($newPassword) < 12) {
            $error = 'Your new password must contain at least 12 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'The new passwords do not match.';
        } elseif (password_verify($newPassword, $currentHash)) {
            $error = 'Choose a password different from your current password.';
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->execute([$newHash, $_SESSION['user_id']]);
            session_regenerate_id(true);
            $success = 'Your password has been changed.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Travelly</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body class="admin-body">
    <main class="login-card">
        <h1>Change Password</h1>
        <?php if ($error): ?><p class="error"><?= h($error) ?></p><?php endif; ?>
        <?php if ($success): ?><p class="success"><?= h($success) ?></p><?php endif; ?>
        <form method="POST" action="change_password.php">
            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
            <label for="current_password">Current password</label>
            <input id="current_password" type="password" name="current_password" autocomplete="current-password" required>
            <label for="new_password">New password</label>
            <input id="new_password" type="password" name="new_password" autocomplete="new-password" minlength="12" required>
            <label for="confirm_password">Confirm new password</label>
            <input id="confirm_password" type="password" name="confirm_password" autocomplete="new-password" minlength="12" required>
            <button type="submit" class="btn">Save password</button>
            <a href="dashboard.php" class="btn secondary" style="text-align:center;">Back to dashboard</a>
        </form>
    </main>
</body>
</html>
