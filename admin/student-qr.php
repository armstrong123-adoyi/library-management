<?php
// admin/student-qr.php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";

require_staff();

$student_id = intval($_GET["id"] ?? 0);
$stmt = mysqli_prepare($conn, "SELECT full_name, email FROM users WHERE id = ? AND role = 'student'");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$student) {
    header("Location: manage-users.php");
    exit();
}

$active_page = "users";
include __DIR__ . "/../includes/admin-layout-header.php";
?>

<h2>Student ID QR Code</h2>
<p class="admin-page-subtitle">Print this as the student's library card. Scanning it during Issue identifies this student instantly.</p>

<div class="admin-panel" style="max-width:340px;text-align:center">
    <div id="qr-box" style="background:#fff;padding:16px;border-radius:8px;display:inline-block"></div>
    <h3 style="margin-top:14px"><?php echo htmlspecialchars($student["full_name"]); ?></h3>
    <p class="hint-text"><?php echo htmlspecialchars($student["email"]); ?> &middot; ID: <?php echo $student_id; ?></p>
    <button onclick="window.print()" class="auth-submit-btn" style="margin-top:10px">Print</button>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
new QRCode(document.getElementById("qr-box"), {
    text: "STUDENT:<?php echo $student_id; ?>",
    width: 220,
    height: 220
});
</script>

<?php include __DIR__ . "/../includes/admin-layout-footer.php"; ?>
