<?php
// admin/book-qr.php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";

require_staff();

$book_id = intval($_GET["id"] ?? 0);
$stmt = mysqli_prepare($conn, "SELECT title, author FROM books WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $book_id);
mysqli_stmt_execute($stmt);
$book = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$book) {
    header("Location: manage-books.php");
    exit();
}

$active_page = "books";
include __DIR__ . "/../includes/admin-layout-header.php";
?>

<h2>Book QR Code</h2>
<p class="admin-page-subtitle">Print this and stick it inside the book's cover. Scanning it during Issue/Return identifies this exact title instantly.</p>

<div class="admin-panel" style="max-width:340px;text-align:center">
    <div id="qr-box" style="background:#fff;padding:16px;border-radius:8px;display:inline-block"></div>
    <h3 style="margin-top:14px"><?php echo htmlspecialchars($book["title"]); ?></h3>
    <p class="hint-text"><?php echo htmlspecialchars($book["author"]); ?> &middot; ID: <?php echo $book_id; ?></p>
    <button onclick="window.print()" class="auth-submit-btn" style="margin-top:10px">Print</button>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
new QRCode(document.getElementById("qr-box"), {
    text: "BOOK:<?php echo $book_id; ?>",
    width: 220,
    height: 220
});
</script>

<?php include __DIR__ . "/../includes/admin-layout-footer.php"; ?>
