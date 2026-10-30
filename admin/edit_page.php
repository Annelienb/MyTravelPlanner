<?php
require_once __DIR__ . '/../config/helpers.php';
requireAdmin();

$pdo = null;
try {
    require_once '../config/database.php';
} catch (Throwable $e) {
    $pdo = null;
}

$message = '';
$csrfToken = csrfToken();
$page = ['title' => 'Go somewhere wonderful.', 'subtitle' => 'Keep the little details in one lovely place, and make more room for the moments that matter.'];
$pageExists = false;

if ($pdo) {
    $stmt = $pdo->prepare('SELECT page_key, title, body FROM page_content WHERE page_key = ?');
    $stmt->execute(['Home']);
    $pageContent = $stmt->fetch();
    if ($pageContent) {
        $pageExists = true;
        $page = [
            'title' => $pageContent['title'],
            'subtitle' => $pageContent['body'],
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Your session expired. Refresh the page and try again.';
    } elseif (!$pdo) {
        $message = 'The database is unavailable. Please try again later.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');

        if ($title === '' || $subtitle === '') {
            $message = 'The title and introduction are required.';
        } else {
            if ($pageExists) {
                $stmt = $pdo->prepare('UPDATE page_content SET title = ?, body = ? WHERE page_key = ?');
                $stmt->execute([$title, $subtitle, 'Home']);
            } else {
                $stmt = $pdo->prepare('INSERT INTO page_content (page_key, title, body) VALUES (?, ?, ?)');
                $stmt->execute(['Home', $title, $subtitle]);
            }

            header('Location: dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travelly CMS - Edit Homepage</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body class="admin-body">
    <div class="login-card">
        <h1>Edit Homepage</h1>
        <p>Update the heading and introduction shown to your visitors.</p>
        <?php if ($message): ?><p class="error" role="alert"><?= h($message) ?></p><?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
            <label for="title">Homepage title</label>
            <input id="title" type="text" name="title" value="<?= h($page['title']) ?>" placeholder="Homepage title" required>
            <label for="subtitle">Introduction</label>
            <textarea id="subtitle" name="subtitle" placeholder="A short introduction for your visitors" required><?= h($page['subtitle']) ?></textarea>
            <button type="submit" class="btn">Save changes</button>
            <a href="dashboard.php" class="btn secondary" style="text-align:center;">Back to dashboard</a>
        </form>
    </div>
</body>
</html>
