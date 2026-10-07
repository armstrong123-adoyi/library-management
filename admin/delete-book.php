<?php
// admin/delete-book.php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/activity.php";

require_admin(); // deleting is destructive - reserved for admins, unlike add/edit which librarians can also do

$book_id = intval($_GET["id"] ?? 0);

if ($book_id > 0) {
    // Grab the title first so we can log something meaningful
    $title_query = "SELECT title FROM books WHERE id = ?";
    $stmt = mysqli_prepare($conn, $title_query);
    mysqli_stmt_bind_param($stmt, "i", $book_id);
    mysqli_stmt_execute($stmt);
    $title_result = mysqli_stmt_get_result($stmt);
    $book_row = mysqli_fetch_assoc($title_result);
    mysqli_stmt_close($stmt);

    $query = "DELETE FROM books WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $book_id);
    mysqli_stmt_execute($stmt);

    if ($book_row) {
        log_activity($conn, $_SESSION["user_id"], "Deleted book", $book_row["title"]);
    }
}

header("Location: manage-books.php?deleted=1");
exit();
