<?php
require_once __DIR__ . '/../config/helpers.php';

$pdo = null;
try {
    require_once '../config/db.php';
} catch (Throwable $e) {
    $pdo = null;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

$trip = null;
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM trips WHERE id = ?");
        $stmt->execute([$id]);
        $trip = $stmt->fetch();
    } catch (Throwable $e) {
        $trip = null;
    }
}

if (!$trip) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Trip not found</title><link rel="stylesheet" href="css/style.css"></head><body class="admin-body"><main class="login-card"><h1>Trip not found</h1><p>This trip could not be found.</p><a href="index.php" class="btn">Back to all trips</a></main></body></html>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($trip['title']) ?> - Details</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="../uploads/Travelly.png" type="image/png">
    <script src="https://kit.fontawesome.com/a369785260.js" crossorigin="anonymous"></script>
</head>
<body class="detail-page">
    <nav class="navbar" aria-label="Main navigation">
        <a class="logo" href="index.php" aria-label="Travelly home">
            <img src="../uploads/Travelly.png" alt="">
            <span>Travelly</span>
        </a>
        <div><a href="index.php" class="nav-link"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> All trips</a></div>
    </nav>

    <div class="detail-shell">
        <header class="detail-hero">
            <?php if (!empty($trip['cover_image'])): ?>
                <img src="../<?= h($trip['cover_image']) ?>" alt="<?= h($trip['title']) ?>" class="detail-hero-image">
            <?php endif; ?>
            <div class="detail-hero-content">
                <span class="eyebrow">Trip details</span>
                <h1><?= h($trip['title']) ?></h1>
                <p><?= h($trip['city'] . ', ' . $trip['country']) ?></p>
            </div>
        </header>

        <div class="detail-layout">
            <aside class="detail-summary card">
                <h3>Trip summary</h3>
                <div class="detail-stat">
                    <span>Destination</span>
                    <strong><i class="fa-solid fa-location-dot" aria-hidden="true"></i> <?= h($trip['city'] . ', ' . $trip['country']) ?></strong>
                </div>
                <div class="detail-stat">
                    <span>Estimated budget</span>
                    <strong>€<?= number_format((float)$trip['budget'], 2, ',', '.') ?></strong>
                </div>
                <div class="detail-stat">
                    <span>Travel dates</span>
                    <strong><?php if ($trip['start_date'] && $trip['end_date']): ?><?= h(date('M j, Y', strtotime($trip['start_date']))) ?> – <?= h(date('M j, Y', strtotime($trip['end_date']))) ?><?php else: ?>Dates not set<?php endif; ?></strong>
                </div>
                <a href="index.php" class="btn">Back to all trips</a>
            </aside>

            <main class="detail-panel card">
                <h2>Itinerary & notes</h2>
                <div class="detail-note">
                    <?= nl2br(h($trip['description'] ?: 'No notes have been added for this trip.')) ?>
                </div>

                <div class="itinerary-steps">
                    <div class="step">
                        <span>1</span>
                        <p>Check your travel dates and confirm your reservations.</p>
                    </div>
                    <div class="step">
                        <span>2</span>
                        <p>Check your luggage and tick off your packing list.</p>
                    </div>
                    <div class="step">
                        <span>3</span>
                        <p>Enjoy your adventure and keep your plans close at hand.</p>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
