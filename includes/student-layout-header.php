<?php
// includes/student-layout-header.php
// Include this instead of includes/header.php on student-facing pages.
// Requires: $_SESSION already validated (require_login() called by the
// page BEFORE including this), and $active_page set to highlight the
// right sidebar item, e.g. $active_page = "dashboard".
if (!isset($active_page)) { $active_page = ""; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Library System</title>
    <link rel="stylesheet" href="/library-management/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/dist/tabler-icons.min.css">
</head>
<body class="admin-body">

<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-sidebar-brand">
            <i class="ti ti-book-2"></i> <span class="brand-accent">Smart</span>&nbsp;Library
        </div>

        <nav class="admin-nav">
            <a href="/library-management/dashboard.php" class="<?php echo $active_page === 'dashboard' ? 'active' : ''; ?>">
                <i class="ti ti-layout-dashboard"></i> Dashboard
            </a>
            <a href="/library-management/my-profile.php" class="<?php echo $active_page === 'profile' ? 'active' : ''; ?>">
                <i class="ti ti-user"></i> My Profile
            </a>

            <div class="admin-nav-section">Library</div>
            <a href="/library-management/books.php" class="<?php echo $active_page === 'books' ? 'active' : ''; ?>">
                <i class="ti ti-books"></i> Borrow Books
            </a>
            <a href="/library-management/dashboard.php#borrowing" class="<?php echo $active_page === 'history' ? 'active' : ''; ?>">
                <i class="ti ti-history"></i> Borrowing History
            </a>
            <a href="/library-management/dashboard.php#recommendations" class="<?php echo $active_page === 'recommendations' ? 'active' : ''; ?>">
                <i class="ti ti-star"></i> Recommendations
            </a>

            <div class="admin-nav-section">Account</div>
            <a href="/library-management/dashboard.php#notifications" class="<?php echo $active_page === 'notifications' ? 'active' : ''; ?>">
                <i class="ti ti-bell"></i> Notifications
            </a>
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
