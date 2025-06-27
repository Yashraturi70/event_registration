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
$message = '';
// Get places for dropdown
$places = mysqli_query($conn, "SELECT id, name FROM places");
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
    $poster = '';
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] == 0) {
        $target = 'uploads/'.basename($_FILES['poster']['name']);
        if (move_uploaded_file($_FILES['poster']['tmp_name'], $target)) {
            $poster = $target;
        }
    }
    // Check for overlap
    $sql = "SELECT * FROM events WHERE place_id = $place_id AND date = '$date' AND (start_time < '$end_time' AND end_time > '$start_time')";
    $overlap = mysqli_query($conn, $sql);
    if (mysqli_num_rows($overlap) > 0) {
        $message = '<span style="color:red;">Error: Another event is already scheduled at this place and time!</span>';
    } else {
        // Check for same university/place
        $notify = '';
        if ($eligibility_type == 'university') {
            $sql2 = "SELECT * FROM events WHERE place_id = $place_id AND date = '$date' AND eligibility_type = 'university' AND eligibility_value = '$eligibility_value'";
            $same = mysqli_query($conn, $sql2);
            if (mysqli_num_rows($same) > 0) {
                $notify = '<span style="color:orange;">Note: Another event is already registered for this university at this place and date.</span><br>';
            }
        }
        // Insert event
        $sql = "INSERT INTO events (name, description, poster, organizer_id, contact, eligibility_type, eligibility_value, place_id, date, start_time, end_time, max_seats) VALUES ('$name', '$description', '$poster', $organizer_id, '$contact', '$eligibility_type', '$eligibility_value', $place_id, '$date', '$start_time', '$end_time', $max_seats)";
        if (mysqli_query($conn, $sql)) {
            $message = $notify.'<span style="color:green;">Event created successfully!</span>';
        } else {
            $message = '<span style="color:red;">Error: '.mysqli_error($conn).'</span>';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Event</title>
    <link rel="stylesheet" href="create_event_css.css">
</head>
<body>
<div style="text-align:center;"><h2>Create New Event</h2></div>
<p><a href="organizer.php">Back to Dashboard</a> | <a href="logout.php">Logout</a></p>
<p><?php echo $message; ?></p>
<form method="post" enctype="multipart/form-data">
    Event Name: <input type="text" name="name" required><br>
    Description: <textarea name="description" required></textarea><br>
    Poster: <input type="file" name="poster" accept="image/*"><br>
    Contact Info: <input type="text" name="contact" required><br>
    Eligibility: 
    <select name="eligibility_type" id="eligibility_type" onchange="showEligibilityValue()">
        <option value="all">Open to All</option>
        <option value="university">University</option>
        <option value="department">Department</option>
    </select>
    <span id="eligibility_value_span"></span><br>
    Place: <select name="place_id" required>
        <option value="">Select Place</option>
        <?php while($p = mysqli_fetch_assoc($places)) { echo '<option value="'.$p['id'].'">'.$p['name'].'</option>'; } ?>
    </select><br>
    Date: <input type="date" name="date" required><br>
    Start Time: <input type="time" name="start_time" required><br>
    End Time: <input type="time" name="end_time" required><br>
    Max Seats (optional): <input type="number" name="max_seats" min="1"><br>
    <input type="submit" value="Create Event">
</form>
<script>
function showEligibilityValue() {
    var type = document.getElementById('eligibility_type').value;
    var span = document.getElementById('eligibility_value_span');
    if (type === 'university' || type === 'department') {
        span.innerHTML = ' Value: <input type="text" name="eligibility_value" required>';
    } else {
        span.innerHTML = '<input type="hidden" name="eligibility_value" value="">';
    }
}
showEligibilityValue();
</script>
</body>
</html> 