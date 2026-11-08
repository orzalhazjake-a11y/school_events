
<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$name = $_SESSION['full_name'] ?? 'Administrator';
$message = '';
$messageType = 'success';

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* HANDLE NOTIFICATIONS */
if (isset($_GET['success'])) {
    $message = 'School event added successfully!';
} elseif (isset($_GET['updated'])) {
    $message = 'School event updated successfully!';
} elseif (isset($_GET['deleted'])) {
    $message = 'School event deleted successfully!';
} elseif (isset($_GET['error'])) {
    $messageType = 'error';

    switch ($_GET['error']) {
        case 'invalid_id':
            $message = 'Invalid event ID.';
            break;
        case 'not_found':
            $message = 'Event not found.';
            break;
        case 'delete_failed':
            $message = 'Unable to delete event.';
            break;
        default:
            $message = 'Something went wrong.';
    }
}

/* ADD EVENT */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_date = trim($_POST['event_date'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $category = trim($_POST['category'] ?? '');

    if (
        $title === '' ||
        $event_date === '' ||
        $location === '' ||
        $category === ''
    ) {
        $message = 'Please fill in all required fields.';
        $messageType = 'error';

    } elseif (mb_strlen($title) > 150 || mb_strlen($location) > 150) {
        $message = 'Title and location must not exceed 150 characters.';
        $messageType = 'error';

    } else {
        $date = DateTime::createFromFormat('Y-m-d', $event_date);

        if (!$date || $date->format('Y-m-d') !== $event_date) {
            $message = 'Please enter a valid event date.';
            $messageType = 'error';
        } else {
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO events
                    (title, description, event_date, location, category)
                    VALUES (?, ?, ?, ?, ?)"
                );

                $stmt->execute([
                    $title,
                    $description,
                    $event_date,
                    $location,
                    $category
                ]);

                header("Location: admin.php?success=1");
                exit;

            } catch (PDOException $error) {
                $message = 'Unable to add event. Please check your database.';
                $messageType = 'error';
            }
        }
    }
}

/* TOTAL EVENTS */
$totalEvents = (int)$pdo->query(
    "SELECT COUNT(*) FROM events"
)->fetchColumn();

/* GET EVENTS */
$stmt = $pdo->query(
    "SELECT event_id, title, description, event_date,
            location, category
     FROM events
     ORDER BY event_date ASC"
);

$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Manage School Events | School Event System</title>

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
    font-size: 25px;
    margin-bottom: 10px;
}

.welcome p {
    color: #F8E9EC;
    line-height: 1.7;
}

.stats-card {
    background: white;
    padding: 20px 23px;
    border-radius: 14px;
    border-top: 4px solid #800000;
    box-shadow: 0 4px 16px rgba(0,0,0,.04);
    margin-bottom: 25px;
}

.stats-card p {
    color: #777;
    font-size: 13px;
    margin-bottom: 8px;
}

.stats-card h3 {
    color: #5B0018;
    font-size: 28px;
}

.card {
    background: white;
    padding: 26px;
    border-radius: 14px;
    box-shadow: 0 4px 16px rgba(0,0,0,.04);
    margin-bottom: 28px;
}

.card-heading {
    color: #5B0018;
    font-size: 21px;
    margin-bottom: 22px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
}

.form-group {
    margin-bottom: 18px;
}

.form-group.full {
    grid-column: 1 / -1;
}

label {
    display: block;
    color: #4B0015;
    font-size: 14px;
    font-weight: bold;
    margin-bottom: 9px;
}

input,
textarea,
select {
    width: 100%;
    padding: 13px 14px;
    border: 1px solid #E2D6D9;
    border-radius: 8px;
    background: #FFFEFE;
    color: #333;
    font-family: Arial, sans-serif;
    font-size: 14px;
    outline: none;
    transition: .2s;
}

input:focus,
textarea:focus,
select:focus {
    border-color: #800000;
    box-shadow: 0 0 0 3px rgba(128,0,0,.08);
}

textarea {
    min-height: 115px;
    resize: vertical;
}

.btn {
    display: inline-block;
    padding: 12px 18px;
    border: none;
    border-radius: 8px;
    background: #800000;
    color: white;
    text-decoration: none;
    text-align: center;
    font-size: 13px;
    font-weight: bold;
    cursor: pointer;
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

.btn-delete {
    background: #800000;
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
    min-width: 700px;
    border-collapse: collapse;
}

th,
td {
    text-align: left;
    padding: 15px 12px;
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

.event-title {
    color: #4B0015;
    font-weight: bold;
}

.category-badge {
    display: inline-block;
    background: #F7E9ED;
    color: #800000;
    border-radius: 20px;
    padding: 6px 10px;
    font-size: 12px;
}

.empty {
    text-align: center;
    padding: 25px;
    color: #777;
}

.action-buttons {
    display: flex;
    gap: 7px;
    align-items: center;
    flex-wrap: wrap;
}

.action-buttons form {
    margin: 0;
}

@media (max-width: 850px) {
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

    .form-grid {
        grid-template-columns: 1fr;
        gap: 0;
    }

    .form-group.full {
        grid-column: auto;
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
    <a href="admin.php" class="nav-link active">＋ Add / Manage Events</a>
    <a href="manage_attendance.php" class="nav-link">✓ Manage Attendance</a>
    <a href="attendance_report.php" class="nav-link">▤ Attendance Reports</a>
    <a href="manage_users.php" class="nav-link">♙ Manage Users</a>

    <div class="nav-title">Account</div>
    <a href="logout.php" class="nav-link">↪ Logout</a>

</aside>

<main class="main">

    <header class="topbar">
        <div>
            <h1>Manage School Events</h1>
            <p>Create and organize activities for your school.</p>
        </div>

        <div class="profile">
            Welcome, <strong><?= e($name) ?></strong>
        </div>
    </header>

    <section class="welcome">
        <h2>Add a New School Event</h2>
        <p>
            Enter the event information below. Once saved,
            the event will appear on the School Events page.
        </p>
    </section>

    <?php if ($message !== ''): ?>
        <div class="alert <?= $messageType === 'error' ? 'error' : '' ?>">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <section class="stats-card">
        <p>Total School Events</p>
        <h3><?= $totalEvents ?></h3>
    </section>

    <section class="card">

        <h2 class="card-heading">Event Information</h2>

        <form method="POST" action="admin.php">

            <div class="form-grid">

                <div class="form-group full">
                    <label for="title">Event Title *</label>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        placeholder="Enter event title"
                        maxlength="150"
                        required
                    >
                </div>

                <div class="form-group full">
                    <label for="description">Description</label>
                    <textarea
                        id="description"
                        name="description"
                        placeholder="Describe the school event..."
                    ></textarea>
                </div>

                <div class="form-group">
                    <label for="event_date">Event Date *</label>
                    <input
                        type="date"
                        id="event_date"
                        name="event_date"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="location">Location *</label>
                    <input
                        type="text"
                        id="location"
                        name="location"
                        placeholder="Enter event location"
                        maxlength="150"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <option value="">Select a category</option>
                        <option value="Academic">Academic</option>
                        <option value="Sports">Sports</option>
                        <option value="Cultural">Cultural</option>
                        <option value="Seminar">Seminar</option>
                        <option value="School Celebration">School Celebration</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

            </div>

            <button type="submit" name="add_event" class="btn">
                ＋ Save School Event
            </button>

            <a href="events.php" class="btn btn-light">
                View School Events
            </a>

        </form>

    </section>

    <section class="card">

        <h2 class="card-heading">Existing School Events</h2>

        <div class="table-wrapper">

            <table>
                <thead>
                    <tr>
                        <th>Event Title</th>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Category</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (!empty($events)): ?>

                    <?php foreach ($events as $event): ?>

                        <tr>

                            <td class="event-title">
                                <?= e($event['title']) ?>
                            </td>

                            <td>
                                <?= e($event['event_date']) ?>
                            </td>

                            <td>
                                <?= e($event['location']) ?>
                            </td>

                            <td>
                                <span class="category-badge">
                                    <?= e($event['category'] ?: 'General') ?>
                                </span>
                            </td>

                            <td>
                                <div class="action-buttons">

                                    <a
                                        class="btn btn-light"
                                        href="edit_event.php?id=<?= (int)$event['event_id'] ?>"
                                    >Edit</a>

                                    <form
                                        method="POST"
                                        action="delete_event.php"
                                        onsubmit="return confirm('Are you sure you want to delete this event?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int)$event['event_id'] ?>"
                                        >

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
                        <td colspan="5" class="empty">
                            No school events have been added yet.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>

        </div>

    </section>

</main>

</body>
</html>
