<?php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['organizer', 'admin'])) {
    header('Location: login.php');
    exit();
}
$conn = mysqli_connect('localhost', 'root', '', 'event_registration');
if (!$conn) {
    die('Connection failed: ' . mysqli_connect_error());
}
$event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($event_id <= 0) {
    die('Invalid event ID.');
}
// Fetch event
$event = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM events WHERE id=$event_id"));
if (!$event) {
    die('Event not found.');
}
// Permission check
if ($_SESSION['role'] == 'organizer' && $event['organizer_id'] != $_SESSION['user_id']) {
    die('You do not have permission to view RSVPs for this event.');
}
// Get RSVPs
$rsvps = mysqli_query($conn, "SELECT u.name, u.email, u.university, u.department FROM rsvps r JOIN users u ON r.user_id = u.id WHERE r.event_id = $event_id");
?>
<!DOCTYPE html>
<html>
<head>
    <title>RSVP List</title>
    <link rel="stylesheet" href="events_css.css">
</head>
<body>
<h2>RSVP List for: <?php echo htmlspecialchars($event['name']); ?></h2>
<p><a href="<?php echo ($_SESSION['role'] == 'admin') ? 'admin.php' : 'organizer.php'; ?>">Back to Dashboard</a> | <a href="logout.php">Logout</a></p>
<table border="1">
<tr><th>Name</th><th>Email</th><th>University</th><th>Department</th></tr>
<?php while($u = mysqli_fetch_assoc($rsvps)) {
    echo '<tr>';
    echo '<td>'.htmlspecialchars($u['name']).'</td>';
    echo '<td>'.htmlspecialchars($u['email']).'</td>';
    echo '<td>'.htmlspecialchars($u['university']).'</td>';
    echo '<td>'.htmlspecialchars($u['department']).'</td>';
    echo '</tr>';
} ?>
</table>
</body>
</html> 