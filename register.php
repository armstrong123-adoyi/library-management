<?php
// register.php
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/config/staff-accounts.php";

$errors = [];
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $matric_number = trim($_POST["matric_number"]);
    $occupation = trim($_POST["occupation"]);
    $department = trim($_POST["department"]);
    $phone_number = trim($_POST["phone_number"] ?? "");
    $level = trim($_POST["level"]);

    if (empty($full_name) || empty($email) || empty($password)) {
        $errors[] = "Full name, email, and password are required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid, active email address.";
    }

    if (strlen($password) < MIN_PASSWORD_LENGTH) {
        $errors[] = "Password must be at least " . MIN_PASSWORD_LENGTH . " characters long.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($occupation)) {
        $errors[] = "Please state your occupation.";
    }

    // Handle profile photo upload
    $photo_path = null;
    if (!isset($_FILES["profile_photo"]) || $_FILES["profile_photo"]["error"] === UPLOAD_ERR_NO_FILE) {
        $errors[] = "Please upload a profile photo.";
    } elseif ($_FILES["profile_photo"]["error"] !== UPLOAD_ERR_OK) {
        $errors[] = "There was a problem uploading your photo. Please try again.";
    } else {
        $file = $_FILES["profile_photo"];
        $allowed_types = ["image/jpeg", "image/png", "image/jpg"];
        $max_size = 2 * 1024 * 1024; // 2MB

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detected_type = finfo_file($finfo, $file["tmp_name"]);
        finfo_close($finfo);

        if (!in_array($detected_type, $allowed_types)) {
            $errors[] = "Profile photo must be a JPG or PNG image.";
        } elseif ($file["size"] > $max_size) {
            $errors[] = "Profile photo must be smaller than 2MB.";
        } else {
            $upload_dir = __DIR__ . "/uploads/profile-photos/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $extension = $detected_type === "image/png" ? "png" : "jpg";
            $filename = uniqid("photo_") . "." . $extension;

            if (move_uploaded_file($file["tmp_name"], $upload_dir . $filename)) {
                $photo_path = "uploads/profile-photos/" . $filename;
            } else {
                $errors[] = "Could not save your photo. Please try again.";
            }
        }
    }

    if (array_key_exists($email, $ADMIN_ACCOUNTS) || array_key_exists($email, $LIBRARIAN_ACCOUNTS)) {
        $errors[] = "This email is reserved. Please use a different email.";
    }

    if (empty($errors)) {
        $check_query = "SELECT id FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "An account with this email already exists.";
        }
        mysqli_stmt_close($stmt);
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = "student";

        $insert_query = "INSERT INTO users (full_name, email, password, role, matric_number, occupation, department, level, photo_path, phone_number)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "ssssssssss", $full_name, $email, $hashed_password, $role, $matric_number, $occupation, $department, $level, $photo_path, $phone_number);

        if (mysqli_stmt_execute($stmt)) {
            $success = true;
        } else {
            $errors[] = "Something went wrong. Please try again.";
        }
        mysqli_stmt_close($stmt);
    }
}

include __DIR__ . "/includes/header.php";
?>

<script>document.body.classList.add("auth-body");</script>

<div class="auth-wrapper">
    <div class="auth-card wide">
        <h2>Create your account</h2>
        <p class="auth-subtitle">Join the library in a couple of minutes &mdash; search the catalog, borrow books, and track your fines all in one place.</p>

        <?php if ($success): ?>
            <div class="alert alert-success">You're all set! Your account has been created. <a href="login.php">Log in now &rarr;</a></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" action="register.php" enctype="multipart/form-data">
            <label>Profile photo</label>
            <div class="photo-upload-box">
                <img id="photo-preview" src="" alt="" style="display:none">
                <div id="photo-placeholder" class="photo-placeholder"><i class="ti ti-user"></i></div>
                <div class="photo-upload-controls">
                    <label for="profile_photo" class="photo-upload-btn">Choose image</label>
                    <input type="file" id="profile_photo" name="profile_photo" accept="image/jpeg,image/png" style="display:none" required>
                    <p class="hint-text">JPG or PNG, max 2MB. This photo will be visible to admins on your profile.</p>
                </div>
            </div>

            <label for="full_name">Full name</label>
            <input type="text" id="full_name" name="full_name" placeholder="e.g. Armstrong Ofuowoich" value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>" required>

            <label for="email">Email address</label>
            <input type="email" id="email" name="email" placeholder="you@example.com" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>

            <div class="field-row">
                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="At least <?php echo MIN_PASSWORD_LENGTH; ?> characters" required minlength="<?php echo MIN_PASSWORD_LENGTH; ?>">
                </div>
                <div class="field">
                    <label for="confirm_password">Confirm password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required minlength="<?php echo MIN_PASSWORD_LENGTH; ?>">
                </div>
            </div>

            <div class="field-row">
                <div class="field">
                    <label for="matric_number">Matric number</label>
                    <input type="text" id="matric_number" name="matric_number" placeholder="e.g. ENG/2021/045" value="<?php echo isset($matric_number) ? htmlspecialchars($matric_number) : ''; ?>">
                </div>
                <div class="field">
                    <label for="occupation">Occupation</label>
                    <input type="text" id="occupation" name="occupation" placeholder="e.g. Undergraduate" value="<?php echo isset($occupation) ? htmlspecialchars($occupation) : ''; ?>" required>
                </div>
            </div>

            <div class="field-row">
                <div class="field">
                    <label for="department">Department</label>
                    <input type="text" id="department" name="department" placeholder="e.g. Computer Science" value="<?php echo isset($department) ? htmlspecialchars($department) : ''; ?>">
                </div>
                <div class="field">
                    <label for="level">Level</label>
                    <input type="text" id="level" name="level" placeholder="e.g. 200L" value="<?php echo isset($level) ? htmlspecialchars($level) : ''; ?>">
                </div>
            </div>

            <label for="phone_number">Phone number</label>
            <input type="tel" id="phone_number" name="phone_number" placeholder="e.g. 08012345678" value="<?php echo isset($phone_number) ? htmlspecialchars($phone_number) : ''; ?>">
            <p class="hint-text">Used for SMS reminders about due dates and fines, once that's enabled.</p>

            <button type="submit" class="auth-submit-btn">Create my account</button>
        </form>
        <?php endif; ?>

        <p class="auth-footer-link">Already registered? <a href="login.php">Log in</a></p>
    </div>
</div>

<?php include __DIR__ . "/includes/footer.php"; ?>
