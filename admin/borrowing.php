<?php
// admin/borrowing.php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/activity.php";
require_once __DIR__ . "/../includes/borrowing.php";

require_staff();

$flash = null;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["return_id"])) {
    $borrow_id = intval($_POST["return_id"]);
    $result = return_book($conn, $borrow_id);
    if ($result["success"]) {
        log_activity($conn, $_SESSION["user_id"], "Marked book returned", $result["message"]);
    }
    $flash = $result;
}

$status_filter = $_GET["status"] ?? "all";
$borrows = get_all_borrows($conn, $status_filter);

$active_page = "borrowing";
include __DIR__ . "/../includes/admin-layout-header.php";
?>

<h2>Borrowing</h2>
<p class="admin-page-subtitle">Every borrow record, across every student. Mark a book returned here to close the loan and calculate any fine automatically.</p>

<?php if ($flash): ?>
    <p class="alert <?php echo $flash["success"] ? "alert-success" : "alert-error"; ?>"><?php echo htmlspecialchars($flash["message"]); ?></p>
<?php endif; ?>

<div class="admin-panel">
    <div class="admin-header-row" style="margin-bottom:16px">
        <div class="filter-tabs">
            <a href="borrowing.php?status=all" class="<?php echo $status_filter === 'all' ? 'active' : ''; ?>">All</a>
            <a href="borrowing.php?status=borrowed" class="<?php echo $status_filter === 'borrowed' ? 'active' : ''; ?>">Currently Borrowed</a>
            <a href="borrowing.php?status=overdue" class="<?php echo $status_filter === 'overdue' ? 'active' : ''; ?>">Overdue</a>
            <a href="borrowing.php?status=returned" class="<?php echo $status_filter === 'returned' ? 'active' : ''; ?>">Returned</a>
        </div>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Book</th>
                <th>Borrowed</th>
                <th>Due</th>
                <th>Status</th>
                <th>Fine</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($borrows)): ?>
                <tr><td colspan="7" class="empty-state">No records found.</td></tr>
            <?php else: ?>
                <?php foreach ($borrows as $b): ?>
                    <?php $overdue = is_overdue($b); ?>
                    <tr>
                        <td><?php echo htmlspecialchars($b["student_name"]); ?></td>
                        <td><?php echo htmlspecialchars($b["title"]); ?></td>
                        <td><?php echo date("M j, Y", strtotime($b["borrow_date"])); ?></td>
                        <td><?php echo date("M j, Y", strtotime($b["due_date"])); ?></td>
                        <td>
                            <?php if ($b["status"] === "returned"): ?>
                                <span class="status-pill returned">Returned</span>
                            <?php elseif ($overdue): ?>
                                <span class="status-pill overdue">Overdue</span>
                            <?php else: ?>
                                <span class="status-pill borrowed">Borrowed</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($b["status"] === "returned" && $b["fine_amount"] > 0): ?>
                                <?php echo number_format($b["fine_amount"], 2); ?>
                            <?php elseif ($overdue): ?>
                                ~<?php echo number_format(estimate_current_fine($conn, $b["due_date"]), 2); ?>
                            <?php else: ?>
                                &mdash;
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($b["status"] === "borrowed"): ?>
                                <form method="POST" action="borrowing.php?status=<?php echo $status_filter; ?>" style="display:inline">
                                    <input type="hidden" name="return_id" value="<?php echo $b["id"]; ?>">
                                    <button type="submit" class="small-action-btn">Mark Returned</button>
                                </form>
                            <?php else: ?>
                                &mdash;
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . "/../includes/admin-layout-footer.php"; ?>
