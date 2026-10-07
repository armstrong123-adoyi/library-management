<?php
// dashboard.php
session_start();
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/activity.php";
require_once __DIR__ . "/includes/notifications.php";
require_once __DIR__ . "/includes/borrowing.php";
require_once __DIR__ . "/includes/reservations.php";
require_once __DIR__ . "/includes/recommendations.php";
require_once __DIR__ . "/includes/ml-recommendations.php";

require_login();

// Staff get the admin sidebar dashboard instead
if (is_staff()) {
    header("Location: admin/dashboard.php");
    exit();
}

$dept_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT department FROM users WHERE id = " . intval($_SESSION["user_id"])));
$my_department = $dept_row["department"] ?? "";

$my_notifications = get_notifications_for_student($conn, $_SESSION["user_id"], $my_department, 10);
$my_borrows = get_student_borrows($conn, $_SESSION["user_id"]);
$my_reservations = get_student_reservations($conn, $_SESSION["user_id"]);
$my_recommendations = get_ml_recommendations_for_student($conn, $_SESSION["user_id"], 4);
$used_ml = !empty($my_recommendations);
if (!$used_ml) {
    // Cold start: this student has no borrow history yet for the ML
    // model to learn from, so fall back to genre-matching / popularity.
    $my_recommendations = get_recommendations_for_student($conn, $_SESSION["user_id"], 4);
}

$flash = $_SESSION["borrow_flash"] ?? null;
unset($_SESSION["borrow_flash"]);

// KPI numbers computed from what we already fetched - no extra queries needed
$active_borrows = array_filter($my_borrows, fn($b) => $b["status"] === "borrowed");
$books_returned = count(array_filter($my_borrows, fn($b) => $b["status"] === "returned"));
$outstanding_fine = 0;
foreach ($my_borrows as $b) {
    if ($b["status"] === "returned" && $b["fine_amount"] > 0 && !$b["fine_paid"]) {
        $outstanding_fine += $b["fine_amount"];
    } elseif ($b["status"] === "borrowed" && is_overdue($b)) {
        $outstanding_fine += estimate_current_fine($conn, $b["due_date"]);
    }
}

$active_page = "dashboard";
include __DIR__ . "/includes/student-layout-header.php";
?>

<h2>Dashboard</h2>
<p class="admin-page-subtitle">Welcome back, <?php echo htmlspecialchars($_SESSION["full_name"]); ?>.</p>

<?php if ($flash): ?>
    <p class="alert <?php echo $flash["success"] ? "alert-success" : "alert-error"; ?>"><?php echo htmlspecialchars($flash["message"]); ?></p>
<?php endif; ?>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-icon i-teal"><i class="ti ti-books"></i></div>
        <div class="kpi-value"><?php echo count($active_borrows); ?></div>
        <div class="kpi-label">Borrowed Books</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon i-red"><i class="ti ti-cash"></i></div>
        <div class="kpi-value"><?php echo number_format($outstanding_fine, 2); ?></div>
        <div class="kpi-label">Outstanding Fine</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon i-purple"><i class="ti ti-star"></i></div>
        <div class="kpi-value"><?php echo count($my_recommendations); ?></div>
        <div class="kpi-label">Recommendations</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon i-green"><i class="ti ti-corner-down-left"></i></div>
        <div class="kpi-value"><?php echo $books_returned; ?></div>
        <div class="kpi-label">Books Returned</div>
    </div>
</div>

<div class="two-col-row" id="borrowing">
    <div class="admin-panel">
        <div class="admin-panel-header">
            <h3>Recent Borrowings</h3>
            <a href="books.php">View all</a>
        </div>
        <?php if (empty($my_borrows)): ?>
            <p class="hint-text">You haven't borrowed any books yet. <a href="books.php">Browse the catalog</a> to get started.</p>
        <?php else: ?>
            <ul class="admin-activity-list">
                <?php foreach (array_slice($my_borrows, 0, 5) as $b): ?>
                    <?php $overdue = is_overdue($b); ?>
                    <li>
                        <div class="activity-dot <?php echo $overdue ? 'i-red' : ($b["status"] === 'returned' ? 'i-green' : 'i-teal'); ?>">
                            <i class="ti ti-book"></i>
                        </div>
                        <div class="activity-text">
                            <strong><?php echo htmlspecialchars($b["title"]); ?></strong>
                            <br>
                            <?php if ($b["status"] === "returned"): ?>
                                Returned <?php echo date("M j, Y", strtotime($b["return_date"])); ?>
                                <?php if ($b["fine_amount"] > 0): ?> &mdash; fine <?php echo number_format($b["fine_amount"], 2); ?> <?php echo $b["fine_paid"] ? "(paid)" : "(unpaid)"; ?><?php endif; ?>
                            <?php elseif ($overdue): ?>
                                <span style="color:#f87171;font-weight:600">Overdue</span> since <?php echo date("M j, Y", strtotime($b["due_date"])); ?>
                            <?php else: ?>
                                Due <?php echo date("M j, Y", strtotime($b["due_date"])); ?>
                                <?php if (!$b["renewed"]): ?>
                                    <form method="POST" action="renew-book.php" style="display:inline;margin-left:6px">
                                        <input type="hidden" name="borrow_id" value="<?php echo $b["id"]; ?>">
                                        <button type="submit" class="small-action-btn" style="background:#273449;color:#cbd5e1">Renew</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                            <span class="activity-when">Borrowed <?php echo date("M j, Y", strtotime($b["borrow_date"])); ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="admin-panel" id="recommendations">
        <div class="admin-panel-header">
            <h3>
                Recommended for You
                <?php if ($used_ml): ?>
                    <span class="ml-badge" title="Generated by a Machine Learning model (Item-Based Collaborative Filtering, Cosine Similarity)">
                        <i class="ti ti-brain"></i> AI-Powered
                    </span>
                <?php endif; ?>
            </h3>
        </div>
        <?php if ($used_ml): ?>
            <p class="hint-text" style="margin-bottom:12px">Based on what students with similar borrowing patterns to you have read.</p>
        <?php endif; ?>
        <?php if (empty($my_recommendations)): ?>
            <p class="hint-text">Borrow a few books and we'll start recommending titles you might like.</p>
        <?php else: ?>
            <div class="book-grid">
                <?php foreach ($my_recommendations as $rec): ?>
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
        <?php endif; ?>
    </div>
</div>

<div class="two-col-row" id="notifications">
    <div class="admin-panel">
        <div class="admin-panel-header">
            <h3>Notifications</h3>
        </div>
        <?php if (empty($my_notifications)): ?>
            <p class="hint-text">No notifications for you right now.</p>
        <?php else: ?>
            <ul class="admin-activity-list">
                <?php foreach (array_slice($my_notifications, 0, 5) as $n): ?>
                    <li>
                        <div class="activity-dot i-amber"><i class="ti ti-bell"></i></div>
                        <div class="activity-text">
                            <strong><?php echo htmlspecialchars($n["title"]); ?></strong>
                            <br><?php echo htmlspecialchars($n["message"]); ?>
                            <span class="activity-when"><?php echo date("M j, g:i A", strtotime($n["created_at"])); ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="admin-panel">
        <h3>Quick Actions</h3>
        <div class="quick-actions-list">
            <a href="books.php" class="quick-action-btn"><span><i class="ti ti-books"></i>Borrow a Book</span> <i class="ti ti-chevron-right"></i></a>
            <a href="my-profile.php" class="quick-action-btn"><span><i class="ti ti-user"></i>My Profile</span> <i class="ti ti-chevron-right"></i></a>
        </div>

        <?php if (!empty($my_reservations)): ?>
        <h3 style="margin-top:22px">Your Reservations</h3>
        <ul class="admin-activity-list">
            <?php foreach (array_slice($my_reservations, 0, 3) as $r): ?>
                <li>
                    <div class="activity-dot i-purple"><i class="ti ti-bookmark"></i></div>
                    <div class="activity-text">
                        <strong><?php echo htmlspecialchars($r["title"]); ?></strong>
                        <br>
                        <?php if ($r["status"] === "pending"): ?>
                            <span style="color:#fbbf24">Waiting for a copy</span>
                        <?php elseif ($r["status"] === "approved"): ?>
                            <span style="color:#4ade80">Approved &amp; issued</span>
                        <?php else: ?>
                            <span style="color:#f87171"><?php echo ucfirst($r["status"]); ?></span>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . "/includes/admin-layout-footer.php"; ?>
