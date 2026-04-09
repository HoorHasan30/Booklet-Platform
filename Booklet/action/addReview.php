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

    <style>
        body {
            background: #FFF7EE;
            font-family: Arial;
        }

        .container {
            width: 80%;
            margin: auto;
        }

        .review-card, .comment {
            background: #D3C1B4;
            padding: 15px;
            margin-top: 15px;
            border-radius: 10px;
        }

        .main-btn {
            background: #483434;
            color: white;
            padding: 10px;
            border: none;
            margin-top: 15px;
        }

        .hidden { display: none; }

        .delete-btn {
            background: red;
            color: white;
            border: none;
            margin-top: 10px;
        }
    </style>
</head>

<body>

<div class="container">

<?php
$review = $conn->query("
    SELECT r.*, u.firstName
    FROM dbProj_reviews r
    JOIN dbProj_users u ON r.userId = u.userId
    WHERE r.reviewId = $reviewId
")->fetch_assoc();
?>

<div class="review-card">
    <b><?= $review['firstName'] ?></b>
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
");

while($c = $comments->fetch_assoc()){
?>
<div class="comment">
    <b><?= $c['firstName'] ?></b>
    <p><?= $c['commentText'] ?></p>

    <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'){ ?>
        <form method="POST" action="action/deleteComment.php">
            <input type="hidden" name="commentId" value="<?= $c['commentId'] ?>">
            <button class="delete-btn">Delete</button>
        </form>
    <?php } ?>
</div>
<?php } ?>

<!-- ADD COMMENT -->
<?php if(isset($_SESSION['userId'])){ ?>
<button class="main-btn" onclick="toggleComment()">+ Add Comment</button>

<div id="commentForm" class="hidden">
    <form method="POST" action="action/addComment.php">
        <input type="hidden" name="reviewId" value="<?= $reviewId ?>">
        <input type="text" name="commentText" required>
        <button>Send</button>
    </form>
</div>
<?php } ?>

</div>

<script>
function toggleComment(){
    let f = document.getElementById("commentForm");
    f.style.display = f.style.display === "block" ? "none" : "block";
}
</script>

</body>
</html>