<?php
    session_start();
    include("DBConnection.php");
    $dbc = getConnection();

    if(!isset($_GET['reviewId'])){
        die("Review not found");
    }

    $reviewId = $_GET['reviewId'];

    // GET REVIEW 
    // PREPARE QUERY
        $reviewQuery = "SELECT r.*, u.firstName, r.bookId
                        FROM dbProj_reviews r
                        JOIN dbProj_users u ON r.userId = u.userId
                        WHERE r.reviewId = ?";

        $reviewStmt = mysqli_prepare($dbc, $reviewQuery);

        // BIND PARAMETER
        mysqli_stmt_bind_param($reviewStmt, "i", $reviewId);

        // EXECUTE QUERY
        mysqli_stmt_execute($reviewStmt);

        // GET RESULT
        $reviewResult = mysqli_stmt_get_result($reviewStmt);

        $review = mysqli_fetch_assoc($reviewResult);

    // GET COMMENTS 
    // PREPARE QUERY
        $commentsQuery = "SELECT c.*, u.firstName
                          FROM dbProj_comments c
                          JOIN dbProj_users u ON c.userId = u.userId
                          WHERE c.reviewId = ?
                          ORDER BY c.createdAt ASC";

        $commentsStmt = mysqli_prepare($dbc, $commentsQuery);

        // BIND PARAMETER
        mysqli_stmt_bind_param($commentsStmt, "i", $reviewId);

        // EXECUTE QUERY
        mysqli_stmt_execute($commentsStmt);

        // GET RESULT
        $comments = mysqli_stmt_get_result($commentsStmt);
        ?>

<!DOCTYPE html>
<html>
    <head>
        <title>Review Details</title>
        <link rel="stylesheet" href="BookletCSS.css">
        
        <script>
             // Open comment modal to allow user to add a comment
            function openComment(){
                document.getElementById("commentModal").style.display = "flex";
            }
            function closeComment(){
                document.getElementById("commentModal").style.display = "none";
            }
             // Open login modal (for users not logged in)
            function openLogin(){
                document.getElementById("loginModal").style.display = "flex";
            }
            function closeLogin(){
                document.getElementById("loginModal").style.display = "none";
            }
 // Open delete modal and decide whether deleting a review or a comment
            function openDelete(id, type){
                document.getElementById("deleteModal").style.display = "flex";
                document.getElementById("deleteId").value = id;

                let form = document.getElementById("deleteForm");
    // Choose correct delete action based on type
                if(type === 'review'){
                    form.action = "action/deleteReview.php";
                } else {
                    form.action = "action/deleteComment.php";
                }
            }

            function closeDelete(){
                document.getElementById("deleteModal").style.display = "none";
            }

            /* CLOSE MODALS WHEN CLICK OUTSIDE */
            window.onclick = function(e){
                let commentModal = document.getElementById("commentModal");
                let deleteModal = document.getElementById("deleteModal");
                let loginModal = document.getElementById("loginModal");

                if(e.target == commentModal) commentModal.style.display = "none";
                if(e.target == deleteModal) deleteModal.style.display = "none";
                if(e.target == loginModal) loginModal.style.display = "none";
            }
        </script>
    </head>

    <body>

        <?php if(isset($_SESSION['success'])){ ?>

        <div id="success-msg" class="success-msg">
            <?= $_SESSION['success'] ?>
        </div>

        <?php unset($_SESSION['success']); } ?>

        <!-- LOGIN MODAL -->
        <div id="loginModal" class="modal">
          <div class="modal-content">
            <span class="close" onclick="closeLogin()">✖</span>
            <h3>You need to sign in</h3>
            <p>Please login to continue</p>
            <a href="Login.php" class="login-btn">Login</a>
          </div>
        </div>

        <!-- DELETE MODAL -->
        <div id="deleteModal" class="modal">
          <div class="modal-content">
            <span class="close" onclick="closeDelete()">✖</span>

            <h3>Are you sure?</h3>
            <p>This action cannot be undone.</p>

            <form method="POST" id="deleteForm">
                <input type="hidden" name="id" id="deleteId">
                <input type="hidden" name="reviewId" value="<?= $reviewId ?>">

                <div class="modal-actions">
                    <button type="submit" class="delete-confirm">Yes, Delete</button>
                    <button type="button" class="cancel-btn" onclick="closeDelete()">Cancel</button>
                </div>
            </form>
          </div>
        </div>

        <?php
        if(isset($_SESSION['role'])){
            if($_SESSION['role'] == 'Creator'){
                include("CreatorNavBar.php");
            } elseif($_SESSION['role'] == 'Admin'){
                include("AdminNavBar.php");
            } else {
                include("VisitorNavBar.php");
            }
        } else {
            include("VisitorNavBar.php");
        }
        ?>

        <div class="container">

        <a href="bookDetails.php?id=<?= $review['bookId'] ?>" class="back-btn">←</a>

        <!-- REVIEW -->
        <div class="review-card1">

            <div class="review-stars">
                <?= str_repeat("⭐", $review['rating']) ?>
            </div>

            <p class="review-text1"><?= $review['reviewText'] ?></p>
            <small class="review-user">By: <?= $review['firstName'] ?></small>

            <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'Admin'){ ?>
                <button class="delete-btn"
                    onclick="openDelete(<?= $review['reviewId'] ?>, 'review')">
                    Delete
                </button>
            <?php } ?>

        </div>

        <!-- COMMENTS -->
        <div class="comments-header">
            <h3 class="space">Comments</h3>

        <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'Creator'){ ?>
            <button class="comment-btn" onclick="openComment()">Add Comment</button>
        <?php } elseif(!isset($_SESSION['role'])) { ?>
            <button class="comment-btn" onclick="openLogin()">Add Comment</button>
        <?php } ?>
        </div>

        <!-- NO COMMENTS -->
        <?php if(mysqli_num_rows($comments) == 0){ ?>

            <p class="no-comments">No comments for this review.</p>

        <?php } else { ?>

            <?php while($c = mysqli_fetch_assoc($comments)){ ?>
            <div class="comment-card">

                <p><?= $c['commentText'] ?></p>
                <small>By: <?= $c['firstName'] ?></small>

                <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'Admin'){ ?>
                <button class="delete-btn"
                    onclick="openDelete(<?= $c['commentId'] ?>, 'comment')">
                    Delete
                </button>
                <?php } ?>

            </div>
            <?php } ?>

        <?php } ?>

        </div>

        <!-- COMMENT MODAL -->
        <div id="commentModal" class="modal">
        <div class="modal-content">

        <span class="close" onclick="closeComment()">✖</span>

        <form method="POST" action="action/addComment.php">
            <input type="hidden" name="reviewId" value="<?= $reviewId ?>">
            <textarea name="commentText" placeholder="  Write a comment..." required></textarea>
            <button class="save-btn">Save</button>
        </form>

        </div>
        </div>

        <?php include("Footer.php"); ?>
<?php
    mysqli_stmt_close($reviewStmt);
    mysqli_stmt_close($commentsStmt);

    mysqli_close($dbc);
?>
    </body>
</html>