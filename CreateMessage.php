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

      $fromUsername = $_GET["from"];
      $toArray = explode(", ",$_GET["to"]);
      $body = $_GET["body"];


      $fromQuery = $conn -> query("SELECT userId FROM users WHERE username = '" . $fromUsername ."'");
      if($fromQuery -> rowCount() == 0 ){
        print "The user \"" . $fromUsername . "\" does not exist.";
        return;
      }
      $fromId = $fromQuery -> fetch()["userId"];

      forEach($toArray as $toUsername){
        $toQuery = $conn -> query("SELECT userId FROM users WHERE username = '" . $toUsername ."'");
        if($toQuery -> rowCount() ==0 ){
          print "The user \"" . $toUsername . "\" does not exist.";
          return;
        }
      }




    $conn -> exec("INSERT INTO messages(body, fromUserId) VALUES ( '" . $body . "','" . $fromId . "')");
    $messageId = $conn -> lastInsertId();

    foreach($toArray as $to){
      $toId = $conn -> query("SELECT userId FROM users WHERE username = '" . $to ."'")
       -> fetch()["userId"];

      $conn -> exec("INSERT INTO messageRecipients(messageId, toUserId) VALUES ('".$messageId."','".$toId."')");
    }
    print "Message sent!";
}
catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
