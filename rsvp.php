<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'user') {
    header('Location: login.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'])) {
    $conn = mysqli_connect('localhost', 'root', '', 'event_registration');
    if (!$conn) {
        die('Connection failed: ' . mysqli_connect_error());
    }
    $user_id = $_SESSION['user_id'];
    $event_id = intval($_POST['event_id']);
    // Check seat limit
    $event = mysqli_fetch_assoc(mysqli_query($conn, "SELECT max_seats FROM events WHERE id=$event_id"));
    if ($event && $event['max_seats'] !== null) {
        $rsvp_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM rsvps WHERE event_id=$event_id"));
        if ($rsvp_count['cnt'] >= $event['max_seats']) {
            header('Location: events.php?msg=full');
            exit();
        }
    }
    // Prevent duplicate RSVP
    $check = mysqli_query($conn, "SELECT * FROM rsvps WHERE user_id=$user_id AND event_id=$event_id");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "INSERT INTO rsvps (user_id, event_id) VALUES ($user_id, $event_id)");
    }
    header('Location: events.php');
    exit();
} else {
    header('Location: events.php');
    exit();
} 