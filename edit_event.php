
<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$eventId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$eventId || $eventId < 1) {
    header("Location: admin.php");
    exit;
}

$message = '';
$messageType = 'error';

/* FETCH EVENT */
$stmt = $pdo->prepare(
    "SELECT event_id, title, description, event_date, location, category
     FROM events
     WHERE event_id = ?
     LIMIT 1"
);

$stmt->execute([$eventId]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header("Location: admin.php?notfound=1");
    exit;
}

/* UPDATE EVENT */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_event'])) {

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $eventDate = trim($_POST['event_date'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $category = trim($_POST['category'] ?? '');

    if (
        $title === '' ||
        $eventDate === '' ||
        $location === '' ||
        $category === ''
    ) {
        $message = 'Please complete all required fields.';

    } elseif (mb_strlen($title) > 150 || mb_strlen($location) > 150) {
        $message = 'Title and location must not exceed 150 characters.';

    } else {
        $date = DateTime::createFromFormat('Y-m-d', $eventDate);

        if (!$date || $date->format('Y-m-d') !== $eventDate) {
            $message = 'Please enter a valid event date.';
        } else {
            try {
                $update = $pdo->prepare(
                    "UPDATE events
                     SET title = ?,
                         description = ?,
                         event_date = ?,
                         location = ?,
                         category = ?
                     WHERE event_id = ?"
                );

                $update->execute([
                    $title,
                    $description,
                    $eventDate,
                    $location,
                    $category,
                    $eventId
                ]);

                header("Location: admin.php?updated=1");
                exit;

            } catch (PDOException $error) {
                $message = 'Unable to update the event. Please try again.';
            }
        }
    }

    $event['title'] = $title;
    $event['description'] = $description;
    $event['event_date'] = $eventDate;
    $event['location'] = $location;
    $event['category'] = $category;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Edit School Event | School Event System</title>

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
    position: fixed;
    top: 0;
    left: 0;
    padding: 28px 18px;
    background: linear-gradient(180deg, #800000, #5B0018);
    color: white;
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

.card {
    background: white;
    padding: 28px;
    border-radius: 14px;
    box-shadow: 0 4px 16px rgba(0,0,0,.04);
    max-width: 900px;
}

.card-heading {
    color: #5B0018;
    font-size: 21px;
    margin-bottom: 24px;
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
}

input:focus,
textarea:focus,
select:focus {
    border-color: #800000;
    box-shadow: 0 0 0 3px rgba(128,0,0,.08);
}

textarea {
    min-height: 120px;
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

.alert {
    padding: 14px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    background: #FBEAEC;
    color: #800000;
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

    .form-grid {
        grid-template-columns: 1fr;
        gap: 0;
    }

    .form-group.full {
        grid-column: auto;
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
    <a href="admin.php" class="nav-link active">＋ Manage Events</a>
    <a href="manage_attendance.php" class="nav-link">✓ Manage Attendance</a>
    <a href="attendance_report.php" class="nav-link">▤ Attendance Reports</a>
    <a href="manage_users.php" class="nav-link">♙ Manage Users</a>

    <div class="nav-title">Account</div>
    <a href="logout.php" class="nav-link">↪ Logout</a>

</aside>

<main class="main">

    <header class="topbar">
        <h1>Edit School Event</h1>
        <p>Update the information of your selected school event.</p>
    </header>

    <section class="card">

        <h2 class="card-heading">Event Information</h2>

        <?php if ($message !== ''): ?>
            <div class="alert">
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="edit_event.php?id=<?= (int)$eventId ?>">

            <div class="form-grid">

                <div class="form-group full">
                    <label for="title">Event Title *</label>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        maxlength="150"
                        value="<?= e($event['title']) ?>"
                        required
                    >
                </div>

                <div class="form-group full">
                    <label for="description">Description</label>
                    <textarea
                        id="description"
                        name="description"
                    ><?= e($event['description']) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="event_date">Event Date *</label>
                    <input
                        type="date"
                        id="event_date"
                        name="event_date"
                        value="<?= e($event['event_date']) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="location">Location *</label>
                    <input
                        type="text"
                        id="location"
                        name="location"
                        maxlength="150"
                        value="<?= e($event['location']) ?>"
                        required
                    >
                </div>

                <div class="form-group full">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <option value="">Select a category</option>
                        <option value="Academic" <?= $event['category'] === 'Academic' ? 'selected' : '' ?>>Academic</option>
                        <option value="Sports" <?= $event['category'] === 'Sports' ? 'selected' : '' ?>>Sports</option>
                        <option value="Cultural" <?= $event['category'] === 'Cultural' ? 'selected' : '' ?>>Cultural</option>
                        <option value="Seminar" <?= $event['category'] === 'Seminar' ? 'selected' : '' ?>>Seminar</option>
                        <option value="School Celebration" <?= $event['category'] === 'School Celebration' ? 'selected' : '' ?>>School Celebration</option>
                        <option value="Other" <?= $event['category'] === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>

            </div>

            <button type="submit" name="update_event" class="btn">
                Save Changes
            </button>

            <a href="admin.php" class="btn btn-light">
                Cancel
            </a>

        </form>

    </section>

</main>

</body>
</html>
