<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'organizer') {
    header('Location: login.php');
    exit();
}
$conn = mysqli_connect('localhost', 'root', '', 'event_registration');
if (!$conn) {
    die('Connection failed: ' . mysqli_connect_error());
}
$organizer_id = $_SESSION['user_id'];
// Get all events by this organizer
$sql = "SELECT e.*, p.name AS place_name, (SELECT COUNT(*) FROM rsvps WHERE event_id = e.id) AS rsvp_count FROM events e LEFT JOIN places p ON e.place_id = p.id WHERE e.organizer_id = $organizer_id ORDER BY e.date DESC, e.start_time DESC";
$events = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Organizer Dashboard</title>
    <link rel="stylesheet" href="organizer_css.css">
</head>
<body>
<div style="text-align:center;"><h2>Organizer Dashboard</h2></div>
<p><a href="logout.php">Logout</a></p>
<p><a href="create_event.php">Create New Event</a></p>
<div style="text-align:center;"><h3>Your Events</h3></div>
<table border="1">
<tr><th>Name</th><th>Date</th><th>Place</th><th>RSVPs</th><th>Action</th></tr>
<?php while($e = mysqli_fetch_assoc($events)) {
    echo '<tr><td>'.$e['name'].'</td><td>'.$e['date'].' '.$e['start_time'].'-'.$e['end_time'].'</td><td>'.$e['place_name'].'</td><td>'.$e['rsvp_count'].'</td>';
    echo '<td><a href="edit_event.php?id='.$e['id'].'">Edit</a> | <a href="view_rsvps.php?id='.$e['id'].'">View RSVPs</a></td></tr>';
} ?>
</table>
</body>
</html> 