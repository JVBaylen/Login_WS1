<?php

require_once __DIR__ . '/db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.html");
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

$stmt = $conn->prepare("SELECT * FROM member WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password']) || $password === $user['password']) {
        $_SESSION['user'] = $user;

        if ($user['role'] === 'elder') {
            header("Location: admindash.html");
        } elseif ($user['role'] === 'staff') {
            header("Location: staffdash.html");
        } else {
            header("Location: userdash.html");
        }

        exit();
    }
}

header("Location: login.html?error=invalid");
exit();

?>
