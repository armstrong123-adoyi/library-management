<?php
// admin/activity-log.php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/activity.php";

require_staff();

$all_activity = get_all_activity($conn, 200);

$active_page = "activity";
include __DIR__ . "/../includes/admin-layout-header.php";
?>

<h2>Activity Log</h2>
<p class="admin-page-subtitle">Every action performed by librarians and admins, newest first. Nothing here is ever deleted.</p>

<div class="admin-panel">
    <?php if (empty($all_activity)): ?>
        <p class="hint-text">No activity recorded yet.</p>
    <?php else: ?>
        <ul class="admin-activity-list">
            <?php foreach ($all_activity as $entry): ?>
                <li>
                    <div class="activity-dot" style="background:rgba(34,211,238,0.15);color:#22d3ee">
                        <i class="ti ti-point"></i>
                    </div>
                    <div class="activity-text">
                        <strong><?php echo htmlspecialchars($entry["full_name"]); ?></strong>
                        <span class="book-tag" style="margin-left:6px"><?php echo htmlspecialchars(ucfirst($entry["role"])); ?></span>
                        <br>
                        <?php echo htmlspecialchars($entry["action"]); ?>
                        <?php if (!empty($entry["description"])): ?>
                            &mdash; <?php echo htmlspecialchars($entry["description"]); ?>
                        <?php endif; ?>
                        <span class="activity-when"><?php echo date("F j, Y g:i A", strtotime($entry["created_at"])); ?></span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php include __DIR__ . "/../includes/admin-layout-footer.php"; ?>
