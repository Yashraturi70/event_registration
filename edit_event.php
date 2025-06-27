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
    die('You do not have permission to edit this event.');
}
$message = '';
$places = mysqli_query($conn, "SELECT id, name FROM places");
// Handle event deletion
if (isset($_POST['delete_event'])) {
    $event_id = intval($_GET['id']);
    // Delete RSVPs first
    mysqli_query($conn, "DELETE FROM rsvps WHERE event_id=$event_id");
    // Delete the event
    if (mysqli_query($conn, "DELETE FROM events WHERE id=$event_id")) {
        $redirect = ($_SESSION['role'] == 'admin') ? 'admin.php' : 'organizer.php';
        header('Location: ' . $redirect . '?msg=deleted');
        exit();
    } else {
        $message = '<span style="color:red;">Error deleting event: ' . mysqli_error($conn) . '</span>';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);
    $eligibility_type = $_POST['eligibility_type'];
    $eligibility_value = mysqli_real_escape_string($conn, $_POST['eligibility_value']);
    $place_id = intval($_POST['place_id']);
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $max_seats = isset($_POST['max_seats']) && $_POST['max_seats'] !== '' ? intval($_POST['max_seats']) : 'NULL';
    // Poster upload
    $poster = $event['poster'];
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] == 0) {
        $target = 'uploads/'.basename($_FILES['poster']['name']);
        if (move_uploaded_file($_FILES['poster']['tmp_name'], $target)) {
            $poster = $target;
        }
    }
    // Check for overlap (exclude this event)
    $sql = "SELECT * FROM events WHERE place_id = $place_id AND date = '$date' AND (start_time < '$end_time' AND end_time > '$start_time') AND id != $event_id";
    $overlap = mysqli_query($conn, $sql);
    if (mysqli_num_rows($overlap) > 0) {
        $message = '<span style="color:red;">Error: Another event is already scheduled at this place and time!</span>';
    } else {
        // Check for same university/place
        $notify = '';
        if ($eligibility_type == 'university') {
            $sql2 = "SELECT * FROM events WHERE place_id = $place_id AND date = '$date' AND eligibility_type = 'university' AND eligibility_value = '$eligibility_value' AND id != $event_id";
            $same = mysqli_query($conn, $sql2);
            if (mysqli_num_rows($same) > 0) {
                $notify = '<span style="color:orange;">Note: Another event is already registered for this university at this place and date.</span><br>';
            }
        }
        // Update event
        $sql = "UPDATE events SET name='$name', description='$description', poster='$poster', contact='$contact', eligibility_type='$eligibility_type', eligibility_value='$eligibility_value', place_id=$place_id, date='$date', start_time='$start_time', end_time='$end_time', max_seats=$max_seats WHERE id=$event_id";
        if (mysqli_query($conn, $sql)) {
            $message = $notify.'<span style="color:green;">Event updated successfully!</span>';
            // Refresh event data
            $event = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM events WHERE id=$event_id"));
        } else {
            $message = '<span style="color:red;">Error: '.mysqli_error($conn).'</span>';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Event</title>
    <link rel="stylesheet" href="create_event_css.css">
</head>
<body>
<div style="text-align:center;"><h2>Edit Event</h2></div>
<p><a href="<?php echo ($_SESSION['role'] == 'admin') ? 'admin.php' : 'organizer.php'; ?>">Back to Dashboard</a> | <a href="logout.php">Logout</a></p>
<p><?php echo $message; ?></p>
<form method="post" enctype="multipart/form-data">
    Event Name: <input type="text" name="name" value="<?php echo htmlspecialchars($event['name']); ?>" required><br>
    Description: <textarea name="description" required><?php echo htmlspecialchars($event['description']); ?></textarea><br>
    Poster: <input type="file" name="poster" accept="image/*"> <?php if($event['poster']) echo '<br><img src="'.$event['poster'].'" width="80">'; ?><br>
    Contact Info: <input type="text" name="contact" value="<?php echo htmlspecialchars($event['contact']); ?>" required><br>
    Eligibility: 
    <select name="eligibility_type" id="eligibility_type" onchange="showEligibilityValue()">
        <option value="all" <?php if($event['eligibility_type']=='all') echo 'selected'; ?>>Open to All</option>
        <option value="university" <?php if($event['eligibility_type']=='university') echo 'selected'; ?>>University</option>
        <option value="department" <?php if($event['eligibility_type']=='department') echo 'selected'; ?>>Department</option>
    </select>
    <span id="eligibility_value_span"></span><br>
    Place: <select name="place_id" required>
        <option value="">Select Place</option>
        <?php mysqli_data_seek($places, 0); while($p = mysqli_fetch_assoc($places)) { echo '<option value="'.$p['id'].'"'.($event['place_id']==$p['id']?' selected':'').'>'.$p['name'].'</option>'; } ?>
    </select><br>
    Date: <input type="date" name="date" value="<?php echo $event['date']; ?>" required><br>
    Start Time: <input type="time" name="start_time" value="<?php echo $event['start_time']; ?>" required><br>
    End Time: <input type="time" name="end_time" value="<?php echo $event['end_time']; ?>" required><br>
    Max Seats (optional): <input type="number" name="max_seats" min="1" value="<?php echo htmlspecialchars($event['max_seats']); ?>"><br>
    <input type="submit" value="Update Event">
</form>
<form method="post" onsubmit="return confirm('Are you sure you want to delete this event?');" style="margin-top:20px;text-align:center;">
    <input type="hidden" name="delete_event" value="1">
    <input type="submit" value="Delete Event" style="background:#c0392b;color:#fff;padding:10px 20px;border:none;border-radius:6px;cursor:pointer;">
</form>
<script>
function showEligibilityValue() {
    var type = document.getElementById('eligibility_type').value;
    var span = document.getElementById('eligibility_value_span');
    if (type === 'university' || type === 'department') {
        span.innerHTML = ' Value: <input type="text" name="eligibility_value" required value="<?php echo htmlspecialchars($event['eligibility_value']); ?>">';
    } else {
        span.innerHTML = '<input type="hidden" name="eligibility_value" value="">';
    }
}
showEligibilityValue();
</script>
</body>
</html> 