
<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

$name = $_SESSION['full_name'] ?? 'Administrator';

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['approval_csrf'])) {
    $_SESSION['approval_csrf'] = bin2hex(random_bytes(32));
}

/* PROCESS STUDENT ACCOUNT ACTIONS */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['account_action'])) {
    $token = $_POST['csrf_token'] ?? '';
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $action = $_POST['account_action'];

    if (!is_string($token) || !hash_equals($_SESSION['approval_csrf'], $token)) {
        $_SESSION['approval_message'] = 'Invalid request. Please try again.';
    } elseif (
        !$userId ||
        !in_array($action, ['approve', 'reject', 'edit_status', 'delete'], true)
    ) {
        $_SESSION['approval_message'] = 'Invalid account or action.';
    } else {
        try {
            if ($action === 'approve' || $action === 'reject') {
                $newStatus = $action === 'approve' ? 'approved' : 'rejected';

                $stmt = $pdo->prepare(
                    "UPDATE users SET status = ?
                     WHERE user_id = ? AND role = 'student'"
                );
                $stmt->execute([$newStatus, $userId]);

                $_SESSION['approval_message'] = $stmt->rowCount() > 0
                    ? "Student account $newStatus successfully."
                    : 'Student account was not found or already has that status.';

            } elseif ($action === 'edit_status') {
                $newStatus = $_POST['status'] ?? '';

                if (!in_array($newStatus, ['pending', 'approved', 'rejected'], true)) {
                    throw new Exception('Invalid status selected.');
                }

                $stmt = $pdo->prepare(
                    "UPDATE users SET status = ?
                     WHERE user_id = ? AND role = 'student'"
                );
                $stmt->execute([$newStatus, $userId]);

                $_SESSION['approval_message'] = 'Student account status updated successfully.';

            } elseif ($action === 'delete') {
                $stmt = $pdo->prepare(
                    "DELETE FROM users
                     WHERE user_id = ? AND role = 'student'"
                );
                $stmt->execute([$userId]);

                $_SESSION['approval_message'] = $stmt->rowCount() > 0
                    ? 'Student account deleted successfully.'
                    : 'Student account was not found.';
            }

        } catch (Throwable $error) {
            error_log($error->getMessage());

            $_SESSION['approval_message'] =
                'Unable to process the request. Please check the database or related records.';
        }
    }

    header('Location: admin_dashboard.php');
    exit;
}

/* FLASH MESSAGE */
$approvalMessage = $_SESSION['approval_message'] ?? '';
unset($_SESSION['approval_message']);

/* SYSTEM COUNTS */
$totalEvents = (int) $pdo->query(
    "SELECT COUNT(*) FROM events"
)->fetchColumn();

$totalStudents = (int) $pdo->query(
    "SELECT COUNT(*) FROM users WHERE role = 'student'"
)->fetchColumn();

$totalUsers = (int) $pdo->query(
    "SELECT COUNT(*) FROM users"
)->fetchColumn();

$totalRegistrations = (int) $pdo->query(
    "SELECT COUNT(*) FROM event_registrations"
)->fetchColumn();

$pendingCount = (int) $pdo->query(
    "SELECT COUNT(*) FROM users
     WHERE role = 'student' AND status = 'pending'"
)->fetchColumn();

/* STUDENT ACCOUNTS */
$studentStmt = $pdo->query(
    "SELECT user_id, full_name, email, status
     FROM users
     WHERE role = 'student'
     ORDER BY
        CASE status
            WHEN 'pending' THEN 1
            WHEN 'approved' THEN 2
            WHEN 'rejected' THEN 3
            ELSE 4
        END,
        user_id DESC
     LIMIT 100"
);

$studentAccounts = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

/* LATEST EVENTS */
$eventStmt = $pdo->query(
    "SELECT event_id, title, event_date, location, category
     FROM events
     ORDER BY event_date ASC
     LIMIT 5"
);

$latestEvents = $eventStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard | School Event System</title>

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
    font-size: 14px;
}

.profile strong {
    color: #800000;
}

.welcome {
    background: linear-gradient(120deg, #800000, #A52A46);
    color: white;
    padding: 30px;
    border-radius: 16px;
    margin-bottom: 25px;
    box-shadow: 0 8px 20px rgba(128,0,0,.12);
}

.welcome h2 {
    font-size: 25px;
    margin-bottom: 10px;
}

.welcome p {
    color: #F8E9EC;
    line-height: 1.6;
    margin-bottom: 20px;
}

.btn {
    display: inline-block;
    border: none;
    border-radius: 7px;
    padding: 10px 14px;
    background: #800000;
    color: white;
    text-decoration: none;
    cursor: pointer;
    font-size: 12px;
    font-weight: bold;
    transition: .2s;
}

.btn:hover {
    opacity: .85;
}

.welcome .btn {
    background: white;
    color: #800000;
}

.section-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin: 26px 0 18px;
}

.section-heading h2 {
    color: #5B0018;
    font-size: 20px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    padding: 22px;
    border: 1px solid transparent;
    border-top: 4px solid;
    border-radius: 14px;
    box-shadow: 0 4px 16px rgba(0,0,0,.06);
}

.stats-grid .stat-card:nth-child(1) {
    background: #EFF6FF;
    border-color: #93C5FD;
    border-top-color: #2563EB;
}

.stats-grid .stat-card:nth-child(2) {
    background: #FAF5FF;
    border-color: #C4B5FD;
    border-top-color: #7C3AED;
}

.stats-grid .stat-card:nth-child(3) {
    background: #ECFDF5;
    border-color: #6EE7B7;
    border-top-color: #059669;
}

.stats-grid .stat-card:nth-child(4) {
    background: #FFF7ED;
    border-color: #FDBA74;
    border-top-color: #EA580C;
}

.stat-icon {
    font-size: 24px;
    margin-bottom: 15px;
}

.stat-card p {
    color: #4B5563;
    font-size: 13px;
    margin-bottom: 9px;
}

.stat-card h3 {
    font-size: 28px;
}

.quick-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.quick-card {
    display: block;
    background: white;
    color: #333;
    text-decoration: none;
    padding: 23px;
    border-radius: 14px;
    border-top: 4px solid #800000;
    box-shadow: 0 4px 16px rgba(0,0,0,.04);
    transition: .2s;
}

.quick-card:hover {
    transform: translateY(-3px);
}

.quick-icon {
    font-size: 27px;
    margin-bottom: 15px;
}

.quick-card h3 {
    color: #5B0018;
    font-size: 16px;
    margin-bottom: 10px;
}

.quick-card p {
    color: #777;
    font-size: 13px;
    line-height: 1.6;
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
    min-width: 750px;
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

td strong {
    color: #4B0015;
}

.badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    background: #F7E9ED;
    color: #800000;
    font-size: 12px;
}

.empty {
    text-align: center;
    padding: 25px;
    color: #777;
}

.approval-notice {
    padding: 13px 16px;
    margin-bottom: 16px;
    background: #F7E9ED;
    color: #800000;
    border: 1px solid #E7C4CE;
    border-radius: 9px;
    font-size: 14px;
}

.approval-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 27px;
    height: 27px;
    padding: 0 8px;
    margin-left: 8px;
    background: #800000;
    color: white;
    border-radius: 20px;
    font-size: 12px;
}

.status-badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    text-transform: capitalize;
}

.status-pending {
    background: #FEF3C7;
    color: #92400E;
}

.status-approved {
    background: #D1FAE5;
    color: #065F46;
}

.status-rejected {
    background: #FEE2E2;
    color: #991B1B;
}

.approval-actions {
    display: flex;
    gap: 7px;
    align-items: center;
    flex-wrap: wrap;
}

.approval-actions form {
    display: inline;
}

.btn-approve {
    background: #15803D;
}

.btn-reject {
    background: #B91C1C;
}

.btn-edit {
    background: #2563EB;
}

.btn-delete {
    background: #B91C1C;
}

.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.55);
    z-index: 1000;
    justify-content: center;
    align-items: center;
    padding: 15px;
}

.modal-content {
    background: white;
    width: 100%;
    max-width: 400px;
    padding: 25px;
    border-radius: 12px;
}

.modal-content h2 {
    color: #800000;
    margin-bottom: 10px;
}

.modal-content p {
    color: #777;
    margin-bottom: 20px;
    font-size: 14px;
}

.modal-content label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
}

.modal-content select {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 7px;
    margin-bottom: 20px;
}

.modal-buttons {
    display: flex;
    gap: 10px;
}

.modal-buttons button {
    flex: 1;
}

.btn-cancel {
    background: #777;
}

@media(max-width:1100px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .quick-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media(max-width:760px) {
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

    .stats-grid,
    .quick-grid {
        grid-template-columns: 1fr;
    }

    .welcome {
        padding: 24px;
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

    <a href="admin_dashboard.php" class="nav-link active">▦ Admin Dashboard</a>
    <a href="events.php" class="nav-link">▣ School Events</a>

    <div class="nav-title">Management</div>

    <a href="admin.php" class="nav-link">＋ Add / Manage Events</a>
    <a href="manage_attendance.php" class="nav-link">✓ Manage Attendance</a>
    <a href="attendance_report.php" class="nav-link">▤ Attendance Reports</a>
    <a href="manage_users.php" class="nav-link">♙ Manage Users</a>

    <div class="nav-title">Account</div>

    <a href="logout.php" class="nav-link">↪ Logout</a>

</aside>

<main class="main">

    <header class="topbar">
        <div>
            <h1>Admin Dashboard</h1>
            <p>Manage school events and monitor student participation.</p>
        </div>

        <div class="profile">
            Welcome, <strong><?= e($name) ?></strong>
        </div>
    </header>

    <section class="welcome">
        <h2>Welcome back, <?= e($name) ?>!</h2>

        <p>
            Manage school events, review registrations, and monitor attendance
            using your School Event System administrator dashboard.
        </p>

        <a href="admin.php" class="btn">＋ Manage School Events</a>
    </section>

    <div class="section-heading">
        <h2>System Overview</h2>
    </div>

    <section class="stats-grid">

        <div class="stat-card">
            <div class="stat-icon">🎓</div>
            <p>Total School Events</p>
            <h3><?= $totalEvents ?></h3>
        </div>

        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <p>Registered Students</p>
            <h3><?= $totalStudents ?></h3>
        </div>

        <div class="stat-card">
            <div class="stat-icon">✓</div>
            <p>Event Registrations</p>
            <h3><?= $totalRegistrations ?></h3>
        </div>

        <div class="stat-card">
            <div class="stat-icon">♙</div>
            <p>Total User Accounts</p>
            <h3><?= $totalUsers ?></h3>
        </div>

    </section>

    <div class="section-heading">
        <h2>Quick Actions</h2>
    </div>

    <section class="quick-grid">

        <a href="admin.php" class="quick-card">
            <div class="quick-icon">📅</div>
            <h3>Manage School Events</h3>
            <p>Add, edit, and manage your school's events.</p>
        </a>

        <a href="manage_attendance.php" class="quick-card">
            <div class="quick-icon">✅</div>
            <h3>Manage Attendance</h3>
            <p>Record and update student attendance.</p>
        </a>

        <a href="attendance_report.php" class="quick-card">
            <div class="quick-icon">📊</div>
            <h3>Attendance Reports</h3>
            <p>Review event attendance summaries.</p>
        </a>

        <a href="manage_users.php" class="quick-card">
            <div class="quick-icon">👤</div>
            <h3>Manage Users</h3>
            <p>View student and administrator accounts.</p>
        </a>

        <a href="events.php" class="quick-card">
            <div class="quick-icon">🎉</div>
            <h3>View School Events</h3>
            <p>Browse the available school activities.</p>
        </a>

        <a href="logout.php" class="quick-card">
            <div class="quick-icon">↪</div>
            <h3>Logout</h3>
            <p>Securely end your administrator session.</p>
        </a>

    </section>

    <div class="section-heading">
        <h2>
            Student Account Approvals
            <span class="approval-count"><?= $pendingCount ?></span>
        </h2>
    </div>

    <section class="table-card">

        <?php if ($approvalMessage !== ''): ?>
            <div class="approval-notice">
                <?= e($approvalMessage) ?>
            </div>
        <?php endif; ?>

        <p style="color:#777;font-size:13px;margin-bottom:18px">
            Review registered student accounts. You can approve, reject,
            edit, or delete student accounts.
        </p>

        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Email Address</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

            <?php if (!empty($studentAccounts)): ?>

                <?php foreach ($studentAccounts as $student): ?>

                    <?php
                    $status = $student['status'] ?? 'pending';

                    $safeStatus = in_array(
                        $status,
                        ['pending', 'approved', 'rejected'],
                        true
                    ) ? $status : 'pending';
                    ?>

                    <tr>
                        <td>
                            <strong><?= e($student['full_name']) ?></strong>
                        </td>

                        <td><?= e($student['email']) ?></td>

                        <td>
                            <span class="status-badge status-<?= e($safeStatus) ?>">
                                <?= e($safeStatus) ?>
                            </span>
                        </td>

                        <td>
                            <div class="approval-actions">

                                <?php if ($safeStatus === 'pending'): ?>

                                    <form method="POST">
                                        <input type="hidden" name="csrf_token"
                                            value="<?= e($_SESSION['approval_csrf']) ?>">

                                        <input type="hidden" name="user_id"
                                            value="<?= (int)$student['user_id'] ?>">

                                        <input type="hidden" name="account_action"
                                            value="approve">

                                        <button type="submit" class="btn btn-approve">
                                            Approve
                                        </button>
                                    </form>

                                    <form method="POST"
                                        onsubmit="return confirm('Are you sure you want to reject this student account?');">

                                        <input type="hidden" name="csrf_token"
                                            value="<?= e($_SESSION['approval_csrf']) ?>">

                                        <input type="hidden" name="user_id"
                                            value="<?= (int)$student['user_id'] ?>">

                                        <input type="hidden" name="account_action"
                                            value="reject">

                                        <button type="submit" class="btn btn-reject">
                                            Reject
                                        </button>
                                    </form>

                                <?php endif; ?>

                                <button
                                    type="button"
                                    class="btn btn-edit"
                                    onclick="openStatusModal(
                                        <?= (int)$student['user_id'] ?>,
                                        <?= e(json_encode($safeStatus)) ?>,
                                        <?= e(json_encode($student['full_name'])) ?>
                                    )">
                                    Edit
                                </button>

                                <form method="POST"
                                    onsubmit="return confirm('Are you sure you want to delete this student account? This action cannot be undone.');">

                                    <input type="hidden" name="csrf_token"
                                        value="<?= e($_SESSION['approval_csrf']) ?>">

                                    <input type="hidden" name="user_id"
                                        value="<?= (int)$student['user_id'] ?>">

                                    <input type="hidden" name="account_action"
                                        value="delete">

                                    <button type="submit" class="btn btn-delete">
                                        Delete
                                    </button>
                                </form>

                            </div>
                        </td>
                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="4" class="empty">
                        No student accounts found.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>
        </table>

    </section>

    <div class="section-heading">
        <h2>Latest School Events</h2>
        <a href="events.php" class="btn">View All Events</a>
    </div>

    <section class="table-card">

        <table>
            <thead>
                <tr>
                    <th>Event Name</th>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Category</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

            <?php if (!empty($latestEvents)): ?>

                <?php foreach ($latestEvents as $event): ?>

                    <tr>
                        <td>
                            <strong><?= e($event['title']) ?></strong>
                        </td>

                        <td><?= e($event['event_date']) ?></td>

                        <td><?= e($event['location']) ?></td>

                        <td>
                            <span class="badge">
                                <?= e($event['category'] ?: 'General') ?>
                            </span>
                        </td>

                        <td>
                            <a class="btn"
                               href="edit_event.php?id=<?= (int)$event['event_id'] ?>">
                                Edit
                            </a>
                        </td>
                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="5" class="empty">
                        No school events yet. Add your first event through
                        <a href="admin.php">Manage Events</a>.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>
        </table>

    </section>

</main>

<!-- EDIT STATUS MODAL -->
<div class="modal" id="statusModal">

    <div class="modal-content">

        <h2>Edit Account Status</h2>
        <p id="studentModalName"></p>

        <form method="POST">

            <input type="hidden" name="csrf_token"
                value="<?= e($_SESSION['approval_csrf']) ?>">

            <input type="hidden" name="account_action"
                value="edit_status">

            <input type="hidden" name="user_id"
                id="modalUserId">

            <label for="modalStatus">Select New Status</label>

            <select name="status" id="modalStatus" required>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>

            <div class="modal-buttons">

                <button type="submit" class="btn">
                    Save Changes
                </button>

                <button
                    type="button"
                    class="btn btn-cancel"
                    onclick="closeStatusModal()">
                    Cancel
                </button>

            </div>

        </form>

    </div>

</div>

<script>
function openStatusModal(userId, status, studentName) {
    document.getElementById('modalUserId').value = userId;
    document.getElementById('modalStatus').value = status;
    document.getElementById('studentModalName').textContent = studentName;
    document.getElementById('statusModal').style.display = 'flex';
}

function closeStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
}

window.addEventListener('click', function(event) {
    const modal = document.getElementById('statusModal');

    if (event.target === modal) {
        closeStatusModal();
    }
});
</script>

</body>
</html>
