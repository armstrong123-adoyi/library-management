<?php
// includes/reservations.php

function has_pending_reservation($conn, $student_id, $book_id) {
    $stmt = mysqli_prepare($conn, "SELECT id FROM reservations WHERE student_id = ? AND book_id = ? AND status = 'pending'");
    mysqli_stmt_bind_param($stmt, "ii", $student_id, $book_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    return mysqli_stmt_num_rows($stmt) > 0;
}

function create_reservation($conn, $student_id, $book_id) {
    if (has_pending_reservation($conn, $student_id, $book_id)) {
        return ["success" => false, "message" => "You've already reserved this book. We'll notify you when it's ready."];
    }
    $stmt = mysqli_prepare($conn, "INSERT INTO reservations (book_id, student_id) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, "ii", $book_id, $student_id);
    $ok = mysqli_stmt_execute($stmt);
    return ["success" => $ok, "message" => $ok ? "Book reserved. You'll be notified when a copy becomes available." : "Something went wrong."];
}

function get_student_reservations($conn, $student_id) {
    $stmt = mysqli_prepare($conn, "SELECT r.*, b.title, b.author FROM reservations r
                                    JOIN books b ON r.book_id = b.id
                                    WHERE r.student_id = ? ORDER BY r.created_at DESC");
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) { $rows[] = $row; }
    return $rows;
}

function get_pending_reservations($conn) {
    $result = mysqli_query($conn, "SELECT r.*, b.title, b.available_copies, u.full_name AS student_name, u.email AS student_email
                                    FROM reservations r
                                    JOIN books b ON r.book_id = b.id
                                    JOIN users u ON r.student_id = u.id
                                    WHERE r.status = 'pending'
                                    ORDER BY r.created_at ASC");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) { $rows[] = $row; }
    return $rows;
}

// Approve: if a copy is available right now, issue the book immediately.
// Otherwise, tell the librarian there's nothing to hand out yet.
function approve_reservation($conn, $reservation_id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM reservations WHERE id = ? AND status = 'pending'");
    mysqli_stmt_bind_param($stmt, "i", $reservation_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$res) {
        return ["success" => false, "message" => "Reservation not found or already handled."];
    }

    $borrow_result = borrow_book($conn, $res["student_id"], $res["book_id"]);

    if (!$borrow_result["success"]) {
        return ["success" => false, "message" => "Could not issue yet: " . $borrow_result["message"]];
    }

    $update = mysqli_prepare($conn, "UPDATE reservations SET status = 'approved', resolved_at = NOW() WHERE id = ?");
    mysqli_stmt_bind_param($update, "i", $reservation_id);
    mysqli_stmt_execute($update);

    return ["success" => true, "message" => $borrow_result["message"], "student_id" => $res["student_id"], "book_id" => $res["book_id"]];
}

function reject_reservation($conn, $reservation_id) {
    $get = mysqli_prepare($conn, "SELECT student_id, book_id FROM reservations WHERE id = ?");
    mysqli_stmt_bind_param($get, "i", $reservation_id);
    mysqli_stmt_execute($get);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($get));

    $stmt = mysqli_prepare($conn, "UPDATE reservations SET status = 'rejected', resolved_at = NOW() WHERE id = ? AND status = 'pending'");
    mysqli_stmt_bind_param($stmt, "i", $reservation_id);
    $ok = mysqli_stmt_execute($stmt);

    return ["success" => $ok, "message" => "Reservation rejected.", "student_id" => $row["student_id"] ?? null, "book_id" => $row["book_id"] ?? null];
}

function count_pending_reservations($conn) {
    $result = mysqli_query($conn, "SELECT COUNT(*) AS c FROM reservations WHERE status = 'pending'");
    return intval(mysqli_fetch_assoc($result)["c"]);
}
?>
