
<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: admin.php?error=invalid_id');
    exit;
}

try {
    $stmt = $pdo->prepare(
        "DELETE FROM events WHERE event_id = ?"
    );

    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        header('Location: admin.php?deleted=1');
        exit;
    }

    header('Location: admin.php?error=not_found');
    exit;

} catch (PDOException $error) {
    header('Location: admin.php?error=delete_failed');
    exit;
}
