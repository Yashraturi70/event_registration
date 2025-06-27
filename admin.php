<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}
$conn = mysqli_connect('localhost', 'root', '', 'event_registration');
if (!$conn) {
    die('Connection failed: ' . mysqli_connect_error());
}
$message = '';
// Add new place
if (isset($_POST['add_place'])) {
    $place = mysqli_real_escape_string($conn, $_POST['place']);
    if ($place != '') {
        $sql = "INSERT INTO places (name) VALUES ('$place')";
        if (mysqli_query($conn, $sql)) {
            $message = 'Place added!';
        } else {
            $message = 'Error: ' . mysqli_error($conn);
        }
    }
}
// User search logic for role assignment
$search_results = [];
if (isset($_POST['search_user'])) {
    $search_term = mysqli_real_escape_string($conn, $_POST['search_term']);
    $sql = "SELECT id, name, email, role FROM users WHERE name LIKE '%$search_term%' OR email LIKE '%$search_term%'";
    $search_results = mysqli_query($conn, $sql);
}
// Assign role
if (isset($_POST['assign_role'])) {
    $user_id = intval($_POST['user_id']);
    $role = $_POST['role'];
    if (in_array($role, ['admin','organizer','user'])) {
        $sql = "UPDATE users SET role='$role' WHERE id=$user_id";
        if (mysqli_query($conn, $sql)) {
            $message = 'Role updated!';
        } else {
            $message = 'Error: ' . mysqli_error($conn);
        }
    }
}
// Get all users
$users = mysqli_query($conn, "SELECT id, name, email, role FROM users");
// Get all places
$places = mysqli_query($conn, "SELECT id, name FROM places");
// Handle event deletion
if (isset($_POST['delete_event'])) {
    $event_id = intval($_POST['event_id']);
    // Delete RSVPs first (to maintain referential integrity)
    mysqli_query($conn, "DELETE FROM rsvps WHERE event_id=$event_id");
    // Delete the event
    if (mysqli_query($conn, "DELETE FROM events WHERE id=$event_id")) {
        $message = 'Event deleted!';
    } else {
        $message = 'Error deleting event: ' . mysqli_error($conn);
    }
}
// List all events
$all_events = mysqli_query($conn, "SELECT e.*, p.name AS place_name, u.name AS organizer_name FROM events e LEFT JOIN places p ON e.place_id = p.id LEFT JOIN users u ON e.organizer_id = u.id ORDER BY date DESC, start_time DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin_css.css">
</head>
<body>
<h2>Admin Dashboard</h2>
<p><a href="logout.php">Logout</a></p>
<p style="color:green;"> <?php echo $message; ?> </p>
<div style="text-align:center;"><h3>Add New Place</h3></div>
<form method="post" action="">
    Place Name: <input type="text" name="place" required>
    <input type="submit" name="add_place" value="Add Place">
</form>
<div style="text-align:center;"><h3>Assign Role to User</h3></div>
<form method="post" action="">
    <input type="text" name="search_term" placeholder="Search by name or email" required>
    <input type="submit" name="search_user" value="Search">
</form>
<?php if (isset($_POST['search_user'])): ?>
    <form method="post" action="">
        <?php if (mysqli_num_rows($search_results) > 0): ?>
            <?php while($u = mysqli_fetch_assoc($search_results)): ?>
                <label>
                    <input type="radio" name="user_id" value="<?php echo $u['id']; ?>" required>
                    <?php echo $u['name'] . ' (' . $u['email'] . ')'; ?>
                </label><br>
            <?php endwhile; ?>
            <select name="role" required>
                <option value="user">User</option>
                <option value="organizer">Organizer</option>
                <option value="admin">Admin</option>
            </select>
            <input type="submit" name="assign_role" value="Assign Role">
        <?php else: ?>
            <p>No users found.</p>
        <?php endif; ?>
    </form>
<?php endif; ?>
<div style="text-align:center;"><h3>All Users</h3></div>
<table border="1"><tr><th>Name</th><th>Email</th><th>Role</th></tr>
<?php 
$users = mysqli_query($conn, "SELECT name, email, role FROM users");
while($u = mysqli_fetch_assoc($users)) {
    echo '<tr><td>'.$u['name'].'</td><td>'.$u['email'].'</td><td>'.$u['role'].'</td></tr>';
}
?>
</table>
<div style="text-align:center;"><h3>All Places</h3></div>
<table border="1"><tr><th>Place Name</th></tr>
<?php 
$places = mysqli_query($conn, "SELECT name FROM places");
while($p = mysqli_fetch_assoc($places)) {
    echo '<tr><td>'.$p['name'].'</td></tr>';
}
?>
<div style="text-align:center;"><h3>All Events</h3></div>
<table border="1"><tr><th>Name</th><th>Organizer</th><th>Place</th><th>Date</th><th>Time</th><th>Action</th></tr>
<?php while($e = mysqli_fetch_assoc($all_events)) {
    echo '<tr>';
    echo '<td>'.$e['name'].'</td>';
    echo '<td>'.$e['organizer_name'].'</td>';
    echo '<td>'.$e['place_name'].'</td>';
    echo '<td>'.$e['date'].'</td>';
    echo '<td>'.$e['start_time'].'-'.$e['end_time'].'</td>';
    echo '<td><a href="edit_event.php?id='.$e['id'].'">Edit</a> | ';
    echo '<a href="view_rsvps.php?id='.$e['id'].'">View RSVPs</a></td>';
    echo '</tr>';
} ?>
</table>
</body>
</html> 