const express = require('express');
const app = express();
const http = require('http');
const server = http.createServer(app);
const { Server } = require('socket.io');
const io = new Server(server);
const mysql = require('mysql');
const util = require( 'util' );


function makeDb( config ) { // Database wrapper to create promises instead of callback functions
  const connection = mysql.createPool( config );
  return {
    query( sql, args ) {
      return util.promisify( connection.query )
        .call( connection, sql, args );
    },
    close() {
      return util.promisify( connection.end ).call( connection );
    }
  };
}

const conn = makeDb({
  host: "us-cdbr-east-05.cleardb.net",
  user: "bc92d718ad46e1",
  password: "b612d3a5",
  database: "heroku_c255c3ac503266d",
  connectionLimit: 100
});


app.get('/', (req, res) => {
  res.sendFile(__dirname + '/page.html');
});
app.use(express.static('.'));

io.on('connection', (socket) => {

  socket.on('listAllUsers',function(){
    (async () => {
      try{
        const result = await conn.query("SELECT `username` FROM users");
        socket.emit('listAllUsers',result);
      } catch(err){
        throw err;
      }
    })();
  });

  socket.on('createUser',function(data){
    (async () => {
      try{
        const usersWithSameName = await conn.query("SELECT * FROM users WHERE username = ?",[data["username"]]);
        if(usersWithSameName.length == 0){
          const result = await conn.query("INSERT INTO `users`(`username`, `password`) VALUES (?,?)",[data["username"],data["password"]]);
          var responseText = "Thank you for signing up, "+data["username"]+"."
          socket.emit('createUser',{"response":responseText,"username":data["username"]});
        } else {
          socket.emit('userAlreadyExists',data["username"]);
        }
      } catch(err){
        throw err;
      }
    })();
  });

  socket.on('logIn',function(data){
    (async () => {
      try{
        const result = await conn.query("SELECT * FROM users WHERE username = ?",[data["username"]]);
        if(result.length == 0){
          socket.emit('userDNE',data["username"]);
        } else if(result[0]["password"] !== data["password"]){
          socket.emit('incorrectPassword',data);
        } else {
          var responseText = "Welcome back, <i>"+data["username"]+"</i>!";
          socket.emit('logIn',{"response":responseText,"username":data["username"]});
        }
      } catch(err){
        throw err;
      }
    })();
  });


  socket.on('checkMessagesFrom',function(data){
    (async () => {
      try{
        const fromUserIdResult = await conn.query("SELECT userId FROM users WHERE username = ?", [data["username"]]);
        if(fromUserIdResult.length == 0){
          socket.emit('userDNE',data["username"]);
        } else {
          let fromUserId = fromUserIdResult[0]["userId"];
          const messagesResult = await conn.query("SELECT * FROM messages WHERE messages.fromUserId = ?",[fromUserId]);
          if(messagesResult.length == 0){
            socket.emit('noMessages',{"type":"from","username":data["username"]}); // TODO
          } else {
            let messagesOutput = [{}];
            for(let i = 0; i < messagesResult.length; i++){
              let messageId = messagesResult[i]["messageId"];
              let toUsernames = "";
              const toUsernameResult = await conn.query("SELECT * FROM messageRecipients JOIN users WHERE messageRecipients.messageId = ? AND users.userID = messageRecipients.toUserId",[messageId]);
              for(let j = 0; j < toUsernameResult.length; j++){
                toUsernames += toUsernameResult[j]["username"]+", ";
              }
              toUsernames = toUsernames.substring(0,toUsernames.length-2);
              messagesOutput[i] = {"body":messagesResult[i]["body"],"to":toUsernames,"from":data["username"]};
            }
            socket.emit('checkMessagesFrom',messagesOutput);
          }
        }
      } catch(err){
        throw err;
      }
    })()
  });

  socket.on('checkMessagesTo',function(data){
    (async () => {
      try{
        const toUserIdResult = await conn.query("SELECT userId FROM users WHERE username = ?",[data["username"]]);
        if(toUserIdResult.length == 0){
          socket.emit('userDNE',data["username"]);
          return;
        }
        let toUserId = toUserIdResult[0]["userId"];
        const messagesResult = await conn.query("SELECT * FROM messages JOIN messageRecipients WHERE messageRecipients.messageId = messages.messageId AND messageRecipients.toUserId = ?",[toUserId]);
        if(messagesResult.length == 0){
          socket.emit('noMessages',{"type":"to","username":data["username"]});
          return;
        }
        let messagesOutput = [{}];
        for(let i = 0; i < messagesResult.length; i++){
          const fromUsernameResult = await conn.query("SELECT * FROM users WHERE userId = ?",messagesResult[i]["fromUserId"]);
          if(fromUsernameResult.length == 0){
            messagesOutput[i] = {"body":messagesResult[i]["body"],"to":data["username"],"from":"<i>unknown</i>"};
            continue;
          }
          let fromUsername = fromUsernameResult[0]["username"];
          messagesOutput[i] = {"body":messagesResult[i]["body"],"to":data["username"],"from":fromUsername};
        }
        socket.emit('checkMessagesTo',messagesOutput)
      } catch(err){
        throw err;
      }
    })();
  });



  socket.on('createMessage',function(data){
    (async () => {
      try{
        // First, VERIFY ALL USERS IN "TO" and change toArray from usernames to userIDs
        let toArray = data["to"].split(", ");
        for(let i=0; i<toArray.length; i++){
          const toUserIdResult = await conn.query("SELECT userId FROM users WHERE username = ?",[toArray[i]]);
          if(toUserIdResult.length == 0){
            socket.emit('userDNE',toArray[i]);
            return;
          } else {
            toArray[i] = toUserIdResult[0]["userId"];
          }
        }

        // Then, get FROM ID
        const fromUserIdResult = await conn.query("SELECT userId FROM users WHERE username= ? ",[data["from"]]);
        if(fromUserIdResult.length == 0){
          socket.emit('userDNE',data["from"]);
          return;
        }
        let fromId = fromUserIdResult[0]["userId"];

        // Insert into MESSAGES table
        const insertResult = await conn.query("INSERT INTO messages(body, fromUserId) VALUES (?,?)", [data["body"],fromId]);

        // Populate messageRecipients table
        for(let i=0; i<toArray.length; i++){
          await conn.query("INSERT INTO messageRecipients(messageId, toUserId) VALUES(?,?)", [insertResult["insertId"],toArray[i]]);
        }
        socket.emit('createMessage',{"response":"Message sent!"});
      } catch(err){
        throw err;
      }
    })();
  });

});





let port = process.env.PORT;
if (port == null || port == "") {
  port = 8000;
}
server.listen(port);

// server.listen(3000, () => {
//   console.log('listening on *:3000');
// });