
<?php
session_start();
require_once 'config/database.php';

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    header("Location: login.php");
    exit();
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_date = trim($_POST['event_date'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $organizer = trim($_POST['organizer'] ?? '');
    $category = trim($_POST['category'] ?? '');

    if (
        $title === '' ||
        $event_date === '' ||
        $location === '' ||
        $organizer === '' ||
        $category === ''
    ) {
        $message = "Please fill in all required fields.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO events
            (title, description, event_date, location, organizer, category)
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "ssssss",
            $title,
            $description,
            $event_date,
            $location,
            $organizer,
            $category
        );

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: admin.php?added=1");
            exit();
        } else {
            $message = "Unable to add event. Please check the data.";
        }

        $stmt->close();
    }
}

if (isset($_GET['added'])) {
    $message = "Event added successfully!";
}

$events = $conn->query(
    "SELECT event_id, title, event_date, location, category
     FROM events
     ORDER BY event_date DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Panel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            margin: 25px;
            color: #1e293b;
        }
        .container {
            max-width: 1100px;
            margin: auto;
        }
        .panel {
            background: white;
            padding: 22px;
            margin-bottom: 22px;
            border-radius: 10px;
        }
        input, textarea, button {
            width: 100%;
            padding: 10px;
            margin: 7px 0 14px;
            box-sizing: border-box;
        }
        button {
            background: #1d4ed8;
            color: white;
            border: 0;
            cursor: pointer;
            border-radius: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        th {
            background: #1d4ed8;
            color: white;
        }
        a { color: #1d4ed8; }
        .message {
            background: #dcfce7;
            padding: 12px;
            border-radius: 5px;
        }
        .nav {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container">

    <h1>Admin Panel</h1>

    <div class="nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="manage_users.php">Manage Users</a>
        <a href="manage_attendance.php">Manage Attendance</a>
        <a href="attendance_report.php">Attendance Report</a>
        <a href="logout.php">Logout</a>
    </div>

    <?php if ($message !== ''): ?>
        <p class="message">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <div class="panel">
        <h2>Add New Event</h2>

        <form method="POST">
            <label>Event Title *</label>
            <input type="text" name="title" required>

            <label>Description</label>
            <textarea name="description" rows="4"></textarea>

            <label>Event Date and Time *</label>
            <input type="datetime-local" name="event_date" required>

            <label>Location *</label>
            <input type="text" name="location" required>

            <label>Organizer *</label>
            <input type="text" name="organizer" required>

            <label>Category *</label>
            <input type="text" name="category" placeholder="e.g. Sports, Academic, Cultural" required>

            <button type="submit">Add Event</button>
        </form>
    </div>

    <div class="panel">
        <h2>Existing Events</h2>

        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Category</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($events && $events->num_rows > 0): ?>
                <?php while ($event = $events->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                        <td><?php echo htmlspecialchars($event['event_date']); ?></td>
                        <td><?php echo htmlspecialchars($event['location']); ?></td>
                        <td><?php echo htmlspecialchars($event['category']); ?></td>
                        <td>
                            <a href="edit_event.php?id=<?php echo (int)$event['event_id']; ?>">Edit</a>
                            |
                            <a href="delete_event.php?id=<?php echo (int)$event['event_id']; ?>">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No events added yet.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>