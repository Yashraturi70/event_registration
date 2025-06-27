<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'user') {
    header('Location: login.php');
    exit();
}
$conn = mysqli_connect('localhost', 'root', '', 'event_registration');
if (!$conn) {
    die('Connection failed: ' . mysqli_connect_error());
}
$user_id = $_SESSION['user_id'];
// Get user details
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT university, department FROM users WHERE id=$user_id"));
// Get places for filter
$places = mysqli_query($conn, "SELECT id, name FROM places");
// Filters
$where = "WHERE 1=1 ";
if (!empty($_GET['university'])) {
    $univ = mysqli_real_escape_string($conn, $_GET['university']);
    $where .= "AND (eligibility_type='all' OR (eligibility_type='university' AND eligibility_value='$univ')) ";
}
if (!empty($_GET['department'])) {
    $dept = mysqli_real_escape_string($conn, $_GET['department']);
    $where .= "AND (eligibility_type='all' OR (eligibility_type='department' AND eligibility_value='$dept')) ";
}
if (!empty($_GET['place_id'])) {
    $pid = intval($_GET['place_id']);
    $where .= "AND place_id=$pid ";
}
if (!empty($_GET['date'])) {
    $date = $_GET['date'];
    $where .= "AND date='$date' ";
}
$sql = "SELECT e.*, p.name AS place_name, u.name AS organizer_name FROM events e LEFT JOIN places p ON e.place_id = p.id LEFT JOIN users u ON e.organizer_id = u.id $where ORDER BY date, start_time";
$events = mysqli_query($conn, $sql);
// Get RSVPs
$rsvped = [];
$res = mysqli_query($conn, "SELECT event_id FROM rsvps WHERE user_id=$user_id");
while($r = mysqli_fetch_assoc($res)) $rsvped[] = $r['event_id'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Events</title>
    <link rel="stylesheet" href="events_css.css">
</head>
<body>
<div style="text-align:center;"><h2>Available Events</h2></div>
<p><a href="logout.php">Logout</a></p>
<?php if (isset($_GET['msg']) && $_GET['msg'] == 'full') echo '<p style="color:red;">RSVP not allowed: All seats are full for this event.</p>'; ?>
<form method="get" action="">
    University: <input type="text" name="university" value="<?php echo htmlspecialchars($user['university']); ?>">
    Department: <input type="text" name="department" value="<?php echo htmlspecialchars($user['department']); ?>">
    Place: <select name="place_id">
        <option value="">All</option>
        <?php while($p = mysqli_fetch_assoc($places)) { echo '<option value="'.$p['id'].'">'.$p['name'].'</option>'; } ?>
    </select>
    Date: <input type="date" name="date">
    <input type="submit" value="Filter">
</form>
<table border="1">
<tr><th>Poster</th><th>Name</th><th>Description</th><th>Organizer</th><th>Contact</th><th>Eligibility</th><th>Place</th><th>Date</th><th>Time</th><th>Seats</th><th>RSVP</th></tr>
<?php while($e = mysqli_fetch_assoc($events)) {
    // Get RSVP count for this event
    $rsvp_count = 0;
    if ($e['id']) {
        $res2 = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM rsvps WHERE event_id=".$e['id']);
        $row2 = mysqli_fetch_assoc($res2);
        $rsvp_count = $row2['cnt'];
    }
    echo '<tr>';
    echo '<td>'.($e['poster'] ? '<img src="'.$e['poster'].'" width="80">' : '').'</td>';
    echo '<td>'.$e['name'].'</td>';
    echo '<td>'.$e['description'].'</td>';
    echo '<td>'.$e['organizer_name'].'</td>';
    echo '<td>'.$e['contact'].'</td>';
    echo '<td>'.($e['eligibility_type']=='all'?'All':($e['eligibility_type'].': '.$e['eligibility_value'])).'</td>';
    echo '<td>'.$e['place_name'].'</td>';
    echo '<td>'.$e['date'].'</td>';
    echo '<td>'.$e['start_time'].'-'.$e['end_time'].'</td>';
    echo '<td>'.($e['max_seats'] ? $rsvp_count.' / '.$e['max_seats'] : ($rsvp_count ? $rsvp_count : '-')).'</td>';
    if (in_array($e['id'], $rsvped)) {
        echo '<td>RSVPed</td>';
    } else {
        echo '<td><form method="post" action="rsvp.php"><input type="hidden" name="event_id" value="'.$e['id'].'"><input type="submit" value="RSVP"></form></td>';
    }
    echo '</tr>';
} ?>
</table>
</body>
</html> 