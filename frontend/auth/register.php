<?php
session_start();

if (isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
</head>
<body>
<h2>Register</h2>
<form id="registerForm">
    <label for="username">Username:</label><br>
    <input type="text" name="username" id="username" placeholder="Username" required><br><br>

    <label for="password">Password:</label><br>
    <input type="password" name="password" id="password" placeholder="Password" required><br><br>

    <label for="role">Role:</label><br>
    <select name="role" id="role" required>
        <option value="user">User</option>
        <option value="admin">Admin</option>
    </select><br><br>

    <button type="submit">Register</button>
</form>

<p>Already have an account? <a href="login.php">Login here</a></p>

<div id="errorMessages" style="color: red; display: none;">
    <ul id="errorList"></ul>
</div>

<div id="apiKeyDisplay" style="display:none;">
    <p>Your API Key: <strong><span id="apiKey"></span></strong></p>
</div>

<script defer src="register.js"></script>
</body>
</html>
