<?php
require_once __DIR__ . '/../../controllers/UserController.php';
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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? '');
    $password = $_POST["password"] ?? '';

    try {
        $userController = new UserController();
        $user = $userController->getByEmail($email);
        if ($user && $userController->verifyPassword($password, $user->getPassword())) {
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['user_role'] = $user->getRole();

            if ($user->getRole() === 'admin') {
                header("Location: ../back-office/index.php");
            } else {
                if ($redirect !== '') {
                    header("Location: " . $redirect);
                } else {
                    header("Location: index.php");
                }
            }
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    } catch (Exception $e) {
        $error = "An error occurred during login.";
    }
}
?>
<html>

<head>
    <title>Login | SkillBridge</title>
    <style>
        :root {
            --primary: #4e73df;
            --primary-dark: #224abe;
            --accent: #e87532;
            --text: #5a5c69;
            --heading: #0f2943;
            --muted: #858796;
            --bg: #f8f9fc;
            --border: #e3e6f0;
            --danger: #e74a3b;
            --white: #ffffff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Nunito", "Segoe UI", Arial, sans-serif;
            color: var(--text);
            background:
                linear-gradient(135deg, rgba(78, 115, 223, 0.08), rgba(232, 117, 50, 0.10)),
                var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-card {
            width: 100%;
            max-width: 430px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 34px 30px;
            box-shadow: 0 12px 35px rgba(90, 92, 105, 0.12);
        }

        .brand {
            text-align: center;
            margin-bottom: 8px;
            font-size: 1.7rem;
            font-weight: 800;
            color: var(--heading);
        }

        .brand span {
            color: var(--accent);
        }

        h2 {
            margin: 0 0 24px;
            text-align: center;
            color: var(--heading);
            font-size: 1.8rem;
        }

        .error-box {
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 10px;
            background: rgba(231, 74, 59, 0.1);
            border: 1px solid rgba(231, 74, 59, 0.2);
            color: var(--danger);
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            color: var(--heading);
        }

        input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 1rem;
            color: var(--text);
            background: var(--white);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.15);
        }

        .submit-btn {
            width: 100%;
            padding: 13px 16px;
            border: none;
            border-radius: 10px;
            background: var(--primary);
            color: var(--white);
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .submit-btn:hover {
            background: var(--primary-dark);
        }

        .helper {
            margin: 18px 0 0;
            text-align: center;
            font-size: 0.95rem;
            color: var(--muted);
        }

        .helper a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 700;
        }

        .helper a:hover {
            text-decoration: underline;
        }

        @media (max-width: 520px) {
            body {
                padding: 16px;
            }

            .login-card {
                padding: 26px 20px;
            }
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="brand">Skill<span>Bridge</span></div>
        <h2>Connexion</h2>

        <?php if ($error !== ''): ?>
            <div class="error-box"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="text" name="email" placeholder="Enter Email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input id="password" type="password" name="password" placeholder="Enter Password">
            </div>

            <button type="submit" class="submit-btn">Se connecter</button>
        </form>

        <p class="helper">Retour au site public : <a href="<?= front_url('index.php') ?>">ouvrir l'accueil</a></p>
    </div>
</body>

</html>
