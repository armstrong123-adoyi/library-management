<?php
// includes/borrowing.php
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/settings.php";

define("RENEWAL_EXTENSION_DAYS", 7);

// Attempts to borrow a book for a student. Returns ["success" => bool, "message" => string]
function borrow_book($conn, $student_id, $book_id) {
    $book_query = "SELECT title, available_copies FROM books WHERE id = ?";
    $stmt = mysqli_prepare($conn, $book_query);
    mysqli_stmt_bind_param($stmt, "i", $book_id);
    mysqli_stmt_execute($stmt);
    $book = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$book) {
        return ["success" => false, "message" => "Book not found."];
    }
    if ($book["available_copies"] < 1) {
        return ["success" => false, "message" => "No copies of this book are currently available."];
    }

    $dup_query = "SELECT id FROM borrow_records WHERE student_id = ? AND book_id = ? AND status = 'borrowed'";
    $stmt = mysqli_prepare($conn, $dup_query);
    mysqli_stmt_bind_param($stmt, "ii", $student_id, $book_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_stmt_close($stmt);
        return ["success" => false, "message" => "This student already has this book borrowed."];
    }
    mysqli_stmt_close($stmt);

    $max_books = intval(get_setting($conn, "max_books_per_student", 3));

    $count_query = "SELECT COUNT(*) AS c FROM borrow_records WHERE student_id = ? AND status = 'borrowed'";
    $stmt = mysqli_prepare($conn, $count_query);
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $current_count = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))["c"];
    mysqli_stmt_close($stmt);

    if ($current_count >= $max_books) {
        return ["success" => false, "message" => "This student has reached the limit of $max_books borrowed books at once."];
    }

    $loan_days = intval(get_setting($conn, "loan_duration_days", 14));
    $borrow_date = date("Y-m-d");
    $due_date = date("Y-m-d", strtotime("+$loan_days days"));

    $insert = "INSERT INTO borrow_records (book_id, student_id, borrow_date, due_date) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert);
    mysqli_stmt_bind_param($stmt, "iiss", $book_id, $student_id, $borrow_date, $due_date);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$ok) {
        return ["success" => false, "message" => "Something went wrong. Please try again."];
    }

    mysqli_query($conn, "UPDATE books SET available_copies = available_copies - 1 WHERE id = " . intval($book_id));

    return ["success" => true, "message" => "\"" . $book["title"] . "\" borrowed successfully. Due back " . date("F j, Y", strtotime($due_date)) . "."];
}

// Marks a borrow record as returned, restores the book's available copies,
// and calculates the automated fine if it's overdue.
function return_book($conn, $borrow_id) {
    $query = "SELECT book_id, status, due_date FROM borrow_records WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $borrow_id);
    mysqli_stmt_execute($stmt);
    $record = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$record || $record["status"] === "returned") {
        return ["success" => false, "message" => "Borrow record not found or already returned.", "fine" => 0];
    }

    $return_date = date("Y-m-d");
    $due_date = $record["due_date"];
    $fine_per_day = floatval(get_setting($conn, "fine_per_day", 50));

    $days_overdue = 0;
    $fine = 0;
    if (strtotime($return_date) > strtotime($due_date)) {
        $days_overdue = round((strtotime($return_date) - strtotime($due_date)) / 86400);
        $fine = $days_overdue * $fine_per_day;
    }

    $update = "UPDATE borrow_records SET status = 'returned', return_date = ?, fine_amount = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update);
    mysqli_stmt_bind_param($stmt, "sdi", $return_date, $fine, $borrow_id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($ok) {
        mysqli_query($conn, "UPDATE books SET available_copies = available_copies + 1 WHERE id = " . intval($record["book_id"]));
    }

    $message = $fine > 0
        ? "Returned. $days_overdue day(s) overdue - a fine of " . number_format($fine, 2) . " was applied automatically."
        : "Returned on time. No fine.";

    return ["success" => $ok, "message" => $message, "fine" => $fine];
}

// Live estimate of the fine for a book still out and already overdue
function estimate_current_fine($conn, $due_date) {
    if (strtotime(date("Y-m-d")) <= strtotime($due_date)) {
        return 0;
    }
    $fine_per_day = floatval(get_setting($conn, "fine_per_day", 50));
    $days_overdue = round((strtotime(date("Y-m-d")) - strtotime($due_date)) / 86400);
    return $days_overdue * $fine_per_day;
}

function count_active_borrows($conn) {
    $result = mysqli_query($conn, "SELECT COUNT(*) AS c FROM borrow_records WHERE status = 'borrowed'");
    return intval(mysqli_fetch_assoc($result)["c"]);
}

function count_overdue_borrows($conn) {
    $result = mysqli_query($conn, "SELECT COUNT(*) AS c FROM borrow_records WHERE status = 'borrowed' AND due_date < CURDATE()");
    return intval(mysqli_fetch_assoc($result)["c"]);
}

function count_returned_today($conn) {
    $result = mysqli_query($conn, "SELECT COUNT(*) AS c FROM borrow_records WHERE status = 'returned' AND return_date = CURDATE()");
    return intval(mysqli_fetch_assoc($result)["c"]);
}

function sum_outstanding_fines($conn) {
    $finalized = mysqli_query($conn, "SELECT COALESCE(SUM(fine_amount),0) AS s FROM borrow_records WHERE fine_amount > 0 AND fine_paid = 0");
    $total = floatval(mysqli_fetch_assoc($finalized)["s"]);

    $overdue = mysqli_query($conn, "SELECT due_date FROM borrow_records WHERE status = 'borrowed' AND due_date < CURDATE()");
    while ($row = mysqli_fetch_assoc($overdue)) {
        $total += estimate_current_fine($conn, $row["due_date"]);
    }
    return $total;
}

function renew_book($conn, $borrow_id) {
    $query = "SELECT due_date, status, renewed FROM borrow_records WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $borrow_id);
    mysqli_stmt_execute($stmt);
    $record = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$record || $record["status"] !== "borrowed") {
        return ["success" => false, "message" => "This book can't be renewed."];
    }
    if ($record["renewed"]) {
        return ["success" => false, "message" => "This book has already been renewed once."];
    }

    $new_due_date = date("Y-m-d", strtotime($record["due_date"] . " +" . RENEWAL_EXTENSION_DAYS . " days"));
    $update = "UPDATE borrow_records SET due_date = ?, renewed = 1 WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update);
    mysqli_stmt_bind_param($stmt, "si", $new_due_date, $borrow_id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok
        ? ["success" => true, "message" => "Renewed. New due date: " . date("F j, Y", strtotime($new_due_date))]
        : ["success" => false, "message" => "Something went wrong."];
}

function is_overdue($record) {
    return $record["status"] === "borrowed" && strtotime($record["due_date"]) < strtotime(date("Y-m-d"));
}

function get_student_borrows($conn, $student_id) {
    $query = "SELECT br.*, b.title, b.author, b.cover_image
              FROM borrow_records br
              JOIN books b ON br.book_id = b.id
              WHERE br.student_id = ?
              ORDER BY br.status = 'borrowed' DESC, br.borrow_date DESC";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) { $rows[] = $row; }
    return $rows;
}

function get_all_borrows($conn, $status_filter = "all") {
    $query = "SELECT br.*, b.title, u.full_name AS student_name
              FROM borrow_records br
              JOIN books b ON br.book_id = b.id
              JOIN users u ON br.student_id = u.id";

    if ($status_filter === "borrowed") {
        $query .= " WHERE br.status = 'borrowed' AND br.due_date >= CURDATE()";
    } elseif ($status_filter === "overdue") {
        $query .= " WHERE br.status = 'borrowed' AND br.due_date < CURDATE()";
    } elseif ($status_filter === "returned") {
        $query .= " WHERE br.status = 'returned'";
    }

    $query .= " ORDER BY br.borrow_date DESC";
    $result = mysqli_query($conn, $query);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) { $rows[] = $row; }
    return $rows;
}

function get_all_fines($conn) {
    $query = "SELECT br.*, b.title, u.full_name AS student_name, u.email AS student_email
              FROM borrow_records br
              JOIN books b ON br.book_id = b.id
              JOIN users u ON br.student_id = u.id
              WHERE br.fine_amount > 0
                 OR (br.status = 'borrowed' AND br.due_date < CURDATE())
              ORDER BY br.fine_paid ASC, br.due_date ASC";
    $result = mysqli_query($conn, $query);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row["status"] === "borrowed") {
            $row["fine_amount"] = estimate_current_fine($conn, $row["due_date"]);
            $row["is_estimate"] = true;
        } else {
            $row["is_estimate"] = false;
        }
        $rows[] = $row;
    }
    return $rows;
}

function mark_fine_paid($conn, $borrow_id) {
    $query = "UPDATE borrow_records SET fine_paid = 1 WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $borrow_id);
    return mysqli_stmt_execute($stmt);
}

// Waive: zero out the fine entirely and mark it settled
function waive_fine($conn, $borrow_id) {
    $query = "UPDATE borrow_records SET fine_amount = 0, fine_paid = 1 WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $borrow_id);
    return mysqli_stmt_execute($stmt);
}

// Reduce: set the fine to a specific new (lower) amount, still unpaid
function reduce_fine($conn, $borrow_id, $new_amount) {
    $query = "UPDATE borrow_records SET fine_amount = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "di", $new_amount, $borrow_id);
    return mysqli_stmt_execute($stmt);
}
?>
