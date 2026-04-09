<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include("DBConnection.php");
$conn = getConnection();

if(!isset($_GET['id'])){
    die("Book not found");
}

$bookId = $_GET['id'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Details</title>

    <style>
        body {
            background-color: #FFF7EE;
            font-family: Arial;
        }

        .container {
            width: 80%;
            margin: auto;
        }

        .book-box {
            display: flex;
            gap: 20px;
            background: #D3C1B4;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .book-box img {
            width: 150px;
            height: 200px;
        }

        .main-btn {
            background: #483434;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 8px;
            margin: 20px 0;
            cursor: pointer;
        }

        .review-card {
            background: #D3C1B4;
            padding: 15px;
            margin-top: 15px;
            border-radius: 10px;
        }

        .hidden {
            display: none;
        }

        textarea, select {
            width: 100%;
            padding: 8px;
            margin-top: 10px;
        }

        .delete-btn {
            background: red;
            color: white;
            border: none;
            padding: 5px;
            margin-top: 10px;
        }
    </style>
</head>

<body>

<div class="container">

<?php
$book = $conn->query("
    SELECT b.*, g.genreName 
    FROM dbProj_books b
    JOIN dbProj_Genres g ON b.genreId = g.genreId
    WHERE b.bookId = $bookId
")->fetch_assoc();
?>

<div class="book-box">
    <img src="<?= $book['bookCover'] ?>">
    <div>
        <h2><?= $book['title'] ?></h2>
        <p><?= $book['author'] ?></p>
        <p><?= $book['genreName'] ?></p>
        <p><?= $book['description'] ?></p>
    </div>
</div>

<!-- ADD REVIEW BUTTON -->
<?php if(isset($_SESSION['userId'])){ ?>
<button class="main-btn" onclick="toggleReview()">+ Add Review</button>

<div id="reviewForm" class="hidden">
    <form method="POST" action="action/addReview.php">
        <input type="hidden" name="bookId" value="<?= $bookId ?>">

        <select name="rating">
            <option value="5">★★★★★</option>
            <option value="4">★★★★</option>
            <option value="3">★★★</option>
            <option value="2">★★</option>
            <option value="1">★</option>
        </select>

        <textarea name="reviewText" required></textarea>
        <button>Submit</button>
    </form>
</div>
<?php } ?>

<!-- REVIEWS -->
<h3>Reviews</h3>

<?php
$reviews = $conn->query("
    SELECT r.*, u.firstName 
    FROM dbProj_reviews r
    JOIN dbProj_users u ON r.userId = u.userId
    WHERE r.bookId = $bookId
");

while($r = $reviews->fetch_assoc()){
?>

<div class="review-card">
    <b><?= $r['firstName'] ?></b>
    <p>⭐ <?= $r['rating'] ?></p>
    <p><?= $r['reviewText'] ?></p>

    <a href="reviewDetails.php?reviewId=<?= $r['reviewId'] ?>">View</a>

    <!-- ADMIN DELETE -->
    <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'){ ?>
        <form method="POST" action="action/deleteReview.php">
            <input type="hidden" name="reviewId" value="<?= $r['reviewId'] ?>">
            <button class="delete-btn">Delete</button>
        </form>
    <?php } ?>
</div>

<?php } ?>

</div>

<script>
function toggleReview(){
    let f = document.getElementById("reviewForm");
    f.style.display = f.style.display === "block" ? "none" : "block";
}
</script>

</body>
</html>