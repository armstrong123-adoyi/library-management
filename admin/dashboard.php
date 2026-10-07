<?php
// admin/dashboard.php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/activity.php";
require_once __DIR__ . "/../includes/borrowing.php";

require_staff();

// --- Real KPI numbers from the database ---
$total_books = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM books"))["c"];
$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role='student'"))["c"];
$total_librarians = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role='librarian'"))["c"];
$new_registrations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE created_at >= NOW() - INTERVAL 7 DAY"))["c"];

$borrowed_books = count_active_borrows($conn);
$overdue_books = count_overdue_borrows($conn);
$outstanding_fines = sum_outstanding_fines($conn);
$returned_today = count_returned_today($conn);

// --- Real chart data ---
// Books by category (genre)
$genre_result = mysqli_query($conn, "SELECT COALESCE(NULLIF(genre,''), 'Uncategorized') AS genre, COUNT(*) AS c FROM books GROUP BY genre ORDER BY c DESC LIMIT 6");
$genre_labels = [];
$genre_counts = [];
while ($row = mysqli_fetch_assoc($genre_result)) {
    $genre_labels[] = $row["genre"];
    $genre_counts[] = intval($row["c"]);
}

// New registrations over the last 7 days
$reg_labels = [];
$reg_counts = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date("Y-m-d", strtotime("-$i days"));
    $reg_labels[] = date("D", strtotime($day));
    $count_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE DATE(created_at) = '$day'"));
    $reg_counts[] = intval($count_row["c"]);
}

$recent_activity = get_recent_activity($conn, $_SESSION["user_id"], 6);

$active_page = "dashboard";
include __DIR__ . "/../includes/admin-layout-header.php";
?>

<h2>Dashboard</h2>
<p class="admin-page-subtitle">Welcome back, <?php echo htmlspecialchars($_SESSION["full_name"]); ?>. Here's what's happening in the library.</p>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-icon i-teal"><i class="ti ti-books"></i></div>
        <div class="kpi-value"><?php echo number_format($total_books); ?></div>
        <div class="kpi-label">Total Books</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon i-purple"><i class="ti ti-users"></i></div>
        <div class="kpi-value"><?php echo number_format($total_students); ?></div>
        <div class="kpi-label">Total Students</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon i-amber"><i class="ti ti-user-star"></i></div>
        <div class="kpi-value"><?php echo number_format($total_librarians); ?></div>
        <div class="kpi-label">Librarians</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon i-green"><i class="ti ti-user-plus"></i></div>
        <div class="kpi-value"><?php echo number_format($new_registrations); ?></div>
        <div class="kpi-label">New Registrations (7d)</div>
    </div>
</div>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-icon i-purple"><i class="ti ti-arrows-exchange"></i></div>
        <div class="kpi-value"><?php echo number_format($borrowed_books); ?></div>
        <div class="kpi-label">Borrowed Books</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon i-red"><i class="ti ti-alert-triangle"></i></div>
        <div class="kpi-value"><?php echo number_format($overdue_books); ?></div>
        <div class="kpi-label">Overdue Books</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon i-amber"><i class="ti ti-cash"></i></div>
        <div class="kpi-value"><?php echo number_format($outstanding_fines, 2); ?></div>
        <div class="kpi-label">Outstanding Fines</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon i-green"><i class="ti ti-corner-down-left"></i></div>
        <div class="kpi-value"><?php echo number_format($returned_today); ?></div>
        <div class="kpi-label">Returned Today</div>
    </div>
</div>

<div class="charts-row">
    <div class="admin-panel">
        <h3>New Registrations (Last 7 Days)</h3>
        <canvas id="registrationsChart" height="90"></canvas>
    </div>
    <div class="admin-panel">
        <h3>Books by Category</h3>
        <?php if (empty($genre_labels)): ?>
            <p class="hint-text">Add some books to see this chart.</p>
        <?php else: ?>
            <canvas id="categoryChart" height="90"></canvas>
        <?php endif; ?>
    </div>
</div>

<div class="two-col-row">
    <div class="admin-panel">
        <div class="admin-panel-header">
            <h3>Recent Activity</h3>
            <a href="activity-log.php">View all</a>
        </div>
        <?php if (empty($recent_activity)): ?>
            <p class="hint-text">No activity recorded yet.</p>
        <?php else: ?>
            <ul class="admin-activity-list">
                <?php foreach ($recent_activity as $entry): ?>
                    <li>
                        <div class="activity-dot i-teal" style="background:rgba(34,211,238,0.15);color:#22d3ee">
                            <i class="ti ti-point"></i>
                        </div>
                        <div class="activity-text">
                            <strong><?php echo htmlspecialchars($entry["action"]); ?></strong>
                            <?php if (!empty($entry["description"])): ?>
                                &mdash; <?php echo htmlspecialchars($entry["description"]); ?>
                            <?php endif; ?>
                            <span class="activity-when"><?php echo date("M j, g:i A", strtotime($entry["created_at"])); ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="admin-panel">
        <h3>Quick Actions</h3>
        <div class="quick-actions-list">
            <a href="add-book.php" class="quick-action-btn"><span><i class="ti ti-plus"></i>Add New Book</span> <i class="ti ti-chevron-right"></i></a>
            <a href="scan-circulation.php" class="quick-action-btn"><span><i class="ti ti-scan"></i>Scan Circulation</span> <i class="ti ti-chevron-right"></i></a>
            <a href="borrowing.php" class="quick-action-btn"><span><i class="ti ti-arrows-exchange"></i>Manage Borrowing</span> <i class="ti ti-chevron-right"></i></a>
            <a href="reservations.php" class="quick-action-btn"><span><i class="ti ti-bookmark"></i>Reservations</span> <i class="ti ti-chevron-right"></i></a>
            <a href="fines.php" class="quick-action-btn"><span><i class="ti ti-cash"></i>Manage Fines</span> <i class="ti ti-chevron-right"></i></a>
            <a href="manage-books.php" class="quick-action-btn"><span><i class="ti ti-books"></i>Manage Books</span> <i class="ti ti-chevron-right"></i></a>
            <a href="manage-users.php" class="quick-action-btn"><span><i class="ti ti-users"></i>View All Users</span> <i class="ti ti-chevron-right"></i></a>
            <a href="activity-log.php" class="quick-action-btn"><span><i class="ti ti-history"></i>View Activity Log</span> <i class="ti ti-chevron-right"></i></a>
            <a href="notifications.php" class="quick-action-btn"><span><i class="ti ti-bell"></i>Send Notification</span> <i class="ti ti-chevron-right"></i></a>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('registrationsChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($reg_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($reg_counts); ?>,
            backgroundColor: '#22d3ee',
            borderRadius: 6,
            maxBarThickness: 32
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { color: '#94a3b8', precision: 0 }, grid: { color: '#334155' } },
            x: { ticks: { color: '#94a3b8' }, grid: { display: false } }
        }
    }
});

<?php if (!empty($genre_labels)): ?>
new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($genre_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($genre_counts); ?>,
            backgroundColor: ['#22d3ee', '#6366f1', '#fbbf24', '#4ade80', '#f87171', '#a78bfa']
        }]
    },
    options: {
        plugins: { legend: { position: 'bottom', labels: { color: '#cbd5e1', boxWidth: 12, font: { size: 11 } } } }
    }
});
<?php endif; ?>
</script>

<?php include __DIR__ . "/../includes/admin-layout-footer.php"; ?>
