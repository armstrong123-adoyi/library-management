<?php
// admin/reservations.php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/activity.php";
require_once __DIR__ . "/../includes/borrowing.php";
require_once __DIR__ . "/../includes/reservations.php";
require_once __DIR__ . "/../includes/notifications.php";

require_staff();

$flash = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $reservation_id = intval($_POST["reservation_id"] ?? 0);

    if (isset($_POST["approve"])) {
        $result = approve_reservation($conn, $reservation_id);
        if ($result["success"]) {
            log_activity($conn, $_SESSION["user_id"], "Approved reservation", $result["message"]);
            send_notification($conn, $_SESSION["user_id"], "announcement", "Your reserved book is ready",
                "The book you reserved has been issued to you. Check your dashboard for the due date.",
                "student", strval($result["student_id"]), ["dashboard", "email"], true);
        }
        $flash = $result;
    } elseif (isset($_POST["reject"])) {
        $result = reject_reservation($conn, $reservation_id);
        log_activity($conn, $_SESSION["user_id"], "Rejected reservation", "Reservation #$reservation_id");
        if ($result["student_id"]) {
            send_notification($conn, $_SESSION["user_id"], "announcement", "Reservation update",
                "Unfortunately your book reservation could not be fulfilled at this time.",
                "student", strval($result["student_id"]), ["dashboard"], true);
        }
        $flash = $result;
    }
}

$pending = get_pending_reservations($conn);

$active_page = "reservations";
include __DIR__ . "/../includes/admin-layout-header.php";
?>

<h2>Reservations</h2>
<p class="admin-page-subtitle">Students reserve books that are fully checked out. Approving issues the book immediately if a copy is available, and notifies the student.</p>

<?php if ($flash): ?>
    <p class="alert <?php echo $flash["success"] ? "alert-success" : "alert-error"; ?>"><?php echo htmlspecialchars($flash["message"]); ?></p>
<?php endif; ?>

<div class="admin-panel">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Book</th>
                <th>Requested</th>
                <th>Copies now available</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pending)): ?>
                <tr><td colspan="5" class="empty-state">No pending reservations.</td></tr>
            <?php else: ?>
                <?php foreach ($pending as $r): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r["student_name"]); ?></td>
                        <td><?php echo htmlspecialchars($r["title"]); ?></td>
                        <td><?php echo date("M j, Y", strtotime($r["created_at"])); ?></td>
                        <td><?php echo $r["available_copies"] > 0 ? '<span class="status-pill returned">' . $r["available_copies"] . ' available</span>' : '<span class="status-pill overdue">Still 0</span>'; ?></td>
                        <td>
                            <form method="POST" action="reservations.php" style="display:inline">
                                <input type="hidden" name="reservation_id" value="<?php echo $r["id"]; ?>">
                                <button type="submit" name="approve" value="1" class="small-action-btn">Approve &amp; Issue</button>
                            </form>
                            <form method="POST" action="reservations.php" style="display:inline">
                                <input type="hidden" name="reservation_id" value="<?php echo $r["id"]; ?>">
                                <button type="submit" name="reject" value="1" class="small-action-btn" style="background:#b02a1e;color:#fff">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . "/../includes/admin-layout-footer.php"; ?>
