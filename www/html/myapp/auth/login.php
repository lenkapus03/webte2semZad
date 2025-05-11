<?php
session_start();

if (isset($_SESSION['username'])) {
    header("Location: /myapp/index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>
<h2>Login</h2>
<form action="/myapp/backend/auth/login.php" method="post" id="loginForm">
    <label for="username">Username:</label><br>
    <input type="text" name="username" id="username" placeholder="Username" required><br><br>

    <label for="password">Password:</label><br>
    <input type="password" name="password" id="password" placeholder="Password" required><br><br>

    <button type="submit">Login</button>
</form>

<div id="errorMessages" style="color: red; margin-top: 10px;"></div>

<p>Don't have an account? <a href="register.php">Register here</a></p>

<script defer src="login.js"></script>
</body>
</html>
