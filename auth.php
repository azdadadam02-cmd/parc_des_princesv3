<?php
session_start();

require_once 'Client/includes/db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {


    if (isset($_POST['btn_login'])) {
        $signin_errors = [];
        $email = trim($_POST['email']);
        $mot_passe = $_POST['mot_passe'];
        
        if (empty($email)) { $signin_errors[] = "Veuillez entrer votre email."; }
        if (empty($mot_passe)) { $signin_errors[] = "Veuillez entrer votre mot de passe."; }
        
        if (empty($signin_errors)) { 
            try {
                $stmt = $pdo->prepare("SELECT * FROM UTILISATEUR WHERE email = :email");
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
                if ($user && password_verify($mot_passe, $user['mot_passe'])){
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_nom'] = $user['nom'];
                    $_SESSION['user_prenom'] = $user['prenom'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    switch (strtoupper($user['role'])) {
                        case 'ADMIN': header("Location: admin/dashboard.php"); break;
                        case 'CLIENT': header("Location: index.php"); break;
                        case 'RESPONSABLE': header("Location: responsables/index.php"); break;
                        case 'AGENT': header("Location: agents/index.php"); break;
                        default: header("Location: login.php");
                    } 
                    exit();
                } else { 
                    $signin_errors[] = "Email ou mot de passe incorrect."; 
                }
            } catch (PDOException $e) {
                $signin_errors[] = "Problème de connexion.";
            }
        }
        
        if (!empty($signin_errors)) {
            $_SESSION['signin_errors'] = $signin_errors;
            header("Location: login.php"); 
            exit();
        }
    }
    

    if (isset($_POST['btn_signup'])){
        $signup_errors = [];
        $nom_complet = trim($_POST['nom']);
        $email = trim($_POST['email']);
        $mot_passe = $_POST['mot_passe'];
        
        if (empty($email) || empty($mot_passe) || empty($nom_complet)) {
            $signup_errors[] = "Tous les champs sont obligatoires.";
        } else {
            if (strlen($nom_complet) < 3 || strlen($nom_complet) > 50) {
                $signup_errors[] = "Le nom doit contenir entre 3 et 50 caractères.";
            } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s'-]+$/", $nom_complet)) {
                $signup_errors[] = "Le nom ne doit contenir que des lettres.";
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $signup_errors[] = "Le format de l'email est invalide.";
            }
            if (strlen($mot_passe) < 8) {
                $signup_errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
            } elseif (!preg_match("#[0-9]+#", $mot_passe)) {
                $signup_errors[] = "Le mot de passe doit inclure au moins un chiffre.";
            } elseif (!preg_match("#[a-zA-Z]+#", $mot_passe)) {
                $signup_errors[] = "Le mot de passe doit inclure au moins une lettre.";
            }
        }
        
        if (empty($signup_errors)) { 
            $parts = explode(" ", $nom_complet, 2);
            $nom = $parts[0];
            $prenom = isset($parts[1]) ? $parts[1] : 'Client';
            
            try {
                $checkStmt = $pdo->prepare("SELECT email FROM UTILISATEUR WHERE email = :email");
                $checkStmt->execute([':email' => $email]);
                
                if ($checkStmt->rowCount() > 0) {
                    $signup_errors[] = "Cet email est déjà utilisé. Veuillez vous connecter.";
                } else {
                    $hashed_password = password_hash($mot_passe, PASSWORD_DEFAULT);
                    $role = 'CLIENT';
                    $date_inscription = date('Y-m-d'); 
                    
                    $pdo->beginTransaction();
                    $stmt1 = $pdo->prepare("INSERT INTO UTILISATEUR (email, mot_passe, nom, prenom, role) VALUES (:email, :mot_passe, :nom, :prenom, :role)");
                    $stmt1->execute([':email' => $email, ':mot_passe' => $hashed_password, ':nom' => $nom, ':prenom' => $prenom, ':role' => $role]);
                  
                    $stmt2 = $pdo->prepare("INSERT INTO CLIENT (email, date_inscription) VALUES (:email, :date_inscription)");
                    $stmt2->execute([':email' => $email, ':date_inscription' => $date_inscription]);
                    $pdo->commit();

                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_nom'] = $nom;
                    $_SESSION['user_role'] = $role;
                    header("Location: index.php");
                    exit();
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $signup_errors[] = "Erreur lors de la création du compte. Réessayez.";
            }
        }
        
        if (!empty($signup_errors)) {
            $_SESSION['signup_errors'] = $signup_errors;
            header("Location: login.php"); 
            exit();
        }
    }    
}
?>
