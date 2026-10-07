<?php
// admin/manage-users.php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/activity.php";

require_staff();

$flash = null;
$reset_password_shown = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = intval($_POST["user_id"] ?? 0);

    if (isset($_POST["toggle_suspend"])) {
        $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT account_status, full_name FROM users WHERE id = $user_id AND role = 'student'"));
        if ($check) {
            $new_status = $check["account_status"] === "suspended" ? "active" : "suspended";
            $stmt = mysqli_prepare($conn, "UPDATE users SET account_status = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $new_status, $user_id);
            mysqli_stmt_execute($stmt);
            log_activity($conn, $_SESSION["user_id"], ucfirst($new_status) . " account", $check["full_name"]);
            $flash = ["success" => true, "message" => $check["full_name"] . " is now " . $new_status . "."];
        }
    }

    if (isset($_POST["reset_password"])) {
        $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT full_name FROM users WHERE id = $user_id AND role = 'student'"));
        if ($check) {
            $new_password = substr(bin2hex(random_bytes(4)), 0, 8); // random 8-char temp password
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $hashed, $user_id);
            mysqli_stmt_execute($stmt);
            log_activity($conn, $_SESSION["user_id"], "Reset password", $check["full_name"]);
            $reset_password_shown = ["name" => $check["full_name"], "password" => $new_password];
        }
    }
}

$result = mysqli_query($conn, "SELECT id, full_name, email, role, occupation, department, level, photo_path, account_status, created_at FROM users ORDER BY created_at DESC");

$active_page = "users";
include __DIR__ . "/../includes/admin-layout-header.php";
?>

<h2>All users</h2>
<p class="admin-page-subtitle">Librarian and admin accounts are managed in <code>config/staff-accounts.php</code>, not here. Suspend and password reset apply to student accounts only.</p>

<?php if ($flash): ?>
    <p class="alert alert-success"><?php echo htmlspecialchars($flash["message"]); ?></p>
<?php endif; ?>

<?php if ($reset_password_shown): ?>
    <div class="alert alert-success">
        New temporary password for <strong><?php echo htmlspecialchars($reset_password_shown["name"]); ?></strong>:
        <code style="font-size:15px"><?php echo htmlspecialchars($reset_password_shown["password"]); ?></code>
        <br><span class="hint-text" style="color:#065f46">Copy this now - it won't be shown again. Give it to the student directly.</span>
    </div>
<?php endif; ?>

<div class="admin-panel">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Photo</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Occupation / Dept / Level</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) === 0): ?>
                <tr><td colspan="8" class="empty-state">No users registered yet.</td></tr>
            <?php else: ?>
                <?php while ($user = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td>
                            <?php if (!empty($user["photo_path"])): ?>
                                <img src="/library-management/<?php echo htmlspecialchars($user["photo_path"]); ?>" alt="" class="user-thumb">
                            <?php else: ?>
                                <div class="user-thumb user-thumb-placeholder"><?php echo htmlspecialchars(mb_substr($user["full_name"], 0, 1)); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($user["full_name"]); ?></td>
                        <td><?php echo htmlspecialchars($user["email"]); ?></td>
                        <td><span class="book-tag"><?php echo htmlspecialchars(ucfirst($user["role"])); ?></span></td>
                        <td>
                            <?php
                                $bits = array_filter([$user["occupation"], $user["department"], $user["level"]]);
                                echo $bits ? htmlspecialchars(implode(" · ", $bits)) : "-";
                            ?>
                        </td>
                        <td>
                            <?php if ($user["role"] === "student"): ?>
                                <span class="status-pill <?php echo $user["account_status"] === "suspended" ? "overdue" : "returned"; ?>">
                                    <?php echo ucfirst($user["account_status"]); ?>
                                </span>
                            <?php else: ?>
                                &mdash;
                            <?php endif; ?>
                        </td>
                        <td><?php echo date("M j, Y", strtotime($user["created_at"])); ?></td>
                        <td>
                            <a href="view-user.php?id=<?php echo $user["id"]; ?>">View</a>
                            <?php if ($user["role"] === "student"): ?>
                                <a href="student-qr.php?id=<?php echo $user["id"]; ?>" style="margin-left:6px">QR</a>
                                <form method="POST" action="manage-users.php" style="display:inline;margin-left:6px">
                                    <input type="hidden" name="user_id" value="<?php echo $user["id"]; ?>">
                                    <button type="submit" name="toggle_suspend" value="1" class="small-action-btn" style="background:<?php echo $user["account_status"] === "suspended" ? "linear-gradient(135deg,#22d3ee,#6366f1)" : "#b02a1e"; ?>">
                                        <?php echo $user["account_status"] === "suspended" ? "Reactivate" : "Suspend"; ?>
                                    </button>
                                </form>
                                <form method="POST" action="manage-users.php" style="display:inline;margin-left:6px" onsubmit="return confirm('Generate a new temporary password for this student?');">
                                    <input type="hidden" name="user_id" value="<?php echo $user["id"]; ?>">
                                    <button type="submit" name="reset_password" value="1" class="small-action-btn" style="background:#334155">Reset Password</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . "/../includes/admin-layout-footer.php"; ?>
