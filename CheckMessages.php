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
    $response = $conn -> query("SELECT userId FROM users WHERE username = '" . $username ."'");
    if($response -> rowCount() == 0){
      print "User \"" . $username . "\" does not exist";
      return;
    }
    $userId = $response -> fetch()["userId"];

    $filteredWords = explode(", ",$_GET["filter"]);
    $isFilter = $_GET["isFilter"];

    if($_GET["to"]=="True"){

      $statement = $conn -> query("SELECT * FROM messages JOIN messageRecipients
        WHERE messageRecipients.messageId = messages.messageId AND messageRecipients.toUserId = " . $userId);

      if($statement -> rowCount()==0) print "You have no messages, <i>" . $username . "</i>.";
      else {
        print "<div id='displayMessagesHeader'>Here are your messages, <i>" . $username ."</i>:";
        if($isFilter == "true"){
          print "<br><span>Filtered words: " . implode(", ",$filteredWords) . "</span></div><br><br>";
        } else {
          print "</div><br>";
        }

      foreach($statement as $row){
        $wordContains = False;
        foreach($filteredWords as $word){
          if(str_contains($row["body"],$word)){
            $wordContains = True;
          }
        }
        if($wordContains == True) {
          $fromUsername = $conn -> query("SELECT * FROM users WHERE userId = ".$row["fromUserId"]) -> fetch()["username"];
          print "<div class=\"messageElement\">";
          print " ➼ <strong>From " . $fromUsername . ": </strong>" . $row["body"];
          print "</div>";
        }
      }
    }
  } else {
    $statement = $conn -> query("SELECT * FROM messages WHERE messages.fromUserId = ".$userId);
    if($statement -> rowCount()==0) print "<i>" . $username . "</i> has not sent any messages.";
    else {
      print "<div id='displayMessagesHeader'>Here are the messages that you have sent, <i>" . $username ."</i>:";
      if($isFilter == "true"){
        print "<br><span>Filtered words: " . implode(", ",$filteredWords) . "</span></div><br><br>";
      } else {
        print "</div><br>";
      }
      foreach($statement as $row){
        $wordContains = False;
        foreach($filteredWords as $word){
          if(str_contains($row["body"],$word)){
            $wordContains = True;
          }
        }
        if($wordContains == True) {

          $messageId = $row["messageId"];
          $usernames = "";
          $toUsernames = $conn -> query("SELECT * FROM messageRecipients JOIN users WHERE messageRecipients.messageId = $messageId AND users.userID = messageRecipients.toUserId");

          foreach($toUsernames as $u){
            $usernames .= $u["username"] . ", ";
          }

          $usernames = substr($usernames,0,-2);

          print "<div class=\"messageElement\">";
          print "<strong> ➼ To " . $usernames . ":</strong> " . $row["body"];
          print "</div>";
        }
      }
    }
  }
}
catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
