
<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? 'student';

    if ($role === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

$error = '';

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (
        $name === '' ||
        $email === '' ||
        $password === '' ||
        $confirm_password === ''
    ) {
        $error = 'Please complete all required fields.';

    } elseif (strlen($name) > 100) {
        $error = 'Full name must not exceed 100 characters.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';

    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';

    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';

    } else {
        try {
            $check = $pdo->prepare(
                "SELECT user_id FROM users WHERE email = ? LIMIT 1"
            );

            $check->execute([$email]);
            $exists = $check->fetch(PDO::FETCH_ASSOC);

            if ($exists) {
                $error = 'This email is already registered.';

            } else {
                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $role = 'student';
                $status = 'approved';

                $stmt = $pdo->prepare(
                    "INSERT INTO users
                    (full_name, email, password, role, status)
                    VALUES (?, ?, ?, ?, ?)"
                );

                $stmt->execute([
                    $name,
                    $email,
                    $hashed_password,
                    $role,
                    $status
                ]);

                header('Location: login.php?registered=1');
                exit;
            }

        } catch (PDOException $errorException) {
            if ($errorException->getCode() === '23000') {
                $error = 'This email is already registered.';
            } else {
                $error = 'Unable to create account. Please try again.';
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
<title>Create Account | UEP School Events</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: #F8F5F5;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 25px;
}

.container {
    width: 100%;
    max-width: 1000px;
    min-height: 580px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    background: white;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 10px 35px rgba(0,0,0,.10);
}

.banner {
    background: linear-gradient(145deg, #800000, #5B0018);
    color: white;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 40px;
    text-align: center;
}

.banner h1 {
    font-size: 29px;
    margin-bottom: 15px;
}

.banner p {
    color: #F2DCDC;
    line-height: 1.8;
    font-size: 14px;
}

.form-section {
    padding: 55px 45px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.form-section h2 {
    color: #5B0018;
    font-size: 28px;
    margin-bottom: 10px;
}

.subtitle {
    color: #777;
    font-size: 14px;
    margin-bottom: 28px;
    line-height: 1.6;
}

.form-group {
    margin-bottom: 17px;
}

label {
    display: block;
    color: #4B0015;
    font-weight: bold;
    font-size: 13px;
    margin-bottom: 9px;
}

input {
    width: 100%;
    padding: 14px;
    border: 1px solid #E2D6D9;
    border-radius: 8px;
    font-size: 14px;
    outline: none;
}

input:focus {
    border-color: #800000;
    box-shadow: 0 0 0 3px rgba(128,0,0,.08);
}

.password-wrapper {
    position: relative;
}

.password-wrapper input {
    padding-right: 75px;
}

.toggle-password {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    border: none;
    background: transparent;
    color: #800000;
    font-size: 12px;
    font-weight: bold;
    cursor: pointer;
}

.btn {
    width: 100%;
    padding: 15px;
    border: none;
    border-radius: 8px;
    background: #800000;
    color: white;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    margin-top: 8px;
    transition: .2s;
}

.btn:hover {
    background: #5B0018;
}

.error-message {
    background: #FBEAEC;
    color: #800000;
    padding: 13px;
    border-radius: 8px;
    margin-bottom: 18px;
    font-size: 13px;
    line-height: 1.5;
}

.login-link {
    text-align: center;
    margin-top: 22px;
    font-size: 13px;
    color: #777;
}

.login-link a {
    color: #800000;
    font-weight: bold;
    text-decoration: none;
}

.login-link a:hover {
    text-decoration: underline;
}

footer {
    text-align: center;
    color: #999;
    font-size: 11px;
    margin-top: 30px;
}

@media (max-width: 760px) {
    body {
        padding: 15px;
    }

    .container {
        grid-template-columns: 1fr;
    }

    .banner {
        padding: 30px 20px;
        min-height: 180px;
    }

    .banner h1 {
        font-size: 23px;
    }

    .form-section {
        padding: 35px 25px;
    }
}
</style>
</head>

<body>

<div class="container">

    <section class="banner">
        <h1>UEP SCHOOL EVENTS</h1>
        <p>
            Join Our Community<br>
            Discover school activities,<br>
            participate in events,<br>
            and create great memories.<br>
            School Events. Made Simple.
        </p>
    </section>

    <section class="form-section">

        <h2>Create Account</h2>

        <p class="subtitle">
            Register as a student and explore school events.
        </p>

        <?php if ($error !== ''): ?>
            <div class="error-message">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="signup.php">

            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    maxlength="100"
                    value="<?= e($_POST['full_name'] ?? '') ?>"
                    placeholder="Enter your full name"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="100"
                    value="<?= e($_POST['email'] ?? '') ?>"
                    placeholder="Enter your email"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        minlength="8"
                        placeholder="At least 8 characters"
                        required
                    >
                    <button
                        type="button"
                        class="toggle-password"
                        onclick="togglePassword('password', this)"
                    >Show</button>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="password-wrapper">
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        minlength="8"
                        placeholder="Confirm your password"
                        required
                    >
                    <button
                        type="button"
                        class="toggle-password"
                        onclick="togglePassword('confirm_password', this)"
                    >Show</button>
                </div>
            </div>

            <button type="submit" class="btn">
                Create Account
            </button>

        </form>

        <div class="login-link">
            Already have an account?
            <a href="login.php">Sign In</a>
        </div>

        <footer>
            © 2026 UEP School Events
        </footer>

    </section>

</div>

<script>
function togglePassword(id, button) {
    const input = document.getElementById(id);

    if (input.type === 'password') {
        input.type = 'text';
        button.textContent = 'Hide';
    } else {
        input.type = 'password';
        button.textContent = 'Show';
    }
}
</script>

</body>
</html>
