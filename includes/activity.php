<?php
// includes/activity.php

function log_activity($conn, $user_id, $action, $description = null) {
    $query = "INSERT INTO activity_log (user_id, action, description) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iss", $user_id, $action, $description);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Returns the most recent activity entries for a given user, newest first
function get_recent_activity($conn, $user_id, $limit = 5) {
    $query = "SELECT action, description, created_at FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $rows;
}
// Returns activity across ALL staff, newest first, with the actor's name/role joined in
function get_all_activity($conn, $limit = 100) {
    $query = "SELECT a.action, a.description, a.created_at, u.full_name, u.role
              FROM activity_log a
              JOIN users u ON a.user_id = u.id
              ORDER BY a.created_at DESC
              LIMIT ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $rows;
}
?>
