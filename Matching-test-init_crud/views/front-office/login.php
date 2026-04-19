<?php

session_start();
if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user_id = trim($_POST["user_id"] ?? '');

    // Simple user ID based routing
    if ($user_id === '1') {
        $_SESSION['user_id'] = 1;
        // Redirect to back office
        header("Location: /pleaseeee/Matching-test-init_crud/views/back-office/index.php");
        exit;
    } elseif ($user_id === '2' || $user_id === '3') {
        $_SESSION['user_id'] = $user_id;
        // Redirect to front office
        header("Location: /pleaseeee/Matching-test-init_crud/views/front-office/index.html");
        exit;
    } else {
        $error = "Invalid User ID. Please enter 1, 2, or 3";
    }
}
?>

<!-- LOGIN FORM -->
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>

<h2>Connexion</h2>

<form method="POST">
    <label>User ID (1 for Back Office, 2 or 3 for Front Office):</label><br>
    <input type="text" name="user_id" placeholder="Enter User ID" required><br><br>

    <button type="submit">Se connecter</button>
</form>

<p style="color:red;">
    <?= $error ?>
</p>
</body>
</html>
