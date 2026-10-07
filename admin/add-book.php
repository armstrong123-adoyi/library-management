<?php
// admin/add-book.php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/activity.php";
require_once __DIR__ . "/../includes/notifications.php";

require_staff();

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"]);
    $author = trim($_POST["author"]);
    $isbn = trim($_POST["isbn"]);
    $genre = trim($_POST["genre"]);
    $description = trim($_POST["description"]);
    $cover_image = trim($_POST["cover_image"]);
    $total_copies = intval($_POST["total_copies"]);

    if (empty($title) || empty($author)) {
        $errors[] = "Title and author are required.";
    }

    if ($total_copies < 1) {
        $errors[] = "Total copies must be at least 1.";
    }

    if (empty($errors)) {
        $available_copies = $total_copies; // all copies available when first added
        $added_by = $_SESSION["user_id"];

        $query = "INSERT INTO books (title, author, isbn, genre, description, cover_image, total_copies, available_copies, added_by)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssssssiii", $title, $author, $isbn, $genre, $description, $cover_image, $total_copies, $available_copies, $added_by);

        if (mysqli_stmt_execute($stmt)) {
            log_activity($conn, $added_by, "Added book", $title);

            // Auto-notify all students - this fires by itself, no manual step
            send_notification(
                $conn,
                $added_by,
                "new_book",
                "New book added: " . $title,
                "\"" . $title . "\" by " . $author . " is now available in the library.",
                "all",
                null,
                ["dashboard", "email", "sms"],
                true // is_automatic
            );

            header("Location: manage-books.php");
            exit();
        } else {
            $errors[] = "Something went wrong. Please try again.";
        }
    }
}

$active_page = "add-book";
include __DIR__ . "/../includes/admin-layout-header.php";
?>

<section class="form-box wide">
    <h2>Add a new book</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="add-book.php">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" required>

        <label for="author">Author</label>
        <input type="text" id="author" name="author" value="<?php echo isset($author) ? htmlspecialchars($author) : ''; ?>" required>

        <label for="isbn">ISBN (optional)</label>
        <input type="text" id="isbn" name="isbn" value="<?php echo isset($isbn) ? htmlspecialchars($isbn) : ''; ?>">

        <label for="genre">Genre</label>
        <input type="text" id="genre" name="genre" value="<?php echo isset($genre) ? htmlspecialchars($genre) : ''; ?>" placeholder="e.g. Fiction, Science, History">

        <label for="cover_image">Cover image URL (optional)</label>
        <input type="text" id="cover_image" name="cover_image" value="<?php echo isset($cover_image) ? htmlspecialchars($cover_image) : ''; ?>" placeholder="https://covers.openlibrary.org/b/isbn/9780451524935-L.jpg">
        <p class="hint-text">Free covers, no account needed: use <code>https://covers.openlibrary.org/b/isbn/THE_ISBN-L.jpg</code> - swap in the book's actual ISBN.</p>
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="4"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>

        <label for="total_copies">Total copies</label>
        <input type="number" id="total_copies" name="total_copies" min="1" value="<?php echo isset($total_copies) ? intval($total_copies) : 1; ?>" required>

        <button type="submit">Add book</button>
    </form>

    <p class="form-footer-link"><a href="manage-books.php">&larr; Back to manage books</a></p>
</section>

<?php include __DIR__ . "/../includes/admin-layout-footer.php"; ?>
