<?php
require_once __DIR__ . '/../config/helpers.php';
requireAdmin();

$pdo = null;
$databaseError = '';
try {
    require_once '../config/database.php';
} catch (PDOException $e) {
    error_log('Travelly database error: ' . $e->getMessage());
    $databaseError = 'The database is unavailable. Please try again later.';
}

$tripId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$tripId || $tripId < 1) {
    header('Location: dashboard.php');
    exit;
}

$stmt = $pdo ? $pdo->prepare('SELECT * FROM trips WHERE id = ?') : null;
if ($stmt) {
    $stmt->execute([$tripId]);
    $trip = $stmt->fetch();
} else {
    $trip = null;
}

if ($pdo && !$trip) {
    http_response_code(404);
    exit('Trip not found.');
}

$message = $databaseError;
$csrfToken = csrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo && $trip) {
    if (!validCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Your session expired. Refresh the page and try again.';
    } else {
        $tripName = trim($_POST['trip_name'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        $estimatedCost = filter_var($_POST['estimated_cost'] ?? null, FILTER_VALIDATE_FLOAT);
        $notes = trim($_POST['notes'] ?? '');

        if ($tripName === '' || $city === '' || $country === '' || $startDate === '' || $endDate === '') {
            $message = 'Please complete all required fields.';
        } elseif ($estimatedCost === false || $estimatedCost < 0) {
            $message = 'Enter a valid non-negative budget.';
        } elseif ($endDate < $startDate) {
            $message = 'The return date must be on or after the departure date.';
        } else {
            try {
                require_once 'trip_image.php';
                $imagePath = $trip['cover_image'];
                if (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $imagePath = storeTripImage($_FILES['image']);
                }

                $stmt = $pdo->prepare('UPDATE trips SET title = ?, country = ?, city = ?, start_date = ?, end_date = ?, budget = ?, description = ?, cover_image = ? WHERE id = ?');
                $stmt->execute([$tripName, $country, $city, $startDate, $endDate, $estimatedCost, $notes, $imagePath, $tripId]);
                header('Location: dashboard.php');
                exit;
            } catch (RuntimeException $e) {
                $message = $e->getMessage();
            } catch (PDOException $e) {
                error_log('Travelly trip update error: ' . $e->getMessage());
                $message = 'The trip could not be updated. Please check the details and try again.';
            }
        }
    }
}

$formData = $trip ?: [
    'title' => '',
    'city' => '',
    'country' => '',
    'start_date' => '',
    'end_date' => '',
    'budget' => '0.00',
    'cover_image' => null,
    'description' => '',
];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = array_merge($formData, [
        'title' => $_POST['trip_name'] ?? $formData['title'],
        'city' => $_POST['city'] ?? $formData['city'],
        'country' => $_POST['country'] ?? $formData['country'],
        'start_date' => $_POST['start_date'] ?? substr((string)($formData['start_date'] ?? ''), 0, 10),
        'end_date' => $_POST['end_date'] ?? substr((string)($formData['end_date'] ?? ''), 0, 10),
        'budget' => $_POST['estimated_cost'] ?? $formData['budget'],
        'description' => $_POST['notes'] ?? $formData['description'],
    ]);
}
$startDateValue = substr((string)$formData['start_date'], 0, 10);
$endDateValue = substr((string)$formData['end_date'], 0, 10);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travelly CMS - Edit Trip</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body class="admin-body">
    <main class="login-card">
        <h1>Edit Trip</h1>
        <p>Update the trip details below. Leave the photo empty to keep the current image.</p>
        <?php if ($message): ?><p class="error" role="alert"><?= h($message) ?></p><?php endif; ?>

        <?php if ($trip): ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                <label for="trip_name">Trip name *</label>
                <input id="trip_name" type="text" name="trip_name" maxlength="100" value="<?= h($formData['title']) ?>" required>
                <label for="city">City *</label>
                <input id="city" type="text" name="city" maxlength="100" value="<?= h($formData['city']) ?>" required>
                <label for="country">Country *</label>
                <input id="country" type="text" name="country" maxlength="80" value="<?= h($formData['country']) ?>" required>
                <label for="start_date">Departure date *</label>
                <input id="start_date" type="date" name="start_date" value="<?= h($startDateValue) ?>" required>
                <label for="end_date">Return date *</label>
                <input id="end_date" type="date" name="end_date" value="<?= h($endDateValue) ?>" required>
                <label for="estimated_cost">Estimated budget (€)</label>
                <input id="estimated_cost" type="number" name="estimated_cost" step="0.01" min="0" value="<?= h($formData['budget']) ?>" required>
                <label for="image">Replace trip photo (optional, max 5 MB)</label>
                <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                <p id="image-error" class="error" role="alert" hidden></p>
                <?php if (!empty($formData['cover_image'])): ?>
                    <img id="image-preview" class="image-preview" src="../<?= h($formData['cover_image']) ?>" alt="Current trip photo">
                <?php else: ?>
                    <img id="image-preview" class="image-preview" alt="Selected trip photo preview" hidden>
                <?php endif; ?>
                <label for="notes">Notes and itinerary</label>
                <textarea id="notes" name="notes"><?= h($formData['description']) ?></textarea>
                <button type="submit" class="btn">Save changes</button>
                <a href="dashboard.php" class="btn secondary" style="text-align:center;">Cancel</a>
            </form>
            <script src="../public/js/main.js"></script>
        <?php endif; ?>
    </main>
</body>
</html>
