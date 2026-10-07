<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Smart Library Management System</title>
    <link rel="stylesheet" href="/library-management/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/dist/tabler-icons.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<header class="site-header">
    <h1><a href="/library-management/index.php" class="site-title-link"><span class="brand-accent">Smart</span> Library Management System</a></h1>
    <nav class="site-nav">
        <?php if (isset($_SESSION["user_id"])): ?>
            <a href="/library-management/books.php">Catalog</a>
            <a href="/library-management/dashboard.php">Dashboard</a>
            <?php if (in_array($_SESSION["role"], ["admin", "librarian"])): ?>
                <a href="/library-management/admin/manage-books.php">Manage Books</a>
            <?php endif; ?>
            <?php if ($_SESSION["role"] === "admin"): ?>
                <a href="/library-management/admin/manage-users.php">Manage Users</a>
            <?php endif; ?>
            <span class="nav-user">Hi, <?php echo htmlspecialchars($_SESSION["full_name"]); ?></span>
            <a href="/library-management/logout.php">Log out</a>
        <?php else: ?>
            <a href="/library-management/login.php">Log in</a>
            <a href="/library-management/register.php">Register</a>
        <?php endif; ?>
    </nav>
</header>
<main class="container">
