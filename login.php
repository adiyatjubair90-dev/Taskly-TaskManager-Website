<?php
// login.php – handles login form submission

session_start();
require_once 'db.php'; //Connects to database

if ($_SERVER['REQUEST_METHOD'] === 'POST') { //Checks for POST request
    //Reads and trims inputs
    $email = trim($_POST['email'] ?? ''); 
    $password = trim($_POST['password'] ?? ''); 

    if ($email === '' || $password === '') { //Checks for empty fields
        echo "<script>alert('Please fill in both email and password.'); window.history.back();</script>";
        exit;
    }

    // Look up user by email
    $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE email = ?"); //Prepares SQL statement to find user
    if (!$stmt) { //if SQL statement fails
        echo "<script>alert('Database error. Please try again later.'); window.history.back();</script>";
        exit;
    }

    $stmt->bind_param("s", $email); //inserts email into placeholder ?
    $stmt->execute(); //Executes SQL Query
    $stmt->store_result(); //Stores result in database memory

    if ($stmt->num_rows === 1) { //if user found
        $stmt->bind_result($id, $username, $passwordHash);
        $stmt->fetch(); //loads user data

        if (password_verify($password, $passwordHash)) {//verifies password
            $_SESSION['user_id']   = $id;
            $_SESSION['username']  = $username;
            $_SESSION['user_email'] = $email;

            header("Location: tasks.php"); //redirects to dashboard
            exit;
        } else {
            echo "<script>alert('Incorrect password.'); window.history.back();</script>";
            exit;
        }
    } else {
        echo "<script>alert('No account found with that email.'); window.history.back();</script>";
        exit;
    }

} else { //redirects to login page if not POST request
    header("Location: login.html"); 
    exit;
}
?>
