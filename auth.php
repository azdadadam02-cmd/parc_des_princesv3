<?php
session_start();
require_once __DIR__ . '/admin/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* =====================================================
       LOGIN (SE CONNECTER)
    ===================================================== */
    if (isset($_POST['btn_login'])) {

        $signin_errors = [];
        $email = trim($_POST['email'] ?? '');
        $mot_passe = $_POST['mot_passe'] ?? '';

        $_SESSION['signin_old'] = ['email' => $email];

        // Vérification email
        if ($email === '') {
            $signin_errors['email'] = "Veuillez entrer votre email.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $signin_errors['email'] = "Adresse email invalide.";
        }

        // Vérification mot de passe
        if ($mot_passe === '') {
            $signin_errors['mot_passe'] = "Veuillez entrer votre mot de passe.";
        }

        if (empty($signin_errors)) {
            try {
                $stmt = $pdo->prepare("
                    SELECT *
                    FROM utilisateur
                    WHERE email = :email
                    LIMIT 1
                ");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($mot_passe, $user['mot_passe'])) {
                    $role = strtolower(trim($user['role']));

                    if ($role === 'inactif') {
                        $signin_errors['email'] = "Ce compte est désactivé.";
                    } elseif (!in_array($role, ['admin', 'client', 'responsable', 'agent'], true)) {
                        $signin_errors['email'] = "Rôle utilisateur inconnu.";
                    } else {
                        unset($_SESSION['signin_old']);

                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_nom'] = $user['nom'];
                        $_SESSION['user_prenom'] = $user['prenom'];
                        $_SESSION['user_role'] = $role;
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $role;

                        switch ($role) {
                            case 'admin':
                                $_SESSION['admin_email'] = $user['email'];
                                $_SESSION['admin_nom'] = $user['nom'];
                                header("Location: admin/dashboard.php");
                                exit;
                            case 'client':
                                header("Location: index.php");
                                exit;
                            case 'responsable':
                                header("Location: responsables/index.php");
                                exit;
                            case 'agent':
                                header("Location: agents/index.php");
                                exit;
                        }
                    }
                } elseif (!$user) {
                    $signin_errors['email'] = "Email introuvable dans la base de données.";
                } else {
                    $signin_errors['mot_passe'] = "Mot de passe incorrect.";
                }

            } catch (PDOException $e) {
                $signin_errors['mot_passe'] = "Problème de connexion à la base de données.";
            }
        }

        if (!empty($signin_errors)) {
            $_SESSION['signin_errors'] = $signin_errors;
            header("Location: login.php?form=login");
            exit;
        }
    }

    /* =====================================================
       SIGN UP CLIENT (CRÉER COMPTE)
    ===================================================== */
    elseif (isset($_POST['btn_signup'])) {

        $signup_errors = [];
        $nom_complet = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mot_passe = $_POST['mot_passe'] ?? '';

        $_SESSION['signup_old'] = [
            'nom' => $nom_complet,
            'email' => $email
        ];

        // Validation Nom
        if ($nom_complet === '') {
            $signup_errors['nom'] = "Le nom est obligatoire.";
        } elseif (mb_strlen($nom_complet) < 3 || mb_strlen($nom_complet) > 50) {
            $signup_errors['nom'] = "Le nom doit contenir entre 3 et 50 caractères.";
        } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s'-]+$/u", $nom_complet)) {
            $signup_errors['nom'] = "Le nom ne doit contenir que des lettres.";
        }

        // Validation Email
        if ($email === '') {
            $signup_errors['email'] = "L'email est obligatoire.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $signup_errors['email'] = "Le format de l'email est invalide.";
        } elseif (strlen($email) > 100) {
            $signup_errors['email'] = "L'email est trop long.";
        }

        // Validation Mot de passe
        if ($mot_passe === '') {
            $signup_errors['mot_passe'] = "Le mot de passe est obligatoire.";
        } elseif (strlen($mot_passe) < 8) {
            $signup_errors['mot_passe'] = "Le mot de passe doit contenir au moins 8 caractères.";
        } elseif (!preg_match('/[0-9]/', $mot_passe)) {
            $signup_errors['mot_passe'] = "Le mot de passe doit inclure au moins un chiffre.";
        } elseif (!preg_match('/[a-zA-Z]/', $mot_passe)) {
            $signup_errors['mot_passe'] = "Le mot de passe doit inclure au moins une lettre.";
        }

        $parts = preg_split('/\s+/', $nom_complet, 2);
        $nom = $parts[0] ?? '';
        $prenom = $parts[1] ?? 'Client';

        if (empty($signup_errors)) {
            try {
                $checkStmt = $pdo->prepare("
                    SELECT email
                    FROM utilisateur
                    WHERE email = :email
                    LIMIT 1
                ");
                $checkStmt->execute([':email' => $email]);

                if ($checkStmt->fetchColumn()) {
                    $signup_errors['email'] = "Cet email est déjà utilisé. Veuillez vous connecter.";
                } else {
                    $pdo->beginTransaction();

                    $stmt1 = $pdo->prepare("
                        INSERT INTO utilisateur (email, mot_passe, nom, prenom, role)
                        VALUES (:email, :mot_passe, :nom, :prenom, :role)
                    ");
                    $stmt1->execute([
                        ':email' => $email,
                        ':mot_passe' => password_hash($mot_passe, PASSWORD_DEFAULT),
                        ':nom' => $nom,
                        ':prenom' => $prenom,
                        ':role' => 'client'
                    ]);

                    $stmt2 = $pdo->prepare("
                        INSERT INTO client (email, date_inscription)
                        VALUES (:email, CURDATE())
                    ");
                    $stmt2->execute([':email' => $email]);

                    $pdo->commit();
                    unset($_SESSION['signup_old']);

                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_nom'] = $nom;
                    $_SESSION['user_prenom'] = $prenom;
                    $_SESSION['user_role'] = 'client';
                    $_SESSION['email'] = $email;
                    $_SESSION['role'] = 'client';

                    header("Location: index.php");
                    exit;
                }

            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $signup_errors['mot_passe'] = "Erreur lors de la création du compte. Réessayez.";
            }
        }

        if (!empty($signup_errors)) {
            $_SESSION['signup_errors'] = $signup_errors;
            header("Location: login.php?form=signup");
            exit;
        }
    }
}

header("Location: login.php");
exit;