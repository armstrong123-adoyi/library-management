<?php
// admin/coming-soon.php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";

require_staff();

$feature = trim($_GET["feature"] ?? "This feature");

$active_page = "";
include __DIR__ . "/../includes/admin-layout-header.php";
?>

<div class="admin-panel coming-soon-box">
    <i class="ti ti-hammer"></i>
    <h2><?php echo htmlspecialchars($feature); ?></h2>
    <p>This module hasn't been built yet. It's on the roadmap and will show up here once it's ready &mdash; for now, nothing has been left broken, just clearly marked as not available.</p>
</div>

<?php include __DIR__ . "/../includes/admin-layout-footer.php"; ?>
