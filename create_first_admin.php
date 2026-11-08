<?php
require_once __DIR__ . '/config/database.php';

$message = '';

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
$adminCount = (int) $stmt->fetchColumn();

if ($adminCount > 0) {
    die("An admin account already exists. This setup is disabled.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($fullName === '' || $email === '' || $password === '') {
        $message = "Please complete all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            "INSERT INTO users (full_name, email, password, role, status)
             VALUES (?, ?, ?, 'admin', 'approved')"
        );

        try {
            $stmt->execute([$fullName, $email, $hashedPassword]);
            $message = "Admin account created successfully!";
        } catch (PDOException $e) {
            $message = "Unable to create account. Check if the email already exists.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create First Admin</title>
</head>
<body>
    <h2>Create First Admin Account</h2>

    <p><?= htmlspecialchars($message) ?></p>

    <form method="POST">
        <label>Full Name</label><br>
        <input type="text" name="full_name" required><br><br>

        <label>Email</label><br>
        <input type="email" name="email" required><br><br>

        <label>Password (minimum 8 characters)</label><br>
        <input type="password" name="password" required minlength="8"><br><br>

        <button type="submit">Create Admin</button>
    </form>
</body>
</html>