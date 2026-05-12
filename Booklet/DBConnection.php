
<?php

    function getConnection()
    {
        //change the paramters in the function below to your user id

         $dbc = mysqli_connect('127.0.0.1:3308', 'u202204157', 'Zxcvbnm123@', 'db202204157');

            if (mysqli_connect_errno()) {
                printf("Connect failed: %s\n", mysqli_connect_error());
                die('b0ther');
            }

        return $dbc;
    }

?>

