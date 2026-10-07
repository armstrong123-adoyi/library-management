<?php
// index.php
// The homepage no longer shows anything itself - it just sends visitors
// to the right place: their dashboard if already logged in, otherwise login.

session_start();

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}
exit();
