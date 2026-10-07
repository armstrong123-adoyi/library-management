<?php
// admin/categories.php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";

require_staff();

$result = mysqli_query($conn, "SELECT COALESCE(NULLIF(genre,''), 'Uncategorized') AS genre, COUNT(*) AS total, SUM(available_copies) AS available FROM books GROUP BY genre ORDER BY total DESC");

$active_page = "categories";
include __DIR__ . "/../includes/admin-layout-header.php";
?>

<h2>Book Categories</h2>
<p class="admin-page-subtitle">Every genre currently in the catalog, with how many titles and copies are available.</p>

<div class="admin-panel">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Category</th>
                <th>Titles</th>
                <th>Available copies</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) === 0): ?>
                <tr><td colspan="4" class="empty-state">No books added yet.</td></tr>
            <?php else: ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row["genre"]); ?></td>
                        <td><?php echo $row["total"]; ?></td>
                        <td><?php echo $row["available"]; ?></td>
                        <td><a href="../books.php?search=<?php echo urlencode($row["genre"]); ?>">Browse</a></td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . "/../includes/admin-layout-footer.php"; ?>
