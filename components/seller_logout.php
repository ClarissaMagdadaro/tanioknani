<?php
    include 'connect.php';

    setcookie('seller_id', '', time() - 1, '/');
    header('location: ../seller_panel/login.php');

?>