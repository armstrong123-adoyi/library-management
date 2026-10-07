<?php
// admin/settings.php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/activity.php";
require_once __DIR__ . "/../includes/settings.php";

require_admin(); // policy changes affect everyone - admin only

$success = false;
$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fine_per_day = trim($_POST["fine_per_day"] ?? "");
    $loan_duration_days = trim($_POST["loan_duration_days"] ?? "");
    $max_books_per_student = trim($_POST["max_books_per_student"] ?? "");
    $library_open_time = trim($_POST["library_open_time"] ?? "");
    $library_close_time = trim($_POST["library_close_time"] ?? "");

    if (!is_numeric($fine_per_day) || $fine_per_day < 0) $errors[] = "Fine per day must be a positive number.";
    if (!ctype_digit($loan_duration_days) || $loan_duration_days < 1) $errors[] = "Loan duration must be a whole number of days.";
    if (!ctype_digit($max_books_per_student) || $max_books_per_student < 1) $errors[] = "Max books per student must be at least 1.";

    if (empty($errors)) {
        update_setting($conn, "fine_per_day", $fine_per_day);
        update_setting($conn, "loan_duration_days", $loan_duration_days);
        update_setting($conn, "max_books_per_student", $max_books_per_student);
        update_setting($conn, "library_open_time", $library_open_time);
        update_setting($conn, "library_close_time", $library_close_time);

        log_activity($conn, $_SESSION["user_id"], "Updated system settings", "Fine: $fine_per_day/day, Loan: $loan_duration_days days, Max books: $max_books_per_student");
        $success = true;
    }
}

$settings = get_all_settings($conn);

$active_page = "settings";
include __DIR__ . "/../includes/admin-layout-header.php";
?>

<h2>Settings</h2>
<p class="admin-page-subtitle">These control real behavior across the whole system - changing the fine rate here immediately affects every future overdue calculation.</p>

<?php if ($success): ?>
    <p class="alert alert-success">Settings updated successfully.</p>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-error"><ul><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="admin-panel" style="max-width:520px">
    <form method="POST" action="settings.php">
        <label for="fine_per_day">Fine per day (overdue)</label>
        <input type="number" step="0.01" min="0" id="fine_per_day" name="fine_per_day" value="<?php echo htmlspecialchars($settings["fine_per_day"] ?? 50); ?>" required>

        <label for="loan_duration_days">Maximum borrow days</label>
        <input type="number" min="1" id="loan_duration_days" name="loan_duration_days" value="<?php echo htmlspecialchars($settings["loan_duration_days"] ?? 14); ?>" required>

        <label for="max_books_per_student">Maximum books per student</label>
        <input type="number" min="1" id="max_books_per_student" name="max_books_per_student" value="<?php echo htmlspecialchars($settings["max_books_per_student"] ?? 3); ?>" required>

        <div class="field-row">
            <div class="field">
                <label for="library_open_time">Library opens</label>
                <input type="time" id="library_open_time" name="library_open_time" value="<?php echo htmlspecialchars($settings["library_open_time"] ?? "08:00"); ?>">
            </div>
            <div class="field">
                <label for="library_close_time">Library closes</label>
                <input type="time" id="library_close_time" name="library_close_time" value="<?php echo htmlspecialchars($settings["library_close_time"] ?? "18:00"); ?>">
            </div>
        </div>

        <button type="submit" class="auth-submit-btn">Save settings</button>
    </form>
</div>

<?php include __DIR__ . "/../includes/admin-layout-footer.php"; ?>
