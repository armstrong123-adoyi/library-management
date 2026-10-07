<?php
// admin/scan-circulation.php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/activity.php";
require_once __DIR__ . "/../includes/borrowing.php";

require_staff();

$flash = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["issue_action"])) {
        $student_id = intval($_POST["student_id"] ?? 0);
        $book_id = intval($_POST["book_id"] ?? 0);

        if ($student_id <= 0 || $book_id <= 0) {
            $flash = ["success" => false, "message" => "Scan or enter both a student and a book first."];
        } else {
            $result = borrow_book($conn, $student_id, $book_id);
            if ($result["success"]) {
                log_activity($conn, $_SESSION["user_id"], "Issued book via scan", $result["message"]);
            }
            $flash = $result;
        }
    } elseif (isset($_POST["return_action"])) {
        $book_id = intval($_POST["return_book_id"] ?? 0);

        $stmt = mysqli_prepare($conn, "SELECT id FROM borrow_records WHERE book_id = ? AND status = 'borrowed' ORDER BY borrow_date ASC LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $book_id);
        mysqli_stmt_execute($stmt);
        $record = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$record) {
            $flash = ["success" => false, "message" => "No active loan found for that book."];
        } else {
            $result = return_book($conn, $record["id"]);
            if ($result["success"]) {
                log_activity($conn, $_SESSION["user_id"], "Returned book via scan", $result["message"]);
            }
            $flash = $result;
        }
    }
}

$active_page = "scan";
include __DIR__ . "/../includes/admin-layout-header.php";
?>

<h2>Scan Circulation</h2>
<p class="admin-page-subtitle">Fast Issue/Return using a book's or student's QR code - point the camera, or type the ID manually if you don't have a camera handy.</p>

<?php if ($flash): ?>
    <p class="alert <?php echo $flash["success"] ? "alert-success" : "alert-error"; ?>"><?php echo htmlspecialchars($flash["message"]); ?></p>
<?php endif; ?>

<div class="two-col-row">
    <div class="admin-panel">
        <h3><i class="ti ti-login"></i> Issue a book</h3>
        <p class="hint-text">Scan the student's QR card first, then the book's QR label.</p>

        <div id="reader-issue" style="max-width:320px;margin:10px 0"></div>
        <button type="button" class="small-action-btn" onclick="toggleScanner('issue')">Start / Stop Camera</button>

        <form method="POST" action="scan-circulation.php" id="issue-form" style="margin-top:14px">
            <label for="student_id">Student ID</label>
            <input type="text" id="student_id" name="student_id" placeholder="Scan or type student ID" required>

            <label for="book_id">Book ID</label>
            <input type="text" id="book_id" name="book_id" placeholder="Scan or type book ID" required>

            <button type="submit" name="issue_action" value="1" class="auth-submit-btn">Confirm Issue</button>
        </form>
    </div>

    <div class="admin-panel">
        <h3><i class="ti ti-logout"></i> Return a book</h3>
        <p class="hint-text">Scan the book's QR label - we'll find the matching active loan automatically.</p>

        <div id="reader-return" style="max-width:320px;margin:10px 0"></div>
        <button type="button" class="small-action-btn" onclick="toggleScanner('return')">Start / Stop Camera</button>

        <form method="POST" action="scan-circulation.php" id="return-form" style="margin-top:14px">
            <label for="return_book_id">Book ID</label>
            <input type="text" id="return_book_id" name="return_book_id" placeholder="Scan or type book ID" required>

            <button type="submit" name="return_action" value="1" class="auth-submit-btn">Confirm Return</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let scanners = { issue: null, return: null };

function parseScannedText(text) {
    const parts = text.split(":");
    return parts.length === 2 ? { type: parts[0], id: parts[1] } : null;
}

function onIssueScan(decodedText) {
    const parsed = parseScannedText(decodedText);
    if (!parsed) return;

    if (parsed.type === "STUDENT") {
        document.getElementById("student_id").value = parsed.id;
    } else if (parsed.type === "BOOK") {
        document.getElementById("book_id").value = parsed.id;
    }
}

function onReturnScan(decodedText) {
    const parsed = parseScannedText(decodedText);
    if (!parsed || parsed.type !== "BOOK") return;
    document.getElementById("return_book_id").value = parsed.id;
}

function toggleScanner(which) {
    const elementId = "reader-" + which;
    const callback = which === "issue" ? onIssueScan : onReturnScan;

    if (scanners[which]) {
        scanners[which].stop().then(() => {
            scanners[which] = null;
            document.getElementById(elementId).innerHTML = "";
        });
        return;
    }

    scanners[which] = new Html5Qrcode(elementId);
    scanners[which].start(
        { facingMode: "environment" },
        { fps: 10, qrbox: 220 },
        callback,
        () => {} // ignore per-frame scan errors
    ).catch(() => {
        alert("Could not access the camera. You can still type IDs manually below.");
        scanners[which] = null;
    });
}
</script>

<?php include __DIR__ . "/../includes/admin-layout-footer.php"; ?>
