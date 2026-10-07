<?php
// admin/notifications.php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/activity.php";
require_once __DIR__ . "/../includes/notifications.php";

require_staff();

$errors = [];
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $type = trim($_POST["type"]);
    $title = trim($_POST["title"]);
    $message = trim($_POST["message"]);
    $recipient_type = trim($_POST["recipient_type"]);
    $recipient_value = null;
    $channels = ["dashboard"]; // always saved to dashboard
    if (isset($_POST["send_email"])) $channels[] = "email";
    if (isset($_POST["send_sms"])) $channels[] = "sms";

    $allowed_types = ["book_due", "fine_reminder", "new_book", "announcement"];
    $allowed_recipients = ["all", "department", "student"];

    if (empty($title) || empty($message)) {
        $errors[] = "Title and message are required.";
    }
    if (!in_array($type, $allowed_types)) {
        $errors[] = "Invalid notification type.";
    }
    if (!in_array($recipient_type, $allowed_recipients)) {
        $errors[] = "Invalid recipient option.";
    }

    if ($recipient_type === "department") {
        $recipient_value = trim($_POST["department_value"] ?? "");
        if (empty($recipient_value)) $errors[] = "Please choose a department.";
    } elseif ($recipient_type === "student") {
        $recipient_value = trim($_POST["student_value"] ?? "");
        if (empty($recipient_value)) $errors[] = "Please choose a student.";
    }

    if (empty($errors)) {
        $sent = send_notification($conn, $_SESSION["user_id"], $type, $title, $message, $recipient_type, $recipient_value, $channels, false);
        if ($sent) {
            $recipient_label = $recipient_type === "all" ? "all students"
                : ($recipient_type === "department" ? "department: $recipient_value" : "one student");
            log_activity($conn, $_SESSION["user_id"], "Sent notification", "$title (to $recipient_label)");
            $success = true;
        } else {
            $errors[] = "Something went wrong sending the notification.";
        }
    }
}

$departments = [];
$dept_result = mysqli_query($conn, "SELECT DISTINCT department FROM users WHERE role='student' AND department IS NOT NULL AND department != '' ORDER BY department");
while ($row = mysqli_fetch_assoc($dept_result)) { $departments[] = $row["department"]; }

$students = [];
$student_result = mysqli_query($conn, "SELECT id, full_name, email FROM users WHERE role='student' ORDER BY full_name");
while ($row = mysqli_fetch_assoc($student_result)) { $students[] = $row; }

$sent_notifications = get_all_notifications($conn, 30);

$type_meta = [
    "announcement" => ["icon" => "ti-speakerphone", "label" => "General Announcement", "color" => "i-teal"],
    "new_book"     => ["icon" => "ti-books",        "label" => "New Book Alert",        "color" => "i-purple"],
    "book_due"     => ["icon" => "ti-calendar-due", "label" => "Book Due Reminder",     "color" => "i-amber"],
    "fine_reminder"=> ["icon" => "ti-cash",          "label" => "Fine Reminder",          "color" => "i-red"],
];

$active_page = "notifications";
include __DIR__ . "/../includes/admin-layout-header.php";
?>

<h2>Notifications</h2>
<p class="admin-page-subtitle">Send announcements to students, or check what's already gone out automatically.</p>

<?php if (!EMAIL_ENABLED || !SMS_ENABLED): ?>
<div class="admin-panel" style="border-color:#f59e0b;margin-bottom:20px">
    <p style="color:#fbbf24;font-size:13px;margin:0">
        <i class="ti ti-alert-triangle"></i>
        <?php if (!EMAIL_ENABLED && !SMS_ENABLED): ?>
            Email and SMS delivery aren't configured yet &mdash; notifications will still save to students' dashboards, but email/SMS checkboxes below won't actually deliver until you set up credentials in <code>config/config.php</code>.
        <?php elseif (!EMAIL_ENABLED): ?>
            Email delivery isn't configured yet &mdash; set it up in <code>config/config.php</code>.
        <?php else: ?>
            SMS delivery isn't configured yet &mdash; set it up in <code>config/config.php</code>.
        <?php endif; ?>
    </p>
</div>
<?php endif; ?>

<div class="two-col-row">
    <div class="admin-panel">
        <h3>Compose notification</h3>

        <?php if ($success): ?>
            <p class="alert alert-success">Notification sent successfully.</p>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error"><ul>
                <?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?>
            </ul></div>
        <?php endif; ?>

        <form method="POST" action="notifications.php">
            <label>Type</label>
            <div class="notify-type-grid">
                <?php foreach ($type_meta as $key => $meta): ?>
                    <label class="notify-type-card">
                        <input type="radio" name="type" value="<?php echo $key; ?>" <?php echo $key === "announcement" ? "checked" : ""; ?>>
                        <span class="kpi-icon <?php echo $meta["color"]; ?>"><i class="ti <?php echo $meta["icon"]; ?>"></i></span>
                        <span class="notify-type-label"><?php echo $meta["label"]; ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <p class="hint-text">"Due" and "Fine" reminders are manual for now &mdash; auto-triggers arrive once the Borrowing module exists.</p>

            <label for="title">Title</label>
            <input type="text" id="title" name="title" placeholder="e.g. Library closed this Friday" required>

            <label for="message">Message</label>
            <textarea id="message" name="message" rows="4" placeholder="Write the notification message..." required></textarea>

            <label for="recipient_type">Send to</label>
            <select id="recipient_type" name="recipient_type" required>
                <option value="all">All Students</option>
                <option value="department">A Specific Department</option>
                <option value="student">A Single Student</option>
            </select>

            <div id="department-field" style="display:none">
                <label for="department_value">Department</label>
                <select id="department_value" name="department_value">
                    <option value="">-- Select department --</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="student-field" style="display:none">
                <label for="student_value">Student</label>
                <select id="student_value" name="student_value">
                    <option value="">-- Select student --</option>
                    <?php foreach ($students as $s): ?>
                        <option value="<?php echo $s["id"]; ?>"><?php echo htmlspecialchars($s["full_name"] . " (" . $s["email"] . ")"); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <label>Delivery channels</label>
            <div class="notify-channels">
                <label class="notify-channel-check"><input type="checkbox" checked disabled> <i class="ti ti-layout-dashboard"></i> Dashboard (always on)</label>
                <label class="notify-channel-check"><input type="checkbox" name="send_email"> <i class="ti ti-mail"></i> Email</label>
                <label class="notify-channel-check"><input type="checkbox" name="send_sms"> <i class="ti ti-device-mobile-message"></i> SMS</label>
            </div>

            <button type="submit" class="auth-submit-btn">Send notification</button>
        </form>
    </div>

    <div class="admin-panel">
        <h3>Recently sent</h3>
        <?php if (empty($sent_notifications)): ?>
            <p class="hint-text">No notifications sent yet.</p>
        <?php else: ?>
            <ul class="admin-activity-list">
                <?php foreach ($sent_notifications as $n):
                    $meta = $type_meta[$n["type"]] ?? $type_meta["announcement"];
                ?>
                    <li>
                        <div class="activity-dot <?php echo $meta["color"]; ?>">
                            <i class="ti <?php echo $meta["icon"]; ?>"></i>
                        </div>
                        <div class="activity-text">
                            <strong><?php echo htmlspecialchars($n["title"]); ?></strong>
                            <?php if ($n["is_automatic"]): ?><span class="book-tag" style="margin-left:6px;font-size:10px">AUTO</span><?php endif; ?>
                            <br><?php echo htmlspecialchars($n["message"]); ?>
                            <div class="notify-meta-row">
                                <span><?php echo $n["recipient_type"] === "all" ? "All Students" : ($n["recipient_type"] === "department" ? "Dept: " . htmlspecialchars($n["recipient_value"]) : "1 student"); ?> &middot; <?php echo $n["recipient_count"]; ?> recipient(s)</span>
                                <span class="notify-channel-badge"><i class="ti ti-layout-dashboard"></i> Dashboard</span>
                                <?php if ($n["email_sent_count"] > 0): ?><span class="notify-channel-badge success"><i class="ti ti-mail"></i> <?php echo $n["email_sent_count"]; ?> emailed</span><?php endif; ?>
                                <?php if ($n["sms_sent_count"] > 0): ?><span class="notify-channel-badge success"><i class="ti ti-device-mobile-message"></i> <?php echo $n["sms_sent_count"]; ?> texted</span><?php endif; ?>
                            </div>
                            <span class="activity-when">By <?php echo htmlspecialchars($n["sender_name"]); ?> &middot; <?php echo date("M j, g:i A", strtotime($n["created_at"])); ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const recipientType = document.getElementById("recipient_type");
    const departmentField = document.getElementById("department-field");
    const studentField = document.getElementById("student-field");

    function updateRecipientFields() {
        departmentField.style.display = recipientType.value === "department" ? "block" : "none";
        studentField.style.display = recipientType.value === "student" ? "block" : "none";
    }
    recipientType.addEventListener("change", updateRecipientFields);
    updateRecipientFields();
});
</script>

<?php include __DIR__ . "/../includes/admin-layout-footer.php"; ?>
