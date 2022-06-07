const express = require('express');
const app = express();
const http = require('http');
const server = http.createServer(app);
const { Server } = require('socket.io');
const io = new Server(server);
const mysql = require('mysql');
const conn = mysql.createConnection({
  host: "us-cdbr-east-05.cleardb.net",
  user: "bc92d718ad46e1",
  password: "b612d3a5",
  database: "heroku_c255c3ac503266d"
});


sql = "SELECT * FROM users";
conn.connect(function(err) {
  if (err) throw err;
  console.log("Connected!");
  conn.query(sql, function (err, result) {
    if (err) throw err;
    console.log("Result: " + result[0]["username"]);
  });
});

app.get('/', (req, res) => {
  res.sendFile(__dirname + '/page.html');
});
app.use(express.static('.'));

io.on('connection', (socket) => {
  console.log('a user connected');
});

let port = process.env.PORT;
if (port == null || port == "") {
  port = 8000;
}
server.listen(3000);
