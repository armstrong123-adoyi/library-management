<?php
// admin/edit-book.php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/activity.php";

require_staff();

$book_id = intval($_GET["id"] ?? $_POST["book_id"] ?? 0);
$errors = [];

// Fetch existing book
$query = "SELECT * FROM books WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $book_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$book = mysqli_fetch_assoc($result);

if (!$book) {
    header("Location: manage-books.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"]);
    $author = trim($_POST["author"]);
    $isbn = trim($_POST["isbn"]);
    $genre = trim($_POST["genre"]);
    $description = trim($_POST["description"]);
    $cover_image = trim($_POST["cover_image"]);
    $total_copies = intval($_POST["total_copies"]);
    $available_copies = intval($_POST["available_copies"]);

    if (empty($title) || empty($author)) {
        $errors[] = "Title and author are required.";
    }

    if ($total_copies < 1) {
        $errors[] = "Total copies must be at least 1.";
    }

    if ($available_copies > $total_copies) {
        $errors[] = "Available copies cannot exceed total copies.";
    }

    if ($available_copies < 0) {
        $errors[] = "Available copies cannot be negative.";
    }

    if (empty($errors)) {
        $update_query = "UPDATE books SET title=?, author=?, isbn=?, genre=?, description=?, cover_image=?, total_copies=?, available_copies=? WHERE id=?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ssssssiii", $title, $author, $isbn, $genre, $description, $cover_image, $total_copies, $available_copies, $book_id);

        if (mysqli_stmt_execute($stmt)) {
            log_activity($conn, $_SESSION["user_id"], "Edited book", $title);
            header("Location: manage-books.php");
            exit();
        } else {
            $errors[] = "Something went wrong. Please try again.";
        }
    }

    // Keep form populated with submitted values on error
    $book = [
        "id" => $book_id,
        "title" => $title,
        "author" => $author,
        "isbn" => $isbn,
        "genre" => $genre,
        "description" => $description,
        "cover_image" => $cover_image,
        "total_copies" => $total_copies,
        "available_copies" => $available_copies
    ];
}

$active_page = "books";
include __DIR__ . "/../includes/admin-layout-header.php";
?>

<section class="form-box wide">
    <h2>Edit book</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="edit-book.php?id=<?php echo $book_id; ?>">
        <input type="hidden" name="book_id" value="<?php echo $book_id; ?>">

        <label for="title">Title</label>
        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($book["title"]); ?>" required>

        <label for="author">Author</label>
        <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($book["author"]); ?>" required>

        <label for="isbn">ISBN</label>
        <input type="text" id="isbn" name="isbn" value="<?php echo htmlspecialchars($book["isbn"] ?? ''); ?>">

        <label for="genre">Genre</label>
        <input type="text" id="genre" name="genre" value="<?php echo htmlspecialchars($book["genre"] ?? ''); ?>">

        <label for="cover_image">Cover image URL</label>
        <input type="text" id="cover_image" name="cover_image" value="<?php echo htmlspecialchars($book["cover_image"] ?? ''); ?>">

        <label for="description">Description</label>
        <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($book["description"] ?? ''); ?></textarea>

        <label for="total_copies">Total copies</label>
        <input type="number" id="total_copies" name="total_copies" min="1" value="<?php echo intval($book["total_copies"]); ?>" required>

        <label for="available_copies">Available copies</label>
        <input type="number" id="available_copies" name="available_copies" min="0" value="<?php echo intval($book["available_copies"]); ?>" required>

        <button type="submit">Save changes</button>
    </form>

    <p class="form-footer-link"><a href="manage-books.php">&larr; Back to manage books</a></p>
</section>

<?php include __DIR__ . "/../includes/admin-layout-footer.php"; ?>
