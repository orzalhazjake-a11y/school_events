
<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$name = $_SESSION['full_name'] ?? 'Administrator';
$selectedEvent = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
$message = '';

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

try {
    $eventQuery = $pdo->query(
        "SELECT event_id, title, event_date
         FROM events
         ORDER BY event_date DESC"
    );

    $events = $eventQuery->fetchAll(PDO::FETCH_ASSOC);

    $students = [];
    $totalStudents = 0;
    $selectedEventTitle = '';

    if ($selectedEvent && $selectedEvent > 0) {
        $checkEvent = $pdo->prepare(
            "SELECT title FROM events WHERE event_id = ?"
        );
        $checkEvent->execute([$selectedEvent]);
        $selectedEventData = $checkEvent->fetch(PDO::FETCH_ASSOC);

        if ($selectedEventData) {
            $selectedEventTitle = $selectedEventData['title'];

            $stmt = $pdo->prepare(
                "SELECT
                    er.registration_id,
                    u.full_name,
                    u.email,
                    ev.title,
                    er.status
                 FROM event_registrations er
                 INNER JOIN users u
                    ON er.user_id = u.user_id
                 INNER JOIN events ev
                    ON er.event_id = ev.event_id
                 WHERE er.event_id = ?
                 ORDER BY u.full_name ASC"
            );

            $stmt->execute([$selectedEvent]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $totalStudents = count($students);
        } else {
            $selectedEvent = null;
            $message = 'The selected event does not exist.';
        }
    }

    if (isset($_GET['saved'])) {
        $message = 'Attendance saved successfully!';
    }

} catch (PDOException $error) {
    die("Unable to load attendance records. Please check your database.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Attendance | School Event System</title>

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
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 25px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 14px;
    border-top: 4px solid #800000;
    box-shadow: 0 4px 16px rgba(0,0,0,.04);
}

.stat-card p {
    color: #777;
    font-size: 13px;
    margin-bottom: 9px;
}

.stat-card h3 {
    color: #5B0018;
    font-size: 27px;
}

.card {
    background: white;
    padding: 25px;
    border-radius: 14px;
    box-shadow: 0 4px 16px rgba(0,0,0,.04);
    margin-bottom: 25px;
}

.card h2 {
    color: #5B0018;
    font-size: 21px;
    margin-bottom: 20px;
}

.select-form {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 13px;
}

.select-form label {
    font-size: 14px;
    font-weight: bold;
    color: #4B0015;
}

select {
    min-width: 240px;
    max-width: 100%;
    padding: 13px 14px;
    border: 1px solid #E2D6D9;
    border-radius: 8px;
    background: white;
    font-size: 14px;
    outline: none;
}

select:focus {
    border-color: #800000;
    box-shadow: 0 0 0 3px rgba(128,0,0,.08);
}

.btn {
    display: inline-block;
    padding: 12px 18px;
    border: none;
    border-radius: 8px;
    background: #800000;
    color: white;
    text-decoration: none;
    font-size: 13px;
    font-weight: bold;
    cursor: pointer;
}

.btn:hover {
    background: #5B0018;
}

.alert {
    padding: 14px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    background: #E8F5E9;
    color: #216B35;
}

.alert.error {
    background: #FBEAEC;
    color: #800000;
}

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 650px;
    border-collapse: collapse;
}

th, td {
    padding: 14px 12px;
    text-align: left;
    border-bottom: 1px solid #F0E8EA;
    font-size: 13px;
}

th {
    background: #800000;
    color: white;
}

td {
    color: #555;
}

tbody tr:hover {
    background: #FCF8F9;
}

.status {
    display: inline-block;
    padding: 6px 11px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: bold;
    text-transform: capitalize;
}

.status.approved {
    background: #E8F5E9;
    color: #216B35;
}

.status.pending {
    background: #FFF4D6;
    color: #8A6200;
}

.status.rejected {
    background: #FBEAEC;
    color: #800000;
}

.empty {
    text-align: center;
    padding: 25px;
    color: #777;
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
        align-items: flex-start;
        flex-direction: column;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .select-form {
        align-items: stretch;
        flex-direction: column;
    }

    select {
        width: 100%;
    }

    .card {
        padding: 20px;
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
    <a href="manage_attendance.php" class="nav-link active">✓ Manage Attendance</a>
    <a href="attendance_report.php" class="nav-link">▤ Attendance Reports</a>
    <a href="manage_users.php" class="nav-link">♙ Manage Users</a>

    <div class="nav-title">Account</div>
    <a href="logout.php" class="nav-link">↪ Logout</a>
</aside>

<main class="main">

    <header class="topbar">
        <div>
            <h1>Manage Attendance</h1>
            <p>Select an event and view its registered participants.</p>
        </div>

        <div class="profile">
            Welcome, <strong><?= e($name) ?></strong>
        </div>
    </header>

    <section class="welcome">
        <h2>Event Participant Management</h2>
        <p>
            Choose a school event to view the students who registered
            for it. This page displays participant totals and registration
            details without Present or Absent controls.
        </p>
    </section>

    <?php if ($message !== ''): ?>
        <div class="alert <?= isset($_GET['saved']) ? '' : 'error' ?>">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <section class="card">
        <h2>Select School Event</h2>

        <form method="GET" action="manage_attendance.php" class="select-form">
            <label for="event_id">Choose Event:</label>

            <select name="event_id" id="event_id" required>
                <option value="">-- Choose Event --</option>

                <?php foreach ($events as $event): ?>
                    <option
                        value="<?= (int)$event['event_id'] ?>"
                        <?= $selectedEvent == $event['event_id'] ? 'selected' : '' ?>
                    >
                        <?= e($event['title']) ?> — <?= e($event['event_date']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn">View Participants</button>
        </form>
    </section>

    <?php if ($selectedEvent && $selectedEventTitle !== ''): ?>

        <section class="stats-grid">
            <div class="stat-card">
                <p>Total Participants</p>
                <h3><?= $totalStudents ?></h3>
            </div>

            <div class="stat-card">
                <p>Selected Event ID</p>
                <h3>#<?= (int)$selectedEvent ?></h3>
            </div>
        </section>

        <section class="card">
            <h2>Registered Participants</h2>

            <?php if ($totalStudents > 0): ?>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Registration ID</th>
                                <th>Participant Name</th>
                                <th>Email Address</th>
                                <th>Event</th>
                                <th>Registration Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?= (int)$student['registration_id'] ?></td>
                                    <td><?= e($student['full_name']) ?></td>
                                    <td><?= e($student['email']) ?></td>
                                    <td><?= e($student['title']) ?></td>
                                    <td>
                                        <span class="status <?= e(strtolower($student['status'] ?? 'pending')) ?>">
                                            <?= e($student['status'] ?? 'Pending') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>
                <p class="empty">
                    Wala pang registered participants para sa event na ito.
                </p>
            <?php endif; ?>
        </section>

    <?php endif; ?>

</main>

</body>
</html>
