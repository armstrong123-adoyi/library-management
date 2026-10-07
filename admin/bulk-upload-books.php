<?php
// admin/bulk-upload-books.php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/activity.php";

require_staff();

$errors = [];
$imported = 0;
$skipped = 0;
$skip_reasons = [];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["csv_file"])) {
    $file = $_FILES["csv_file"];

    if ($file["error"] !== UPLOAD_ERR_OK) {
        $errors[] = "File upload failed. Please try again.";
    } elseif (strtolower(pathinfo($file["name"], PATHINFO_EXTENSION)) !== "csv") {
        $errors[] = "Please upload a .csv file (Excel: use File > Save As > CSV first).";
    } else {
        $handle = fopen($file["tmp_name"], "r");
        $row_number = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $row_number++;
            if ($row_number === 1) continue; // skip header row

            // Expected columns: title, author, isbn, genre, description, total_copies, cover_image
            $title = trim($row[0] ?? "");
            $author = trim($row[1] ?? "");
            $isbn = trim($row[2] ?? "");
            $genre = trim($row[3] ?? "");
            $description = trim($row[4] ?? "");
            $total_copies = intval($row[5] ?? 1);
            if ($total_copies < 1) $total_copies = 1;
            $cover_image = trim($row[6] ?? "");

            if (empty($title) || empty($author)) {
                $skipped++;
                $skip_reasons[] = "Row $row_number: missing title or author.";
                continue;
            }

            $insert = "INSERT INTO books (title, author, isbn, genre, description, total_copies, available_copies, added_by, cover_image)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert);
            mysqli_stmt_bind_param($stmt, "sssssiiis", $title, $author, $isbn, $genre, $description, $total_copies, $total_copies, $_SESSION["user_id"], $cover_image);
            if (mysqli_stmt_execute($stmt)) {
                $imported++;
            } else {
                $skipped++;
                $skip_reasons[] = "Row $row_number: database error.";
            }
        }
        fclose($handle);

        if ($imported > 0) {
            log_activity($conn, $_SESSION["user_id"], "Bulk imported books", "$imported book(s) via CSV");
        }
    }
}

$active_page = "books";
include __DIR__ . "/../includes/admin-layout-header.php";
?>

<h2>Bulk Upload Books</h2>
<p class="admin-page-subtitle">Import many books at once from a CSV file instead of adding them one by one.</p>

<?php if ($imported > 0 || $skipped > 0): ?>
    <p class="alert alert-success"><?php echo $imported; ?> book(s) imported successfully<?php echo $skipped > 0 ? ", $skipped row(s) skipped." : "."; ?></p>
    <?php if (!empty($skip_reasons)): ?>
        <div class="alert alert-error"><ul><?php foreach ($skip_reasons as $r): ?><li><?php echo htmlspecialchars($r); ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error"><ul><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="admin-panel" style="max-width:560px">
    <h3>Upload CSV</h3>
    <p class="hint-text">
        Your CSV needs these columns, in this order, with a header row:<br>
        <code>title, author, isbn, genre, description, total_copies, cover_image</code>
        <br>(<code>cover_image</code> is optional - leave the cell blank if you don't have a URL for that book)
    </p>

    <form method="POST" action="bulk-upload-books.php" enctype="multipart/form-data">
        <label for="csv_file">CSV file</label>
        <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
        <button type="submit" class="auth-submit-btn">Upload and import</button>
    </form>

    <p class="hint-text" style="margin-top:16px">
        Making this in Excel? Fill columns A-G with the headers above, then use
        <strong>File &rarr; Save As &rarr; CSV (Comma delimited)</strong> before uploading.
    </p>
</div>

<?php include __DIR__ . "/../includes/admin-layout-footer.php"; ?>
