<?php
// admin/view-user.php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";

require_staff();

$user_id = intval($_GET["id"] ?? 0);

$query = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    header("Location: manage-users.php");
    exit();
}

$active_page = "users";
include __DIR__ . "/../includes/admin-layout-header.php";
?>

<a href="manage-users.php" class="back-link">&larr; Back to all users</a>

<section class="book-detail-box">
    <div class="user-profile-photo-wrap">
        <?php if (!empty($user["photo_path"])): ?>
            <img src="/library-management/<?php echo htmlspecialchars($user["photo_path"]); ?>" alt="" class="user-profile-photo">
        <?php else: ?>
            <div class="user-profile-photo user-thumb-placeholder large"><?php echo htmlspecialchars(mb_substr($user["full_name"], 0, 1)); ?></div>
        <?php endif; ?>
    </div>

    <div class="book-detail-info">
        <h2><?php echo htmlspecialchars($user["full_name"]); ?></h2>
        <span class="book-tag"><?php echo htmlspecialchars(ucfirst($user["role"])); ?></span>

        <p class="book-meta" style="margin-top:14px">Email: <?php echo htmlspecialchars($user["email"]); ?></p>
        <?php if (!empty($user["occupation"])): ?>
            <p class="book-meta">Occupation: <?php echo htmlspecialchars($user["occupation"]); ?></p>
        <?php endif; ?>
        <?php if (!empty($user["department"])): ?>
            <p class="book-meta">Department: <?php echo htmlspecialchars($user["department"]); ?></p>
        <?php endif; ?>
        <?php if (!empty($user["level"])): ?>
            <p class="book-meta">Level: <?php echo htmlspecialchars($user["level"]); ?></p>
        <?php endif; ?>
        <p class="book-meta">Joined: <?php echo date("F j, Y", strtotime($user["created_at"])); ?></p>
    </div>
</section>

<?php include __DIR__ . "/../includes/admin-layout-footer.php"; ?>
