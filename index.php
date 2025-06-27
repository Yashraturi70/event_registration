<?php
session_start();
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: admin.php');
    } elseif ($_SESSION['role'] == 'organizer') {
        header('Location: organizer.php');
    } else {
        header('Location: events.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Event Registration System</title>
    <link rel="stylesheet" href="index_css.css">
</head>
<body>
<div style="text-align:center;"><h1>Welcome to the Event/Seminar Registration System</h1></div>
<p><a href="login.php">Login</a> | <a href="register.php">Register</a></p>
</body>
</html> 