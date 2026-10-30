<?php
require_once __DIR__ . '/../config/helpers.php';
requireAdmin();

$pdo = null;
$databaseError = '';
try {
    require_once __DIR__ . '/../config/database.php';
} catch (PDOException $e) {
    error_log('Travelly database error: ' . $e->getMessage());
    $pdo = null;
    $databaseError = 'The database is unavailable. Please try again later.';
}

$message = $databaseError;
$csrfToken = csrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Your session expired. Refresh the page and try again.';
    } elseif (!$pdo) {
        $message = $databaseError;
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
                $imagePath = null;
                if (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $imagePath = storeTripImage($_FILES['image']);
                }

                $stmt = $pdo->prepare('INSERT INTO trips (title, country, city, start_date, end_date, budget, description, cover_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$tripName, $country, $city, $startDate, $endDate, $estimatedCost, $notes, $imagePath]);
                header('Location: dashboard.php');
                exit;
            } catch (RuntimeException $e) {
                $message = $e->getMessage();
            } catch (PDOException $e) {
                error_log('Travelly trip creation error: ' . $e->getMessage());
                $message = 'The trip could not be saved. Please check the details and try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travelly CMS - Add a Trip</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body class="admin-body">
    <div class="login-card">
        <h2>Add a Trip</h2>
        <p>Fields marked with an asterisk are required.</p>
        <?php if ($message): ?><p class="error" role="alert"><?= h($message) ?></p><?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
            <label for="trip_name">Trip name *</label>
            <input id="trip_name" type="text" name="trip_name" maxlength="100" placeholder="For example: Summer in Barcelona" required>
            <label for="city">City *</label>
            <input id="city" type="text" name="city" maxlength="100" placeholder="For example: Barcelona" required>
            <label for="country">Country *</label>
            <input id="country" type="text" name="country" maxlength="80" placeholder="For example: Spain" required>
            <label for="start_date">Departure date *</label>
            <input id="start_date" type="date" name="start_date" required>
            <label for="end_date">Return date *</label>
            <input id="end_date" type="date" name="end_date" required>
            <label for="estimated_cost">Estimated budget (€)</label>
            <input id="estimated_cost" type="number" step="0.01" name="estimated_cost" value="0" min="0" required>
            <label for="image">Trip photo (optional, max 5 MB)</label>
            <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
            <p id="image-error" class="error" role="alert" hidden></p>
            <img id="image-preview" class="image-preview" alt="Selected trip photo preview" hidden>
            <label for="notes">Notes and itinerary</label>
            <textarea id="notes" name="notes" placeholder="Add useful details for this trip."></textarea>
            <button type="submit" class="btn">Save trip</button>
            <a href="dashboard.php" class="btn secondary" style="text-align:center;">Back to dashboard</a>
        </form>
    </div>
    <script src="../public/js/main.js"></script>
</body>
</html>
