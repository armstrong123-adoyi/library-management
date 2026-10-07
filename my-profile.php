<?php
// my-profile.php
session_start();
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/borrowing.php";
require_once __DIR__ . "/includes/recommendations.php";

require_login();

if (is_staff()) {
    header("Location: admin/dashboard.php");
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $_SESSION["user_id"]);
mysqli_stmt_execute($stmt);
$me = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$my_borrows = get_student_borrows($conn, $_SESSION["user_id"]);
$active_borrows = array_filter($my_borrows, fn($b) => $b["status"] === "borrowed");
$books_returned = count(array_filter($my_borrows, fn($b) => $b["status"] === "returned"));
$recommendation_count = count(get_recommendations_for_student($conn, $_SESSION["user_id"], 4));

$outstanding_fine = 0;
foreach ($my_borrows as $b) {
    if ($b["status"] === "returned" && $b["fine_amount"] > 0 && !$b["fine_paid"]) {
        $outstanding_fine += $b["fine_amount"];
    } elseif ($b["status"] === "borrowed" && is_overdue($b)) {
        $outstanding_fine += estimate_current_fine($conn, $b["due_date"]);
    }
}

$active_page = "profile";
include __DIR__ . "/includes/student-layout-header.php";
?>

<h2>My Profile</h2>
<p class="admin-page-subtitle">Your account details and library activity at a glance.</p>

<div class="admin-panel">
    <div class="profile-header-row">
        <?php if (!empty($me["photo_path"])): ?>
            <img src="/library-management/<?php echo htmlspecialchars($me["photo_path"]); ?>" alt="" class="profile-header-photo">
        <?php else: ?>
            <div class="profile-header-photo profile-header-photo-placeholder"><?php echo htmlspecialchars(mb_substr($me["full_name"], 0, 1)); ?></div>
        <?php endif; ?>

        <div class="profile-header-info">
            <h3 style="margin:0 0 10px"><i class="ti ti-id-badge"></i> Personal Information</h3>
            <div class="profile-info-grid">
                <div><span class="profile-label">Full Name</span><span class="profile-value"><?php echo htmlspecialchars($me["full_name"]); ?></span></div>
                <div><span class="profile-label">Email Address</span><span class="profile-value"><?php echo htmlspecialchars($me["email"]); ?></span></div>
                <div><span class="profile-label">Matric Number</span><span class="profile-value"><?php echo htmlspecialchars($me["matric_number"] ?? "-") ?: "-"; ?></span></div>
                <div><span class="profile-label">Occupation</span><span class="profile-value"><?php echo htmlspecialchars($me["occupation"] ?? "") ?: "-"; ?></span></div>
                <div><span class="profile-label">Department</span><span class="profile-value"><?php echo htmlspecialchars($me["department"] ?? "") ?: "-"; ?></span></div>
                <div><span class="profile-label">Level</span><span class="profile-value"><?php echo htmlspecialchars($me["level"] ?? "") ?: "-"; ?></span></div>
                <div><span class="profile-label">Date Registered</span><span class="profile-value"><?php echo date("F j, Y", strtotime($me["created_at"])); ?></span></div>
                <div><span class="profile-label">Account Status</span><span class="status-pill returned">Active</span></div>
            </div>
        </div>
    </div>
</div>

<div class="kpi-grid" style="margin-top:20px">
    <div class="kpi-card">
        <div class="kpi-icon i-teal"><i class="ti ti-books"></i></div>
        <div class="kpi-value"><?php echo count($active_borrows); ?></div>
        <div class="kpi-label">Borrowed Books</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon i-green"><i class="ti ti-corner-down-left"></i></div>
        <div class="kpi-value"><?php echo $books_returned; ?></div>
        <div class="kpi-label">Books Returned</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon i-red"><i class="ti ti-cash"></i></div>
        <div class="kpi-value"><?php echo number_format($outstanding_fine, 2); ?></div>
        <div class="kpi-label">Outstanding Fine</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon i-purple"><i class="ti ti-star"></i></div>
        <div class="kpi-value"><?php echo $recommendation_count; ?></div>
        <div class="kpi-label">Recommendations</div>
    </div>
</div>

<div class="admin-panel" style="margin-top:20px">
    <div class="admin-panel-header">
        <h3>Recent Activity</h3>
        <a href="dashboard.php">Full history</a>
    </div>
    <?php if (empty($my_borrows)): ?>
        <p class="hint-text">No activity yet.</p>
    <?php else: ?>
        <ul class="admin-activity-list">
            <?php foreach (array_slice($my_borrows, 0, 5) as $b): ?>
                <li>
                    <div class="activity-dot <?php echo $b["status"] === "returned" ? "i-green" : "i-teal"; ?>">
                        <i class="ti ti-book"></i>
                    </div>
                    <div class="activity-text">
                        <strong><?php echo $b["status"] === "returned" ? "Returned" : "Borrowed"; ?></strong>
                        "<?php echo htmlspecialchars($b["title"]); ?>"
                        <span class="activity-when"><?php echo date("M j, Y", strtotime($b["status"] === "returned" ? $b["return_date"] : $b["borrow_date"])); ?></span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php include __DIR__ . "/includes/admin-layout-footer.php"; ?>
