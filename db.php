<?php
$host = "localhost";   // Assigns local host
$user = "root";        // Assigns WAMP user
$pass = "";            // Assigns WAMP password
$db = "taskly";      // Assigns database name

 $conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { // Checks connection
    die("Connection failed: " . $conn->connect_error);
}
?>
