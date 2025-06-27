<?php
session_start();
$conn = mysqli_connect('localhost', 'root', '', 'event_registration');
if (!$conn) {
    die('Connection failed: ' . mysqli_connect_error());
}
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $sql);
    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = $row['role'];
            if ($row['role'] == 'admin') {
                header('Location: admin.php');
            } elseif ($row['role'] == 'organizer') {
                header('Location: organizer.php');
            } else {
                header('Location: events.php');
            }
            exit();
        } else {
            $message = 'Invalid password!';
        }
    } else {
        $message = 'No user found with that email!';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="login_css.css">
</head>
<body>
<div style="text-align:center;"><h2>Login</h2></div>
<form method="post" action="">
    Email: <input type="email" name="email" required><br>
    Password: <input type="password" name="password" required><br>
    <input type="submit" value="Login">
</form>
<p style="color:red;"> <?php echo $message; ?> </p>
<p>Don't have an account? <a href="register.php">Register here</a>.</p>
</body>
</html>