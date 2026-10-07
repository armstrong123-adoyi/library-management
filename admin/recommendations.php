<?php
// admin/recommendations.php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/recommendations.php";
require_once __DIR__ . "/../includes/ml-recommendations.php";

require_staff();

$most_borrowed = get_popular_books($conn, 10);
$recently_added = get_recently_added_books($conn, 10);
$trending_categories = get_trending_categories($conn, 6);
$similar_pairs = get_top_similar_student_pairs($conn, 8);

$active_page = "recommendations";
include __DIR__ . "/../includes/admin-layout-header.php";
?>

<h2>Recommendations</h2>
<p class="admin-page-subtitle">Popularity/trending are plain SQL. Personalized "Recommended for You" and "Also Borrowed" use a real machine learning model - see below.</p>

<div class="admin-panel ml-explainer">
    <strong><i class="ti ti-brain"></i> Machine Learning: Collaborative Filtering (Cosine Similarity, k-NN)</strong><br>
    Every book is represented as a vector of which students borrowed it (and vice versa for students). The system computes
    <strong>cosine similarity</strong> between these vectors - a standard ML distance metric - to find genuinely similar
    books/students purely from real behavior, with no manually written rules and no external AI service. This is the same
    family of algorithm published by Amazon (item-to-item collaborative filtering) and taught in intro ML/data mining
    courses under Recommender Systems. Below is a live sample of what the model has actually learned from your current data.
</div>

<div class="admin-panel" style="margin-bottom:20px">
    <h3>Model output: most similar student pairs (by borrowing pattern)</h3>
    <?php if (empty($similar_pairs)): ?>
        <p class="hint-text">Not enough overlapping borrow history yet for the model to find similar students. Borrow a few books across 2-3 student accounts, with some shared titles, to see this populate.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr><th>Student A</th><th>Student B</th><th>Cosine Similarity</th></tr>
            </thead>
            <tbody>
                <?php foreach ($similar_pairs as $pair): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($pair["student_a_name"]); ?></td>
                        <td><?php echo htmlspecialchars($pair["student_b_name"]); ?></td>
                        <td style="min-width:140px">
                            <?php echo round($pair["similarity"], 3); ?>
                            <div class="similarity-bar-track">
                                <div class="similarity-bar-fill" style="width:<?php echo round($pair["similarity"] * 100); ?>%"></div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="two-col-row">
    <div class="admin-panel">
        <h3>Top 10 Most Borrowed Books</h3>
        <?php if (empty($most_borrowed)): ?>
            <p class="hint-text">No borrow history yet.</p>
        <?php else: ?>
            <ul class="admin-activity-list">
                <?php foreach ($most_borrowed as $i => $b): ?>
                    <li>
                        <div class="activity-dot i-teal"><?php echo $i + 1; ?></div>
                        <div class="activity-text">
                            <strong><?php echo htmlspecialchars($b["title"]); ?></strong> by <?php echo htmlspecialchars($b["author"]); ?>
                            <span class="activity-when"><?php echo $b["borrow_count"]; ?> time(s) borrowed</span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="admin-panel">
        <h3>Trending Categories (last 30 days)</h3>
        <?php if (empty($trending_categories)): ?>
            <p class="hint-text">No borrowing activity in the last 30 days.</p>
        <?php else: ?>
            <ul class="admin-activity-list">
                <?php foreach ($trending_categories as $cat): ?>
                    <li>
                        <div class="activity-dot i-purple"><i class="ti ti-category"></i></div>
                        <div class="activity-text">
                            <strong><?php echo htmlspecialchars($cat["genre"]); ?></strong>
                            <span class="activity-when"><?php echo $cat["c"]; ?> borrow(s)</span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<div class="admin-panel" style="margin-top:20px">
    <h3>Recently Added Books</h3>
    <div class="book-grid">
        <?php foreach ($recently_added as $b): ?>
            <a href="../book-detail.php?id=<?php echo $b["id"]; ?>" class="book-card">
                <div class="book-cover">
                    <?php if (!empty($b["cover_image"])): ?>
                        <img src="<?php echo htmlspecialchars($b["cover_image"]); ?>" alt="">
                    <?php else: ?>
                        <div class="book-cover-placeholder"><?php echo htmlspecialchars(mb_substr($b["title"], 0, 1)); ?></div>
                    <?php endif; ?>
                </div>
                <p class="book-title"><?php echo htmlspecialchars($b["title"]); ?></p>
                <p class="book-author"><?php echo htmlspecialchars($b["author"]); ?></p>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . "/../includes/admin-layout-footer.php"; ?>
