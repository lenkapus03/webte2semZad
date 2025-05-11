<?php
session_start();
require_once "utilities.php";

if (isset($_SESSION['username'])) {
    try{
        logUserAction($_SESSION['username'], 'logout');
    } catch (Exception $e) {
        error_log("Logout error for {$_SESSION['username']}: " . $e->getMessage());
    }
}




$_COOKIE = array();
$_SESSION = array();
session_unset();
session_destroy();


header('Location: /myapp/index.php');
exit();
