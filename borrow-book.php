<?php
// borrow-book.php
session_start();
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/activity.php";
require_once __DIR__ . "/includes/borrowing.php";

require_login();

if ($_SESSION["role"] !== "student") {
    header("Location: dashboard.php");
    exit();
}

$book_id = intval($_POST["book_id"] ?? 0);

if ($book_id > 0) {
    $result = borrow_book($conn, $_SESSION["user_id"], $book_id);
    if ($result["success"]) {
        log_activity($conn, $_SESSION["user_id"], "Borrowed book", $result["message"]);
    }
    $_SESSION["borrow_flash"] = $result;
}

header("Location: book-detail.php?id=" . $book_id);
exit();
