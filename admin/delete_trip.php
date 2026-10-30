<?php
require_once __DIR__ . '/../config/helpers.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

if (!validCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(400);
    exit('Invalid or expired request. Return to the dashboard and try again.');
}

require_once __DIR__ . '/../config/database.php';

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    header('Location: dashboard.php');
    exit;
}

$stmt = $pdo->prepare('DELETE FROM trips WHERE id = ?');
$stmt->execute([$id]);

header('Location: dashboard.php');
exit;
