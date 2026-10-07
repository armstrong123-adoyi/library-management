<?php
// includes/recommendations.php
// Plain SQL functions: counting, grouping, matching (popularity, trending,
// genre-matching). For the actual machine learning component (Collaborative
// Filtering with Cosine Similarity), see includes/ml-recommendations.php

// Most borrowed books of all time
function get_popular_books($conn, $limit = 6, $exclude_book_id = null) {
    $exclude_clause = $exclude_book_id ? "WHERE b.id != " . intval($exclude_book_id) : "";
    $query = "SELECT b.*, COUNT(br.id) AS borrow_count
              FROM books b
              LEFT JOIN borrow_records br ON b.id = br.book_id
              $exclude_clause
              GROUP BY b.id
              ORDER BY borrow_count DESC, b.created_at DESC
              LIMIT ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) { $rows[] = $row; }
    return $rows;
}

// Popular in the last 30 days specifically - "trending"
function get_trending_books($conn, $limit = 6) {
    $query = "SELECT b.*, COUNT(br.id) AS borrow_count
              FROM books b
              JOIN borrow_records br ON b.id = br.book_id
              WHERE br.borrow_date >= CURDATE() - INTERVAL 30 DAY
              GROUP BY b.id
              ORDER BY borrow_count DESC
              LIMIT ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) { $rows[] = $row; }
    return $rows;
}

// Content-based: same genre or same author as this book
function get_similar_books($conn, $book_id, $limit = 4) {
    $current = mysqli_fetch_assoc(mysqli_query($conn, "SELECT genre, author FROM books WHERE id = " . intval($book_id)));
    if (!$current) return [];

    $query = "SELECT *,
                (CASE WHEN genre = ? THEN 2 ELSE 0 END) +
                (CASE WHEN author = ? THEN 1 ELSE 0 END) AS relevance
              FROM books
              WHERE id != ? AND (genre = ? OR author = ?)
              HAVING relevance > 0
              ORDER BY relevance DESC, available_copies DESC
              LIMIT ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssissi", $current["genre"], $current["author"], $book_id, $current["genre"], $current["author"], $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) { $rows[] = $row; }
    return $rows;
}

// Collaborative filtering: "students who borrowed this also borrowed..."
function get_also_borrowed($conn, $book_id, $limit = 4) {
    $query = "SELECT b.*, COUNT(*) AS together_count
              FROM borrow_records this_br
              JOIN borrow_records other_br
                ON this_br.student_id = other_br.student_id
               AND other_br.book_id != this_br.book_id
              JOIN books b ON other_br.book_id = b.id
              WHERE this_br.book_id = ?
              GROUP BY b.id
              ORDER BY together_count DESC
              LIMIT ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $book_id, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) { $rows[] = $row; }
    return $rows;
}

// Personalized for a student: based on genres they've borrowed before.
// Falls back to plain popularity if they have no borrow history yet
// (the standard "cold start" fallback).
function get_recommendations_for_student($conn, $student_id, $limit = 6) {
    $genre_result = mysqli_query($conn, "
        SELECT DISTINCT b.genre
        FROM borrow_records br
        JOIN books b ON br.book_id = b.id
        WHERE br.student_id = " . intval($student_id) . "
          AND b.genre IS NOT NULL AND b.genre != ''
    ");

    $genres = [];
    while ($row = mysqli_fetch_assoc($genre_result)) { $genres[] = $row["genre"]; }

    if (empty($genres)) {
        // Cold start - no history yet, just show what's popular
        return get_popular_books($conn, $limit);
    }

    // Safe to inline: genres come from our own books table, and we
    // still escape them defensively since they originated as free text.
    $escaped_genres = array_map(fn($g) => "'" . mysqli_real_escape_string($conn, $g) . "'", $genres);
    $genre_list = implode(",", $escaped_genres);

    $query = "SELECT b.* FROM books b
              WHERE b.genre IN ($genre_list)
                AND b.id NOT IN (SELECT book_id FROM borrow_records WHERE student_id = ?)
              ORDER BY b.available_copies DESC
              LIMIT ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $student_id, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) { $rows[] = $row; }

    // If personalized matches ran dry, top up with popular books instead
    if (count($rows) < $limit) {
        $more = get_popular_books($conn, $limit - count($rows));
        $existing_ids = array_column($rows, "id");
        foreach ($more as $m) {
            if (!in_array($m["id"], $existing_ids)) $rows[] = $m;
        }
    }

    return $rows;
}
// Most recently added books (by creation date) - for admin visibility
function get_recently_added_books($conn, $limit = 10) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM books ORDER BY created_at DESC LIMIT ?");
    mysqli_stmt_bind_param($stmt, "i", $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) { $rows[] = $row; }
    return $rows;
}

// Borrow count grouped by genre over a recent window - "what's hot right now"
function get_trending_categories($conn, $limit = 6, $days = 30) {
    $query = "SELECT COALESCE(NULLIF(b.genre,''), 'Uncategorized') AS genre, COUNT(*) AS c
              FROM borrow_records br
              JOIN books b ON b.id = br.book_id
              WHERE br.borrow_date >= CURDATE() - INTERVAL ? DAY
              GROUP BY genre
              ORDER BY c DESC
              LIMIT ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $days, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) { $rows[] = $row; }
    return $rows;
}
?>
