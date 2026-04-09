<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include("DBConnection.php");

$conn = getConnection(); 

if(!isset($_GET['reviewId'])){
    die("Review not found");
}

$reviewId = $_GET['reviewId'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Review Details</title>
    <link rel="stylesheet" href="BookletCSS.css">
</head>

<body>

<div class="container">

<?php
// GET REVIEW
$reviewQuery = "
    SELECT r.*, u.firstName, u.lastName
    FROM dbProj_reviews r
    JOIN dbProj_users u ON r.userId = u.userId
    WHERE r.reviewId = $reviewId
";

$result = $conn->query($reviewQuery);

if(!$result){
    die("Query error: " . $conn->error);
}

$review = $result->fetch_assoc();

if(!$review){
    die("No review found");
}
?>

<div class="review-card">
    <h3><?= $review['firstName'] ?> <?= $review['lastName'] ?></h3>
    <p>⭐ <?= $review['rating'] ?></p>
    <p><?= $review['reviewText'] ?></p>
</div>

<h3>Comments</h3>

<?php
$comments = $conn->query("
    SELECT c.*, u.firstName
    FROM dbProj_comments c
    JOIN dbProj_users u ON c.userId = u.userId
    WHERE c.reviewId = $reviewId
    ORDER BY c.createdAt ASC
");

if(!$comments){
    die("Comments query error: " . $conn->error);
}

if($comments->num_rows == 0){
    echo "<p>No comments yet.</p>";
}

while($c = $comments->fetch_assoc()){
?>
    <div class="comment">
        <b><?= $c['firstName'] ?>:</b>
        <p><?= $c['commentText'] ?></p>
    </div>
<?php } ?>

<?php if(isset($_SESSION['userId'])){ ?>
<form method="POST" action="action/addComment.php">
    <input type="hidden" name="reviewId" value="<?= $reviewId ?>">
    <input type="text" name="commentText" placeholder="Write a comment..." required>
    <button name="addComment">Send</button>
</form>
<?php } ?>

</div>

</body>
</html>