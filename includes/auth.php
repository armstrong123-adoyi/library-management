<?php
// includes/auth.php
// Call session_start() and require this file at the TOP of any page
// that needs to check login status or role, before any HTML output.

function require_login() {
    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit();
    }
}

function require_admin() {
    require_login();
    if ($_SESSION["role"] !== "admin") {
        header("Location: dashboard.php");
        exit();
    }
}

// Book management is allowed for both librarians and admins
function require_staff() {
    require_login();
    if (!in_array($_SESSION["role"], ["admin", "librarian"])) {
        header("Location: dashboard.php");
        exit();
    }
}

function is_logged_in() {
    return isset($_SESSION["user_id"]);
}

function is_admin() {
    return isset($_SESSION["role"]) && $_SESSION["role"] === "admin";
}

function is_staff() {
    return isset($_SESSION["role"]) && in_array($_SESSION["role"], ["admin", "librarian"]);
}
