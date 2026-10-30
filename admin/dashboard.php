<?php
require_once __DIR__ . '/../config/helpers.php';
requireAdmin();

$csrfToken = csrfToken();

$pdo = null;
try {
    require_once __DIR__ . '/../config/database.php';
} catch (Throwable $e) {
    $pdo = null;
}

$trips = [];
if ($pdo) {
    try {
        $trips = $pdo->query("SELECT * FROM trips")->fetchAll();
    } catch (Throwable $e) {
        $trips = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travelly CMS - Dashboard</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="icon" href="../uploads/Travelly.png" type="image/png">
</head>
<body class="admin-body">
    <nav class="navbar admin-navbar" aria-label="Admin navigation">
        <a class="logo" href="../public/index.php" aria-label="Travelly admin home">
            <img src="../uploads/Travelly.png" alt="">
            <span>Travelly <small>admin</small></span>
        </a>
        <div class="admin-nav-actions">
            <span>Welcome, <?= h($_SESSION['username'] ?? '') ?></span>
            <a href="change_password.php">Change password</a>
            <a href="logout.php">Log out</a>
        </div>
    </nav>

    <main class="admin-shell">
        <header class="admin-heading">
            <div>
                <p class="admin-kicker">Travelly CMS</p>
                <h1>Dashboard</h1>
                <p>Manage your homepage and keep your trips organized.</p>
            </div>
            <a href="../public/index.php" class="btn secondary">View website</a>
        </header>

        <div class="admin-grid">
            <section class="card admin-card">
                <h2>Homepage</h2>
                <p>Update the title and introduction visitors see.</p>
                <a href="edit_page.php" class="btn">Edit homepage</a>
            </section>

            <section class="card admin-card">
                <div class="admin-card-heading">
                    <div>
                        <h2>Trips</h2>
                        <p><?= count($trips) ?> <?= count($trips) === 1 ? 'trip' : 'trips' ?> in your overview</p>
                    </div>
                    <a href="add_trip.php" class="btn">Add a trip</a>
                </div>
                <?php if ($trips): ?>
                    <ul class="trip-admin-list">
                        <?php foreach ($trips as $t): ?>
                            <li class="trip-admin-item">
                                <span><?= h($t['title']) ?></span>
                                <div class="trip-admin-actions">
                                    <a href="edit_trip.php?id=<?= (int)$t['id'] ?>" class="edit-link">Edit</a>
                                    <form method="POST" action="delete_trip.php" class="delete-form" data-confirm="Delete this trip? This cannot be undone.">
                                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                        <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                                        <button type="submit" class="delete-link">Delete</button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="admin-empty">You haven't added any trips yet. Add your first trip to show it on the website.</p>
                <?php endif; ?>
            </section>
        </div>
    </main>
    <script src="../public/js/main.js"></script>
</body>
</html>
