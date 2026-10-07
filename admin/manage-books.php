<?php
// admin/manage-books.php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";

require_staff();

$result = mysqli_query($conn, "SELECT * FROM books ORDER BY created_at DESC");

$active_page = "books";
include __DIR__ . "/../includes/admin-layout-header.php";
?>

<section>
    <div class="admin-header-row">
        <h2>Manage books</h2>
        <div style="display:flex;gap:10px">
            <a href="bulk-upload-books.php" class="add-book-btn" style="background:#334155">Bulk Upload</a>
            <a href="add-book.php" class="add-book-btn">+ Add new book</a>
        </div>
    </div>

    <?php if (isset($_GET["deleted"])): ?>
        <p class="alert alert-success">Book deleted successfully.</p>
    <?php endif; ?>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Author</th>
                <th>Genre</th>
                <th>Copies</th>
                <th>Available</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) === 0): ?>
                <tr><td colspan="6" class="empty-state">No books added yet.</td></tr>
            <?php else: ?>
                <?php while ($book = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><a href="../book-detail.php?id=<?php echo $book["id"]; ?>"><?php echo htmlspecialchars($book["title"]); ?></a></td>
                        <td><?php echo htmlspecialchars($book["author"]); ?></td>
                        <td><?php echo htmlspecialchars($book["genre"] ?? "-"); ?></td>
                        <td><?php echo $book["total_copies"]; ?></td>
                        <td><?php echo $book["available_copies"]; ?></td>
                        <td>
                            <a href="edit-book.php?id=<?php echo $book["id"]; ?>">Edit</a>
                            <a href="book-qr.php?id=<?php echo $book["id"]; ?>" style="margin-left:6px">QR</a>
                            <?php if (is_admin()): ?>
                                | <a href="delete-book.php?id=<?php echo $book["id"]; ?>" onclick="return confirm('Delete this book?');">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<?php include __DIR__ . "/../includes/admin-layout-footer.php"; ?>
