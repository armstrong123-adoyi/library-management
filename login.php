<?php
// login.php
session_start();
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/staff-accounts.php";
require_once __DIR__ . "/includes/activity.php";

// Only auto-redirect if this is someone just visiting the page while
// already logged in. A form submission (POST) must always be checked
// properly, even if a stale session happens to exist.
if (isset($_SESSION["user_id"]) && $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: dashboard.php");
    exit();
}

$errors = [];

// Ensures a matching row exists in the users table for a librarian/admin
// (needed so foreign keys like "added_by" and activity logs have a real ID
// to point to), and keeps that row's name/role in sync with this config file.
function sync_staff_user($conn, $full_name, $email, $role) {
    $query = "SELECT id FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $existing = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($existing) {
        $update = "UPDATE users SET full_name = ?, role = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update);
        mysqli_stmt_bind_param($stmt, "ssi", $full_name, $role, $existing["id"]);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $existing["id"];
    }

    // Placeholder password - real authentication for staff never checks
    // this column, it always checks config/staff-accounts.php instead.
    $placeholder_hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

    $insert = "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert);
    mysqli_stmt_bind_param($stmt, "ssss", $full_name, $email, $placeholder_hash, $role);
    mysqli_stmt_execute($stmt);
    $new_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    return $new_id;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Wipe any old session first - every submitted login attempt starts clean
    session_unset();

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {
        $errors[] = "Please enter both email and password.";
    } else {

        // 1. Check admin accounts (all admins share one password)
        if (array_key_exists($email, $ADMIN_ACCOUNTS)) {
            if ($password === ADMIN_SHARED_PASSWORD) {
                $full_name = $ADMIN_ACCOUNTS[$email];
                $user_id = sync_staff_user($conn, $full_name, $email, "admin");

                $_SESSION["user_id"] = $user_id;
                $_SESSION["full_name"] = $full_name;
                $_SESSION["role"] = "admin";

                header("Location: dashboard.php");
                exit();
            } else {
                $errors[] = "Incorrect password.";
            }

        // 2. Check librarian accounts (each has their own password)
        } elseif (array_key_exists($email, $LIBRARIAN_ACCOUNTS)) {
            $librarian = $LIBRARIAN_ACCOUNTS[$email];
            if ($password === $librarian["password"]) {
                $user_id = sync_staff_user($conn, $librarian["name"], $email, "librarian");

                $_SESSION["user_id"] = $user_id;
                $_SESSION["full_name"] = $librarian["name"];
                $_SESSION["role"] = "librarian";

                header("Location: dashboard.php");
                exit();
            } else {
                $errors[] = "Incorrect password.";
            }

        // 3. Otherwise, check the students table in the database
        } else {
            $query = "SELECT id, full_name, email, password, role, photo_path, account_status FROM users WHERE email = ? AND role = 'student'";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if ($user && $user["account_status"] === "suspended") {
                $errors[] = "This account has been suspended. Please contact the library.";
            } elseif ($user && password_verify($password, $user["password"])) {
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["role"] = $user["role"];
                $_SESSION["photo_path"] = $user["photo_path"];

                header("Location: dashboard.php");
                exit();
            } else {
                $errors[] = "Invalid email or password.";
            }
        }
    }
}

include __DIR__ . "/includes/header.php";
?>

<script>document.body.classList.add("auth-body");</script>

<div class="auth-split-wrapper">
    <div class="auth-image-panel">
        <img src="/library-management/assets/images/book-hero.webp" alt="">
        <div class="auth-image-overlay">
            <div class="brand-mark">
                <i class="ti ti-book-2"></i>
            </div>
            <h1><span class="brand-accent">Smart</span> Library<br>Management System</h1>
            <p>Manage books, members, borrowings, and fines &mdash; all in one place, built for students, librarians, and administrators.</p>
        </div>
    </div>

    <div class="auth-form-panel">
        <div class="auth-card">
            <h2>Welcome back</h2>
            <p class="auth-subtitle">Sign in to the library system</p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="you@example.com" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Your password" required>

                <button type="submit" class="auth-submit-btn">Log in</button>
            </form>

            <p class="auth-footer-link">New student? <a href="register.php">Register here</a></p>
        </div>
    </div>
</div>

<?php include __DIR__ . "/includes/footer.php"; ?>
