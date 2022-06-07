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

    if($_GET["all"]=="true"){
      $query = $conn -> query("SELECT * FROM `users`");
      print "<div id='displayMessagesHeader'>Here are all of the users in the database:<br>
      <span>Click on a user to automatically put them in the \"To\" field.</span></div><br><br>";
      foreach($query as $user){
        print "<div class='userElement' onclick='changeTo(\"" . $user["username"]."\")'> ➼ " . $user["username"] . "</div>";
      }
    } else {
      $userId = $_GET["userId"];
      $query = $conn -> query("SELECT * FROM `users` WHERE userID = " . $userId);
      if($query -> rowCount() == 0){
        print "No user has that ID, try another one!";
        return;
      }
       print "User <i>" . $query -> fetch()["username"] . "</i> has a userID of " . $userId;
    }





}
catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
