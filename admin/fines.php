<?php
// admin/fines.php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/activity.php";
require_once __DIR__ . "/../includes/borrowing.php";

require_staff();

$flash = null;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["mark_paid_id"])) {
    $id = intval($_POST["mark_paid_id"]);
    if (mark_fine_paid($conn, $id)) {
        log_activity($conn, $_SESSION["user_id"], "Marked fine as paid", "Record #$id");
        $flash = ["success" => true, "message" => "Fine marked as paid."];
    } else {
        $flash = ["success" => false, "message" => "Something went wrong."];
    }
}

$all_fines = get_all_fines($conn);
$outstanding = array_filter($all_fines, fn($f) => !$f["fine_paid"]);
$paid = array_filter($all_fines, fn($f) => $f["fine_paid"]);
$total_outstanding = array_sum(array_column($outstanding, "fine_amount"));

$active_page = "fines";
include __DIR__ . "/../includes/admin-layout-header.php";
?>

<h2>Fines</h2>
<p class="admin-page-subtitle">Automatically calculated from overdue returns at <?php echo number_format(get_setting($conn, "fine_per_day", 50), 2); ?> per day late.</p>

<?php if ($flash): ?>
    <p class="alert <?php echo $flash["success"] ? "alert-success" : "alert-error"; ?>"><?php echo htmlspecialchars($flash["message"]); ?></p>
<?php endif; ?>

<div class="kpi-grid" style="grid-template-columns:repeat(2,1fr);margin-bottom:20px">
    <div class="kpi-card">
        <div class="kpi-icon i-red"><i class="ti ti-cash"></i></div>
        <div class="kpi-value"><?php echo number_format($total_outstanding, 2); ?></div>
        <div class="kpi-label">Total Outstanding</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon i-green"><i class="ti ti-check"></i></div>
        <div class="kpi-value"><?php echo count($paid); ?></div>
        <div class="kpi-label">Fines Paid</div>
    </div>
</div>

<div class="admin-panel">
    <h3>Outstanding fines</h3>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Book</th>
                <th>Days Overdue</th>
                <th>Fine</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($outstanding)): ?>
                <tr><td colspan="6" class="empty-state">No outstanding fines.</td></tr>
            <?php else: ?>
                <?php foreach ($outstanding as $f):
                    $days = round((strtotime($f["is_estimate"] ? date("Y-m-d") : $f["return_date"]) - strtotime($f["due_date"])) / 86400);
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($f["student_name"]); ?></td>
                        <td><?php echo htmlspecialchars($f["title"]); ?></td>
                        <td><?php echo $days; ?></td>
                        <td><?php echo number_format($f["fine_amount"], 2); ?><?php echo $f["is_estimate"] ? " (still accruing)" : ""; ?></td>
                        <td><span class="status-pill overdue">Unpaid</span></td>
                        <td>
                            <?php if (!$f["is_estimate"]): ?>
                                <form method="POST" action="fines.php" style="display:inline">
                                    <input type="hidden" name="mark_paid_id" value="<?php echo $f["id"]; ?>">
                                    <button type="submit" class="small-action-btn">Mark Paid</button>
                                </form>
                            <?php else: ?>
                                <span class="hint-text">Return book first</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="admin-panel" style="margin-top:20px">
    <h3>Payment history</h3>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Book</th>
                <th>Fine</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($paid)): ?>
                <tr><td colspan="4" class="empty-state">No payments recorded yet.</td></tr>
            <?php else: ?>
                <?php foreach ($paid as $f): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($f["student_name"]); ?></td>
                        <td><?php echo htmlspecialchars($f["title"]); ?></td>
                        <td><?php echo number_format($f["fine_amount"], 2); ?></td>
                        <td><span class="status-pill returned">Paid</span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . "/../includes/admin-layout-footer.php"; ?>
