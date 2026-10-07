<?php
// book-detail.php
session_start();
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/borrowing.php";
require_once __DIR__ . "/includes/reservations.php";
require_once __DIR__ . "/includes/recommendations.php";
require_once __DIR__ . "/includes/ml-recommendations.php";

require_login();

$book_id = intval($_GET["id"] ?? 0);

$query = "SELECT b.*, u.full_name AS added_by_name FROM books b LEFT JOIN users u ON b.added_by = u.id WHERE b.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $book_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$book = mysqli_fetch_assoc($result);

if (!$book) {
    include __DIR__ . "/includes/header.php";
    echo '<p class="empty-state">Book not found. <a href="books.php">Back to catalog</a></p>';
    include __DIR__ . "/includes/footer.php";
    exit();
}

$flash = $_SESSION["borrow_flash"] ?? null;
unset($_SESSION["borrow_flash"]);

$already_borrowed = false;
if ($_SESSION["role"] === "student") {
    $check = mysqli_prepare($conn, "SELECT id FROM borrow_records WHERE student_id = ? AND book_id = ? AND status = 'borrowed'");
    mysqli_stmt_bind_param($check, "ii", $_SESSION["user_id"], $book_id);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);
    $already_borrowed = mysqli_stmt_num_rows($check) > 0;
}

$already_reserved = false;
if ($_SESSION["role"] === "student") {
    $already_reserved = has_pending_reservation($conn, $_SESSION["user_id"], $book_id);
}

$similar_books = get_similar_books($conn, $book_id, 4);
$also_borrowed = compute_similar_books_ml($conn, $book_id, 4);

include __DIR__ . "/includes/header.php";
?>

<a href="books.php" class="back-link">&larr; Back to catalog</a>

<?php if ($flash): ?>
    <p class="alert <?php echo $flash["success"] ? "alert-success" : "alert-error"; ?>"><?php echo htmlspecialchars($flash["message"]); ?></p>
<?php endif; ?>

<section class="book-detail-box">
    <div class="book-detail-cover">
        <?php if (!empty($book["cover_image"])): ?>
            <img src="<?php echo htmlspecialchars($book["cover_image"]); ?>" alt="<?php echo htmlspecialchars($book["title"]); ?>">
        <?php else: ?>
            <div class="book-cover-placeholder large"><?php echo htmlspecialchars(mb_substr($book["title"], 0, 1)); ?></div>
        <?php endif; ?>
    </div>

    <div class="book-detail-info">
        <h2><?php echo htmlspecialchars($book["title"]); ?></h2>
        <p class="book-detail-author">by <?php echo htmlspecialchars($book["author"]); ?></p>

        <?php if (!empty($book["genre"])): ?>
            <span class="book-tag"><?php echo htmlspecialchars($book["genre"]); ?></span>
        <?php endif; ?>

        <?php if (!empty($book["isbn"])): ?>
            <p class="book-meta">ISBN: <?php echo htmlspecialchars($book["isbn"]); ?></p>
        <?php endif; ?>

        <p class="book-meta">
            Copies available: <strong><?php echo $book["available_copies"]; ?></strong> of <?php echo $book["total_copies"]; ?>
        </p>

        <?php if (!empty($book["description"])): ?>
            <p class="book-description"><?php echo nl2br(htmlspecialchars($book["description"])); ?></p>
        <?php endif; ?>

        <?php if ($_SESSION["role"] === "student"): ?>
            <?php if ($already_borrowed): ?>
                <p class="alert alert-success" style="display:inline-block">You already have this book borrowed. Check your dashboard for the due date.</p>
            <?php elseif ($book["available_copies"] > 0): ?>
                <form method="POST" action="borrow-book.php">
                    <input type="hidden" name="book_id" value="<?php echo $book["id"]; ?>">
                    <button type="submit" class="borrow-btn">Borrow this book</button>
                </form>
                <p class="hint-text">Loan period: <?php echo get_setting($conn, "loan_duration_days", 14); ?> days. Max <?php echo get_setting($conn, "max_books_per_student", 3); ?> books at once.</p>
            <?php else: ?>
                <p class="alert alert-error">All copies are currently borrowed.</p>
                <?php if ($already_reserved): ?>
                    <p class="alert alert-success" style="display:inline-block">You've reserved this book &mdash; we'll notify you when it's ready.</p>
                <?php else: ?>
                    <form method="POST" action="reserve-book.php">
                        <input type="hidden" name="book_id" value="<?php echo $book["id"]; ?>">
                        <button type="submit" class="borrow-btn">Reserve this book</button>
                    </form>
                    <p class="hint-text">We'll notify you as soon as a copy is returned and available.</p>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (is_staff()): ?>
            <div class="admin-actions">
                <a href="admin/edit-book.php?id=<?php echo $book["id"]; ?>" class="admin-link">Edit book</a>
                <?php if (is_admin()): ?>
                    <a href="admin/delete-book.php?id=<?php echo $book["id"]; ?>" class="admin-link danger" onclick="return confirm('Delete this book? This cannot be undone.');">Delete book</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($similar_books)): ?>
<section style="margin-top:28px">
    <h3 style="font-size:16px;margin-bottom:12px">More like this</h3>
    <div class="book-grid">
        <?php foreach ($similar_books as $rec): ?>
            <a href="book-detail.php?id=<?php echo $rec["id"]; ?>" class="book-card">
                <div class="book-cover">
                    <?php if (!empty($rec["cover_image"])): ?>
                        <img src="<?php echo htmlspecialchars($rec["cover_image"]); ?>" alt="">
                    <?php else: ?>
                        <div class="book-cover-placeholder"><?php echo htmlspecialchars(mb_substr($rec["title"], 0, 1)); ?></div>
                    <?php endif; ?>
                </div>
                <p class="book-title"><?php echo htmlspecialchars($rec["title"]); ?></p>
                <p class="book-author"><?php echo htmlspecialchars($rec["author"]); ?></p>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($also_borrowed)): ?>
<section style="margin-top:28px">
    <h3 style="font-size:16px;margin-bottom:4px">
        Students who borrowed this also borrowed
        <span class="ml-badge" title="Item-Based Collaborative Filtering, Cosine Similarity"><i class="ti ti-brain"></i> AI-Powered</span>
    </h3>
    <p class="hint-text" style="margin-bottom:12px">Computed by a machine learning similarity model from real borrowing patterns - not manually curated.</p>
    <div class="book-grid">
        <?php foreach ($also_borrowed as $rec): ?>
            <a href="book-detail.php?id=<?php echo $rec["id"]; ?>" class="book-card">
                <div class="book-cover">
                    <?php if (!empty($rec["cover_image"])): ?>
                        <img src="<?php echo htmlspecialchars($rec["cover_image"]); ?>" alt="">
                    <?php else: ?>
                        <div class="book-cover-placeholder"><?php echo htmlspecialchars(mb_substr($rec["title"], 0, 1)); ?></div>
                    <?php endif; ?>
                </div>
                <p class="book-title"><?php echo htmlspecialchars($rec["title"]); ?></p>
                <p class="book-author"><?php echo htmlspecialchars($rec["author"]); ?></p>
                <div class="similarity-bar-track" title="Similarity score: <?php echo round($rec["similarity"], 3); ?>">
                    <div class="similarity-bar-fill" style="width:<?php echo min(100, round($rec["similarity"] * 100)); ?>%"></div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . "/includes/footer.php"; ?>
