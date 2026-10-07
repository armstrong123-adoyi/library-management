<?php
// books.php
session_start();
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/recommendations.php";

require_login();

$search = trim($_GET["search"] ?? "");
$trending = $search === "" ? get_trending_books($conn, 6) : [];

if ($search !== "") {
    $like = "%" . $search . "%";
    $query = "SELECT * FROM books WHERE title LIKE ? OR author LIKE ? OR genre LIKE ? ORDER BY title ASC";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sss", $like, $like, $like);
} else {
    $query = "SELECT * FROM books ORDER BY title ASC";
    $stmt = mysqli_prepare($conn, $query);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

include __DIR__ . "/includes/header.php";
?>

<section>
    <h2>Book catalog</h2>

    <form method="GET" action="books.php" class="search-bar">
        <input type="text" name="search" placeholder="Search by title, author, or genre..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">Search</button>
        <?php if ($search !== ""): ?>
            <a href="books.php" class="clear-search">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (!empty($trending)): ?>
        <h3 style="font-size:15px;margin-bottom:10px">Trending this month</h3>
        <div class="book-grid" style="margin-bottom:28px">
            <?php foreach ($trending as $book): ?>
                <a href="book-detail.php?id=<?php echo $book["id"]; ?>" class="book-card">
                    <div class="book-cover">
                        <?php if (!empty($book["cover_image"])): ?>
                            <img src="<?php echo htmlspecialchars($book["cover_image"]); ?>" alt="<?php echo htmlspecialchars($book["title"]); ?>">
                        <?php else: ?>
                            <div class="book-cover-placeholder"><?php echo htmlspecialchars(mb_substr($book["title"], 0, 1)); ?></div>
                        <?php endif; ?>
                    </div>
                    <p class="book-title"><?php echo htmlspecialchars($book["title"]); ?></p>
                    <p class="book-author"><?php echo htmlspecialchars($book["author"]); ?></p>
                    <p class="book-availability available"><?php echo $book["borrow_count"]; ?> borrows this month</p>
                </a>
            <?php endforeach; ?>
        </div>
        <h3 style="font-size:15px;margin-bottom:10px">Full catalog</h3>
    <?php endif; ?>

    <?php if (mysqli_num_rows($result) === 0): ?>
        <p class="empty-state">No books found<?php echo $search !== "" ? " for \"" . htmlspecialchars($search) . "\"" : ""; ?>.</p>
    <?php else: ?>
        <div class="book-grid">
            <?php while ($book = mysqli_fetch_assoc($result)): ?>
                <a href="book-detail.php?id=<?php echo $book["id"]; ?>" class="book-card">
                    <div class="book-cover">
                        <?php if (!empty($book["cover_image"])): ?>
                            <img src="<?php echo htmlspecialchars($book["cover_image"]); ?>" alt="<?php echo htmlspecialchars($book["title"]); ?>">
                        <?php else: ?>
                            <div class="book-cover-placeholder"><?php echo htmlspecialchars(mb_substr($book["title"], 0, 1)); ?></div>
                        <?php endif; ?>
                    </div>
                    <p class="book-title"><?php echo htmlspecialchars($book["title"]); ?></p>
                    <p class="book-author"><?php echo htmlspecialchars($book["author"]); ?></p>
                    <p class="book-availability <?php echo $book["available_copies"] > 0 ? "available" : "unavailable"; ?>">
                        <?php echo $book["available_copies"] > 0 ? "Available (" . $book["available_copies"] . ")" : "Not available"; ?>
                    </p>
                </a>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>
