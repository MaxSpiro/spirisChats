<?php
//See original: https://www.w3schools.com/php/php_mysql_connect.asp
$servername = "localhost";
$username = "root";
$password = "";
$dbName = "chatLog";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbName", $username, $password);

    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


      $username = $_GET["username"];
      $password = $_GET["pw"];
      $email = $_GET["email"];

    $sql = "INSERT INTO `users`(`username`, `password`, `email`)
    VALUES ('$username','$password','$email')";
     // use exec() because no results are returned
     $conn->exec($sql);

     print "Thank you for signing up, $username";


}
catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
