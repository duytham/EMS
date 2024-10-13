<?php
    $server = 'localhost';
    $user = 'root';
    $pass = 'admin';
    $db = 'EMS';

    $conn = new mysqLi($server, $user, $pass, $db);
    if ($conn) { 
       mysqLi_query($conn, "Set names 'utf8' "); 
    }
    else {
        echo 'Not Connected';
    }
?>