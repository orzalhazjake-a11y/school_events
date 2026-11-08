
<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$name = $_SESSION['full_name'] ?? 'Administrator';
$search = trim($_GET['search'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

try {
    // Statistics
    $totalUsers = (int)$pdo->query(
        "SELECT COUNT(*) FROM users"
    )->fetchColumn();

    $totalStudents = (int)$pdo->query(
        "SELECT COUNT(*) FROM users WHERE role = 'student'"
    )->fetchColumn();

    $totalAdmins = (int)$pdo->query(
        "SELECT COUNT(*) FROM users WHERE role = 'admin'"
    )->fetchColumn();

    // Search and filter users
    $sql = "
        SELECT user_id, full_name, email, role, created_at
        FROM users
        WHERE 1=1
    ";

    $params = [];

    if ($search !== '') {
        $sql .= " AND (full_name LIKE ? OR email LIKE ?)";
        $keyword = "%{$search}%";
        $params[] = $keyword;
        $params[] = $keyword;
    }

    if (in_array($roleFilter, ['admin', 'student'], true)) {
        $sql .= " AND role = ?";
        $params[] = $roleFilter;
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $userCount = count($users);

} catch (PDOException $error) {
    die("Unable to load user accounts. Please check your database.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users | School Event System</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: #F8F5F5;
    color: #333;
}

.sidebar {
    width: 250px;
    height: 100vh;
    background: linear-gradient(180deg, #800000, #5B0018);
    color: white;
    position: fixed;
    top: 0;
    left: 0;
    padding: 28px 18px;
    overflow-y: auto;
}

.brand {
    font-size: 20px;
    font-weight: bold;
    text-align: center;
    padding-bottom: 30px;
    border-bottom: 1px solid rgba(255,255,255,.2);
}

.brand span {
    display: block;
    font-size: 12px;
    font-weight: normal;
    margin-top: 7px;
    color: #F2DCDC;
}

.nav-title {
    font-size: 11px;
    color: #E5BFC8;
    margin: 28px 12px 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.nav-link {
    display: block;
    color: white;
    text-decoration: none;
    padding: 13px 14px;
    border-radius: 8px;
    margin-bottom: 7px;
    font-size: 14px;
    transition: .2s;
}

.nav-link:hover,
.nav-link.active {
    background: rgba(255,255,255,.18);
}

.main {
    margin-left: 250px;
    padding: 30px;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 28px;
}

.topbar h1 {
    color: #5B0018;
    font-size: 28px;
}

.topbar p {
    color: #777;
    margin-top: 7px;
    font-size: 14px;
}

.profile {
    background: white;
    padding: 12px 16px;
    border-radius: 10px;
    box-shadow: 0 3px 12px rgba(0,0,0,.05);
    white-space: nowrap;
    font-size: 14px;
}

.profile strong {
    color: #800000;
}

.welcome {
    background: linear-gradient(120deg, #800000, #A52A46);
    color: white;
    padding: 28px;
    border-radius: 16px;
    margin-bottom: 25px;
    box-shadow: 0 8px 20px rgba(128,0,0,.12);
}

.welcome h2 {
    font-size: 24px;
    margin-bottom: 10px;
}

.welcome p {
    color: #F8E9EC;
    line-height: 1.6;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 28px;
}

.stat-card {
    background: white;
    padding: 22px;
    border-radius: 14px;
    border-top: 4px solid #800000;
    box-shadow: 0 4px 16px rgba(0,0,0,.04);
}

.stat-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #F7E9ED;
    border-radius: 12px;
    font-size: 24px;
    margin-bottom: 15px;
}

.stat-card p {
    color: #777;
    font-size: 13px;
    margin-bottom: 9px;
}

.stat-card h3 {
    color: #5B0018;
    font-size: 29px;
}

.filter-card {
    background: white;
    padding: 22px;
    border-radius: 14px;
    box-shadow: 0 4px 16px rgba(0,0,0,.04);
    margin-bottom: 25px;
}

.filter-card h2 {
    color: #5B0018;
    font-size: 20px;
    margin-bottom: 17px;
}

.filter-form {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.filter-form input,
.filter-form select {
    flex: 1;
    min-width: 180px;
    padding: 12px 14px;
    border: 1px solid #E2D6D9;
    border-radius: 8px;
    background: white;
    font-size: 14px;
    outline: none;
}

.filter-form input:focus,
.filter-form select:focus {
    border-color: #800000;
}

.btn {
    display: inline-block;
    border: none;
    border-radius: 8px;
    padding: 12px 17px;
    background: #800000;
    color: white;
    text-decoration: none;
    cursor: pointer;
    font-size: 13px;
    font-weight: bold;
    transition: .2s;
}

.btn:hover {
    background: #5B0018;
}

.btn-light {
    background: #F3E6E9;
    color: #800000;
}

.btn-light:hover {
    background: #E8D1D8;
}

.section-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 18px;
}

.section-heading h2 {
    color: #5B0018;
    font-size: 21px;
}

.count-badge {
    background: #F0DDE2;
    color: #800000;
    padding: 7px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: bold;
}

.table-card {
    background: white;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 4px 16px rgba(0,0,0,.04);
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 680px;
    border-collapse: collapse;
}

th, td {
    padding: 15px 12px;
    text-align: left;
    border-bottom: 1px solid #F0E8EA;
    font-size: 13px;
}

th {
    color: #800000;
    background: #FCF8F9;
}

td {
    color: #666;
}

.user-name {
    color: #4B0015;
    font-weight: bold;
}

.role-badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    background: #F7E9ED;
    color: #800000;
    font-size: 12px;
    font-weight: bold;
}

.role-badge.admin {
    background: #800000;
    color: white;
}

.empty {
    text-align: center;
    padding: 30px;
    color: #777;
}

@media (max-width: 1000px) {
    .stats-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 760px) {
    .sidebar {
        position: static;
        width: 100%;
        height: auto;
    }

    .main {
        margin-left: 0;
        padding: 18px;
    }

    .topbar {
        flex-direction: column;
        align-items: flex-start;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .filter-form {
        flex-direction: column;
    }

    .filter-form input,
    .filter-form select {
        width: 100%;
    }
}
</style>
</head>

<body>

<aside class="sidebar">
    <div class="brand">
        SCHOOL EVENT SYSTEM
        <span>Administrator Panel</span>
    </div>

    <div class="nav-title">Main Menu</div>
    <a href="admin_dashboard.php" class="nav-link">▦ Admin Dashboard</a>
    <a href="events.php" class="nav-link">▣ School Events</a>

    <div class="nav-title">Management</div>
    <a href="admin.php" class="nav-link">＋ Add / Manage Events</a>
    <a href="manage_attendance.php" class="nav-link">✓ Manage Attendance</a>
    <a href="attendance_report.php" class="nav-link">▤ Attendance Reports</a>
    <a href="manage_users.php" class="nav-link active">♙ Manage Users</a>

    <div class="nav-title">Account</div>
    <a href="logout.php" class="nav-link">↪ Logout</a>
</aside>

<main class="main">

    <header class="topbar">
        <div>
            <h1>Manage Users</h1>
            <p>View and search the accounts registered in your system.</p>
        </div>

        <div class="profile">
            Welcome, <strong><?= e($name) ?></strong>
        </div>
    </header>

    <section class="welcome">
        <h2>User Management</h2>
        <p>
            Monitor student and administrator accounts in one place.
            Use the search and role filter to find a specific account.
        </p>
    </section>

    <section class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <p>Total User Accounts</p>
            <h3><?= $totalUsers ?></h3>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🎓</div>
            <p>Student Accounts</p>
            <h3><?= $totalStudents ?></h3>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🛡️</div>
            <p>Administrator Accounts</p>
            <h3><?= $totalAdmins ?></h3>
        </div>
    </section>

    <section class="filter-card">
        <h2>Find a User</h2>

        <form method="GET" action="manage_users.php" class="filter-form">
            <input
                type="search"
                name="search"
                placeholder="Search name or email..."
                value="<?= e($search) ?>"
            >

            <select name="role">
                <option value="">All Roles</option>
                <option value="student" <?= $roleFilter === 'student' ? 'selected' : '' ?>>
                    Student
                </option>
                <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>
                    Admin
                </option>
            </select>

            <button type="submit" class="btn">Search Users</button>
            <a href="manage_users.php" class="btn btn-light">Reset</a>
        </form>
    </section>

    <div class="section-heading">
        <h2>Registered Users</h2>
        <span class="count-badge"><?= $userCount ?> user(s) found</span>
    </div>

    <section class="table-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <th>Role</th>
                    <th>Date Registered</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($userCount > 0): ?>

                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= (int)$user['user_id'] ?></td>
                        <td class="user-name"><?= e($user['full_name']) ?></td>
                        <td><?= e($user['email']) ?></td>
                        <td>
                            <span class="role-badge <?= $user['role'] === 'admin' ? 'admin' : '' ?>">
                                <?= e(ucfirst($user['role'])) ?>
                            </span>
                        </td>
                        <td><?= e($user['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="5" class="empty">
                        No users found. Try another search or filter.
                    </td>
                </tr>

            <?php endif; ?>
            </tbody>
        </table>
    </section>

</main>

</body>
</html>
s