<?php
// renew-book.php
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

$borrow_id = intval($_POST["borrow_id"] ?? 0);

if ($borrow_id > 0) {
    // Make sure this borrow record actually belongs to the logged-in student
    $check = mysqli_prepare($conn, "SELECT id FROM borrow_records WHERE id = ? AND student_id = ?");
    mysqli_stmt_bind_param($check, "ii", $borrow_id, $_SESSION["user_id"]);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);

    if (mysqli_stmt_num_rows($check) > 0) {
        $result = renew_book($conn, $borrow_id);
        if ($result["success"]) {
            log_activity($conn, $_SESSION["user_id"], "Renewed book", $result["message"]);
        }
        $_SESSION["borrow_flash"] = $result;
    }
}

header("Location: dashboard.php");
exit();
