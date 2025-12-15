<?php
// logout.php – clears session and sends user to login page

session_start();
session_unset();
session_destroy();

header("Location: login.html");
exit;
?>
