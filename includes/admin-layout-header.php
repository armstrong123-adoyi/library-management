<?php
// includes/admin-layout-header.php
// Include this instead of includes/header.php on admin/librarian pages.
// Requires: $_SESSION already validated (require_staff/require_admin called
// by the page BEFORE including this), and $active_page set to highlight
// the right sidebar item, e.g. $active_page = "dashboard";
if (!isset($active_page)) { $active_page = ""; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Library System - Admin</title>
    <link rel="stylesheet" href="/library-management/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/dist/tabler-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="admin-body">

<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-sidebar-brand">
            <i class="ti ti-book-2"></i> <span class="brand-accent">Smart</span>&nbsp;Library
        </div>

        <nav class="admin-nav">
            <a href="/library-management/admin/dashboard.php" class="<?php echo $active_page === 'dashboard' ? 'active' : ''; ?>">
                <i class="ti ti-layout-dashboard"></i> Dashboard
            </a>

            <div class="admin-nav-section">Users</div>
            <a href="/library-management/admin/manage-users.php" class="<?php echo $active_page === 'users' ? 'active' : ''; ?>">
                <i class="ti ti-users"></i> All Users
            </a>

            <div class="admin-nav-section">Books</div>
            <a href="/library-management/admin/manage-books.php" class="<?php echo $active_page === 'books' ? 'active' : ''; ?>">
                <i class="ti ti-books"></i> Manage Books
            </a>
            <a href="/library-management/admin/add-book.php" class="<?php echo $active_page === 'add-book' ? 'active' : ''; ?>">
                <i class="ti ti-plus"></i> Add Book
            </a>
            <a href="/library-management/admin/categories.php" class="<?php echo $active_page === 'categories' ? 'active' : ''; ?>">
                <i class="ti ti-category"></i> Categories
            </a>

            <div class="admin-nav-section">Operations</div>
            <a href="/library-management/admin/scan-circulation.php" class="<?php echo $active_page === 'scan' ? 'active' : ''; ?>">
                <i class="ti ti-scan"></i> Scan Circulation
            </a>
            <a href="/library-management/admin/borrowing.php" class="<?php echo $active_page === 'borrowing' ? 'active' : ''; ?>">
                <i class="ti ti-arrows-exchange"></i> Borrowing
            </a>
            <a href="/library-management/admin/fines.php" class="<?php echo $active_page === 'fines' ? 'active' : ''; ?>">
                <i class="ti ti-cash"></i> Fines
            </a>
            <a href="/library-management/admin/reservations.php" class="<?php echo $active_page === 'reservations' ? 'active' : ''; ?>">
                <i class="ti ti-bookmark"></i> Reservations
            </a>
            <a href="/library-management/admin/recommendations.php" class="<?php echo $active_page === 'recommendations' ? 'active' : ''; ?>">
                <i class="ti ti-star"></i> Recommendations
            </a>

            <div class="admin-nav-section">System</div>
            <a href="/library-management/admin/coming-soon.php?feature=Reports" class="soon">
                <i class="ti ti-report"></i> Reports <span class="soon-badge">Soon</span>
            </a>
            <a href="/library-management/admin/notifications.php" class="<?php echo $active_page === 'notifications' ? 'active' : ''; ?>">
                <i class="ti ti-bell"></i> Notifications
            </a>
            <a href="/library-management/admin/activity-log.php" class="<?php echo $active_page === 'activity' ? 'active' : ''; ?>">
                <i class="ti ti-history"></i> Activity Log
            </a>
            <?php if ($_SESSION["role"] === "admin"): ?>
                <a href="/library-management/admin/settings.php" class="<?php echo $active_page === 'settings' ? 'active' : ''; ?>">
                    <i class="ti ti-settings"></i> Settings
                </a>
            <?php endif; ?>
        </nav>

        <div class="admin-sidebar-footer">
            <div class="admin-user-chip">
                <?php if (!empty($_SESSION["photo_path"])): ?>
                    <img src="/library-management/<?php echo htmlspecialchars($_SESSION["photo_path"]); ?>" class="admin-user-avatar-img" alt="">
                <?php else: ?>
                    <div class="admin-user-avatar"><?php echo htmlspecialchars(mb_substr($_SESSION["full_name"], 0, 1)); ?></div>
                <?php endif; ?>
                <div>
                    <div class="admin-user-name"><?php echo htmlspecialchars($_SESSION["full_name"]); ?></div>
                    <div class="admin-user-role"><?php echo htmlspecialchars(ucfirst($_SESSION["role"])); ?></div>
                </div>
            </div>
            <a href="/library-management/logout.php" class="admin-logout-link"><i class="ti ti-logout"></i> Log out</a>
        </div>
    </aside>

    <div class="admin-mobile-topbar">
        <i class="ti ti-book-2"></i>
        <span><span class="brand-accent">Smart</span> Library</span>
    </div>

    <main class="admin-main">
