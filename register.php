<?php
session_start();
// Database connection (update with your DB credentials)
$conn = mysqli_connect('localhost', 'root', '', 'event_registration');
if (!$conn) {
    die('Connection failed: ' . mysqli_connect_error());
}
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $university = mysqli_real_escape_string($conn, $_POST['university']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if (mysqli_num_rows($check) > 0) {
        $message = 'Email already registered!';
    } else {
        $sql = "INSERT INTO users (name, email, password, university, department) VALUES ('$name', '$email', '$password', '$university', '$department')";
        if (mysqli_query($conn, $sql)) {
            $message = 'Registration successful! <a href="login.php">Login here</a>.';
        } else {
            $message = 'Error: ' . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="register_css.css">
</head>
<body>
<div style="text-align:center;"><h2>User Registration</h2></div>
<form method="post" action="">
    Name: <input type="text" name="name" required><br>
    Email: <input type="email" name="email" required><br>
    Password: <input type="password" name="password" required><br>
    University: <input type="text" name="university" required><br>
    Department: <input type="text" name="department" required><br>
    <input type="submit" value="Register">
</form>
<p style="color:red;"> <?php echo $message; ?> </p>
<p>Already have an account? <a href="login.php">Login here</a>.</p>
</body>
</html> 