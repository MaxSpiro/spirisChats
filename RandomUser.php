<?php
//See original: https://www.w3schools.com/php/php_mysql_connect.asp
$servername = "localhost";
$username = "root";
$password = "";
$dbName = "chatLog";


    $conn = new PDO("mysql:host=$servername;dbname=$dbName", $username, $password);



    $users = $conn -> query("SELECT * FROM users");
    $user = $users -> fetchAll()[rand(0,$users -> rowCount()-1)];
    print "The random user generated is <i>" . $user["username"] . "</i>. Try sending them a message!";




?>
