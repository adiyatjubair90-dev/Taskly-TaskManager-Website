<?php
// register.php – handles registration form submission

require_once 'db.php'; //Connects to database

if ($_SERVER['REQUEST_METHOD'] === 'POST') { //Checks for POST request
    //Retrieves input values using POST
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm'] ?? '');

    // AJAX email check
    if (isset($_SERVER['HTTP_X_CHECK_EMAIL']) && $_SERVER['HTTP_X_CHECK_EMAIL'] === 'true') {
        header('Content-Type: application/json');

        if ($email === '') {
            echo json_encode(['exists' => false]);
            exit;
        }

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            $exists = $stmt->num_rows > 0;
            $stmt->close();
            echo json_encode(['exists' => $exists]);
        } else {
            echo json_encode(['exists' => false]);
        }
        exit;
    }

    // Server-side validations
    if ($email === '' || $username === '' || $password === '' || $confirm === '') { //Checks for anything empty
        header("Location: register.html"); //Keeps you in the same page
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { //Checks for proper email format
        header("Location: register.html");
        exit;
    }

    if ($password !== $confirm) { //Checks if password is confirmed
        header("Location: register.html");
        exit;
    }

    if (strlen($password) < 8) { //Chekcs if passowrd meets length
        header("Location: register.html");
        exit;
    }

    //uses password has to securely store password in database
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    //Double-check for duplicate email
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if ($checkStmt) {
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows > 0) {
            header("Location: register.html");
            exit;
        }
        $checkStmt->close();
    }

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (email, username, password_hash) VALUES (?, ?, ?)");
    if (!$stmt) {
        header("Location: register.html");
        exit;
    }

    $stmt->bind_param("sss", $email, $username, $passwordHash);

    if ($stmt->execute()) { //Validates registration
        header("Location: login.html?registered=1");
        exit;
    } else {
        header("Location: register.html"); // duplicate safety
        exit;
    }

} else { //If not POST request
    header("Location: register.html");
    exit;
}
?>
