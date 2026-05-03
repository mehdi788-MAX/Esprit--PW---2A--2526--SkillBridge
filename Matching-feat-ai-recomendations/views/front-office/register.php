<?php
require_once __DIR__ . '/../../controllers/UserController.php';
require_once __DIR__ . '/../../models/UserModel.php';
ensure_session_started();

$redirect = trim((string) ($_GET["redirect"] ?? $_POST["redirect"] ?? ''));

if (isset($_SESSION["user_id"])) {
    if (is_admin()) {
        redirect_to_user_home();
    } elseif ($redirect !== '') {
        header("Location: " . $redirect);
    } else {
        redirect_to_user_home();
    }
    exit;
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $firstName = trim($_POST["first_name"] ?? '');
    $lastName = trim($_POST["last_name"] ?? '');
    $email = trim($_POST["email"] ?? '');
    $phone = trim($_POST["phone"] ?? '');
    $password = $_POST["password"] ?? '';
    $confirmPassword = $_POST["confirm_password"] ?? '';

    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        try {
            $userController = new UserController();
            $existingUser = $userController->getByEmail($email);

            if ($existingUser) {
                $error = "An account with this email already exists.";
            } else {
                $user = new User(null, $firstName, $lastName, $email, $password, 'client', $phone, null, date('Y-m-d H:i:s'));
                $userId = $userController->save($user);

                if ($userId) {
                    $success = "Registration successful! You can now log in.";
                } else {
                    $error = "Failed to create account. Please try again.";
                }
            }
        } catch (Exception $e) {
            $error = "An error occurred during registration. " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>S'inscrire</title>
</head>

<body>
    <h2>Créer un compte</h2>

    <form method="POST">
        <label>Prénom : *</label><br>
        <input type="text" name="first_name" placeholder="Votre prénom" required><br><br>

        <label>Nom : *</label><br>
        <input type="text" name="last_name" placeholder="Votre nom" required><br><br>

        <label>Email : *</label><br>
        <input type="email" name="email" placeholder="Votre email" required><br><br>

        <label>Téléphone :</label><br>
        <input type="text" name="phone" placeholder="Votre téléphone"><br><br>

        <label>Mot de passe : *</label><br>
        <input type="password" name="password" placeholder="Mot de passe" required><br><br>

        <label>Confirmer le mot de passe : *</label><br>
        <input type="password" name="confirm_password" placeholder="Confirmer le mot de passe" required><br><br>

        <button type="submit">S'inscrire</button>
    </form>

    <?php if ($error !== ''): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <p style="color:green;"><?= htmlspecialchars($success) ?> <a href="login.php">Se connecter</a></p>
    <?php endif; ?>
</body>

</html>
