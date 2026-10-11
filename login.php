<?php
session_start();
$errors = $_SESSION['login_errors'] ?? [];
unset($_SESSION['login_errors']);
$form = ($_GET['form'] ?? 'login') === 'signup' ? 'signup' : 'login';
$containerClass = $form === 'signup' ? 'right-panel-active' : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parc des Princes - Connexion</title>
    <link rel="stylesheet" href="Client/assets/css/login.css?v=1">
</head>
<body>
    <div class="cadrLogin <?= htmlspecialchars($containerClass) ?>" id="mainContainer">
        <div class="formLogin">
            <img src="admin/img/logo.png" alt="Logo" class="logoLoginR">
            <form action="auth.php" method="POST" class="form1">
                <h1>Créer Compte</h1>

                <?php if ($form === 'signup' && $errors): ?>
                    <div class="error-box">
                        <?php foreach ($errors as $error): ?>
                            <p class="error-text"><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <input type="text" name="nom" placeholder="Nom complet" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="mot_passe" placeholder="Mot de passe" required>
                <button type="submit" name="btn_signup">S'inscrire</button>
            </form>
        </div>

        <div class="formSingin">
            <img src="admin/img/logo.png" alt="Logo" class="logoLoginL">
            <form action="auth.php" method="POST" class="form2">
                <h1>Se Connecter</h1>

                <?php if ($form === 'login' && $errors): ?>
                    <div class="error-box">
                        <?php foreach ($errors as $error): ?>
                            <p class="error-text"><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="mot_passe" placeholder="Mot de passe" required>
                <button type="submit" name="btn_login">Se connecter</button>
            </form>
        </div>

        <div class="overlay-container">
            <div class="overlay">
                <div class="overlayRight">
                    <h1>Bon retour !</h1>
                    <p>Ravi de te revoir, héros ! Connecte-toi et profite.</p>
                    <button class="ghost" id="signUpBtn">S'inscrire</button>
                </div>
                <div class="overlayLeft">
                    <h1>Rejoins-nous !</h1>
                    <p>Tu n'as pas de compte ? Crée-en un facilement.</p>
                    <button class="ghost" id="signInBtn">Se connecter</button>
                </div>
            </div>
        </div>
    </div>
    <script src="Client/assets/js/login.js"></script>
</body>
</html>
