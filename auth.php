<?php
session_start();
require_once __DIR__ . '/admin/includes/db.php';

$errors = [];
$action_form = 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ================== LOGIN ================== */
    if (isset($_POST['btn_login'])) {
        $action_form = 'login';

        $email = trim($_POST['email'] ?? '');
        $mot_passe = $_POST['mot_passe'] ?? '';

        if ($email === '') {
            $errors[] = "Veuillez entrer votre email.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Adresse email invalide.";
        }

        if ($mot_passe === '') {
            $errors[] = "Veuillez entrer votre mot de passe.";
        }

        if (!$errors) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE email = :email LIMIT 1");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($mot_passe, $user['mot_passe'])) {
                    $role = strtolower(trim((string)$user['role']));

                    // Sessions communes à tous les espaces.
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_nom'] = $user['nom'];
                    $_SESSION['user_prenom'] = $user['prenom'];
                    $_SESSION['user_role'] = $role;

                    // Compatibilité avec les espaces Responsable / Agent existants.
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $role;

                    // Évite de garder une ancienne session admin si un autre rôle se connecte.
                    unset($_SESSION['admin_email'], $_SESSION['admin_nom']);

                    switch ($role) {
                        case 'admin':
                            $_SESSION['admin_email'] = $user['email'];
                            $_SESSION['admin_nom'] = $user['nom'];
                            header('Location: admin/dashboard.php');
                            exit;

                        case 'client':
                            header('Location: index.php');
                            exit;

                        case 'responsable':
                            header('Location: responsables/index.php');
                            exit;

                        case 'agent':
                            header('Location: agents/index.php');
                            exit;

                        case 'inactif':
                            $errors[] = "Ce compte est désactivé.";
                            break;

                        default:
                            $errors[] = "Rôle utilisateur inconnu.";
                    }
                    } elseif (!$user) {

                        $errors[] = "Email introuvable dans la base de données.";

                    } else {

                        $errors[] = "Mot de passe incorrect.";
                    }
            } catch (PDOException $e) {
                $errors[] = "Problème de connexion à la base de données.";
            }
        }
    }

    /* ================== SIGN UP CLIENT ================== */
    elseif (isset($_POST['btn_signup'])) {
        $action_form = 'signup';

        $nom_complet = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mot_passe = $_POST['mot_passe'] ?? '';

        if ($nom_complet === '') {
            $errors[] = "Le nom est obligatoire.";
        } elseif (mb_strlen($nom_complet) < 3 || mb_strlen($nom_complet) > 50) {
            $errors[] = "Le nom doit contenir entre 3 et 50 caractères.";
        } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s'-]+$/u", $nom_complet)) {
            $errors[] = "Le nom ne doit contenir que des lettres.";
        }

        if ($email === '') {
            $errors[] = "L'email est obligatoire.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Le format de l'email est invalide.";
        } elseif (strlen($email) > 100) {
            $errors[] = "L'email est trop long (maximum 100 caractères).";
        }

        if ($mot_passe === '') {
            $errors[] = "Le mot de passe est obligatoire.";
        } elseif (strlen($mot_passe) < 8) {
            $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
        } elseif (!preg_match('/[0-9]/', $mot_passe)) {
            $errors[] = "Le mot de passe doit inclure au moins un chiffre.";
        } elseif (!preg_match('/[a-zA-Z]/', $mot_passe)) {
            $errors[] = "Le mot de passe doit inclure au moins une lettre.";
        }

        $parts = preg_split('/\s+/', $nom_complet, 2);
        $nom = $parts[0] ?? '';
        $prenom = $parts[1] ?? 'Client';

        if (!$errors) {
            try {
                $checkStmt = $pdo->prepare("SELECT email FROM utilisateur WHERE email = :email");
                $checkStmt->execute([':email' => $email]);

                if ($checkStmt->fetchColumn()) {
                    $errors[] = "Cet email est déjà utilisé. Veuillez vous connecter.";
                } else {
                    $pdo->beginTransaction();

                    $stmt1 = $pdo->prepare(
                        "INSERT INTO utilisateur (email, mot_passe, nom, prenom, role)
                         VALUES (:email, :mot_passe, :nom, :prenom, 'client')"
                    );
                    $stmt1->execute([
                        ':email' => $email,
                        ':mot_passe' => password_hash($mot_passe, PASSWORD_DEFAULT),
                        ':nom' => $nom,
                        ':prenom' => $prenom,
                    ]);

                    $stmt2 = $pdo->prepare(
                        "INSERT INTO client (email, date_inscription)
                         VALUES (:email, CURDATE())"
                    );
                    $stmt2->execute([':email' => $email]);

                    $pdo->commit();

                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_nom'] = $nom;
                    $_SESSION['user_prenom'] = $prenom;
                    $_SESSION['user_role'] = 'client';
                    $_SESSION['email'] = $email;
                    $_SESSION['role'] = 'client';

                    header('Location: index.php');
                    exit;
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = "Erreur lors de la création du compte. Réessayez.";
            }
        }
    }

    if ($errors) {
        $_SESSION['login_errors'] = $errors;
        header('Location: login.php?form=' . urlencode($action_form));
        exit;
    }
}

header('Location: login.php');
exit;