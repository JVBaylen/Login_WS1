
<?php

require_once __DIR__ . '/db.php';


function showAlertAndRedirect($message, $redirectPage)
{
    echo "<script>
        alert(" . json_encode($message) . ");
        window.location.href = " . json_encode($redirectPage) . ";
    </script>";
    exit();
}


function showAlertAndGoBack($message)
{
    echo "<script>
        alert(" . json_encode($message) . ");
        window.history.back();
    </script>";
    exit();
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: form.html');
    exit();
}


$fname = trim((string) ($_POST['fname'] ?? ''));
$lname = trim((string) ($_POST['lname'] ?? ''));
$username = trim((string) ($_POST['username'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$dob = trim((string) ($_POST['dob'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$confirm_password = (string) ($_POST['confirm_password'] ?? '');




if (
    $fname === '' ||
    $lname === '' ||
    $username === '' ||
    $phone === '' ||
    $dob === '' ||
    $email === '' ||
    $password === ''
) {
    showAlertAndGoBack('Please complete all required fields.');
}


if (strlen($password) < 8) {
    showAlertAndGoBack('Password must be at least 8 characters.');
}


if (!preg_match('/^[0-9]{11}$/', $phone)) {
    showAlertAndGoBack(
        'Phone number must be 11 digits. Example: 09XXXXXXXXX.'
    );
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    showAlertAndGoBack('Invalid email format.');
}

if (!DateTime::createFromFormat('Y-m-d', $dob)) {
    showAlertAndGoBack('Invalid date of birth.');
}


$check = $conn->prepare(
    'SELECT ID FROM member WHERE username = ? OR email = ? LIMIT 1'
);


if (!$check) {
    showAlertAndGoBack(
        'Database error while checking account: ' . $conn->error
    );
}

if ($password !== $confirm_password) {
    showAlertAndGoBack('Passwords do not match.');
}


$check->bind_param('ss', $username, $email);
$check->execute();
$check->store_result();


if ($check->num_rows > 0) {
    $check->close();
    $conn->close();

    showAlertAndGoBack(
        'Username or email already exists.'
    );
}


$check->close();


$role = 'member';


$storedPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);


$stmt = $conn->prepare("
    INSERT INTO member
        (fname, lname, username, phone, dob, email, password, role)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");


if (!$stmt) {
    showAlertAndGoBack(
        'Database error while creating account: ' . $conn->error
    );
}


$stmt->bind_param(
    'ssssssss',
    $fname,
    $lname,
    $username,
    $phone,
    $dob,
    $email,
    $storedPassword,
    $role
);

if ($stmt->execute()) {

    $stmt->close();
    $conn->close();

    showAlertAndRedirect(
        'Account created successfully!',
        'login.html'
    );
}


$error = $stmt->error;

$stmt->close();
$conn->close();

showAlertAndGoBack(
    'Error: ' . $error
);

?>
```
