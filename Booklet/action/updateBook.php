<?php
        session_start();
        include("../DBConnection.php");
        $dbc = getConnection();

        $bookId = $_POST['bookId'];
        $title = trim($_POST['title']);
        $author = trim($_POST['author']);
        $pages = $_POST['noPages'];
        $description = $_POST['description'];

        $errors = [];

        /* VALIDATION */
        if(empty($title) || empty($author) || empty($pages)){
            $errors[] = "Please fill in all fields.";
        }

      if($pages <= 0){
            $errors[] = "Pages must be greater than 0.";
        }

        /* IF ERROR RETURN BACK */
      if(!empty($errors)){
            $_SESSION['error'] = implode("<br>", $errors);
            header("Location: ../editBook.php?id=$bookId");
            exit;
        }

        /* UPDATE */
        mysqli_query($dbc,"
        UPDATE dbProj_books 
        SET title='$title',
            author='$author',
            noPages='$pages',
            description='$description'
        WHERE bookId=$bookId
        ");

        /* SUCCESS */
        $_SESSION['success'] = "Book updated successfully.";
        header("Location: ../bookDetails.php?id=$bookId");
      exit;
?>