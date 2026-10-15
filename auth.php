<?php

session_start();

require_once __DIR__ . '/admin/includes/db.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {


  

    if (isset($_POST['btn_login'])) {

        $signin_errors = [];

        $email = trim($_POST['email'] ?? '');
        $mot_passe = $_POST['mot_passe'] ?? '';


        // Vérification email
        if ($email === '') {

            $signin_errors[] =
                "Veuillez entrer votre email.";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $signin_errors[] =
                "Adresse email invalide.";
        }


        // Vérification mot de passe
        if ($mot_passe === '') {

            $signin_errors[] =
                "Veuillez entrer votre mot de passe.";
        }


        if (empty($signin_errors)) {

            try {

                $stmt = $pdo->prepare("
                    SELECT *
                    FROM utilisateur
                    WHERE email = :email
                    LIMIT 1
                ");

                $stmt->execute([
                    ':email' => $email
                ]);

                $user =
                    $stmt->fetch(PDO::FETCH_ASSOC);


                if (
                    $user &&
                    password_verify(
                        $mot_passe,
                        $user['mot_passe']
                    )
                ) {

                    // Role toujours en minuscules
                    $role =
                        strtolower(
                            trim($user['role'])
                        );


                    /* ==============================
                       SESSIONS COMMUNES
                    ============================== */

                    $_SESSION['user_email'] =
                        $user['email'];

                    $_SESSION['user_nom'] =
                        $user['nom'];

                    $_SESSION['user_prenom'] =
                        $user['prenom'];

                    $_SESSION['user_role'] =
                        $role;


                    /* ==============================
                       COMPATIBILITE RESPONSABLE/AGENT
                    ============================== */

                    $_SESSION['email'] =
                        $user['email'];

                    $_SESSION['role'] =
                        $role;


                    /* ==============================
                       REDIRECTION SELON ROLE
                    ============================== */

                    switch ($role) {


                        // ADMIN
                        case 'admin':

                            $_SESSION['admin_email'] =
                                $user['email'];

                            $_SESSION['admin_nom'] =
                                $user['nom'];

                            header(
                                "Location: admin/dashboard.php"
                            );

                            exit;


                        // CLIENT
                        case 'client':

                            header(
                                "Location: index.php"
                            );

                            exit;


                        // RESPONSABLE
                        case 'responsable':

                            header(
                                "Location: responsables/index.php"
                            );

                            exit;


                        // AGENT DE VENTE
                        case 'agent':

                            header(
                                "Location: agents/index.php"
                            );

                            exit;


                        // COMPTE DESACTIVE
                        case 'inactif':

                            $signin_errors[] =
                                "Ce compte est désactivé.";

                            break;


                        default:

                            $signin_errors[] =
                                "Rôle utilisateur inconnu.";

                            break;
                    }


                } elseif (!$user) {

                    $signin_errors[] =
                        "Email introuvable dans la base de données.";

                } else {

                    $signin_errors[] =
                        "Mot de passe incorrect.";
                }


            } catch (PDOException $e) {

                $signin_errors[] =
                    "Problème de connexion à la base de données.";
            }
        }


        // Retour vers LOGIN en cas d'erreur
        if (!empty($signin_errors)) {

            $_SESSION['signin_errors'] =
                $signin_errors;

            header(
                "Location: login.php?form=login"
            );

            exit;
        }
    }



    /* =====================================================
       SIGN UP CLIENT
    ===================================================== */

    elseif (isset($_POST['btn_signup'])) {

        $signup_errors = [];


        $nom_complet =
            trim($_POST['nom'] ?? '');

        $email =
            trim($_POST['email'] ?? '');

        $mot_passe =
            $_POST['mot_passe'] ?? '';


        /* ==============================
           VALIDATION NOM
        ============================== */

        if ($nom_complet === '') {

            $signup_errors[] =
                "Le nom est obligatoire.";

        } elseif (
            mb_strlen($nom_complet) < 3 ||
            mb_strlen($nom_complet) > 50
        ) {

            $signup_errors[] =
                "Le nom doit contenir entre 3 et 50 caractères.";

        } elseif (
            !preg_match(
                "/^[a-zA-ZÀ-ÿ\s'-]+$/u",
                $nom_complet
            )
        ) {

            $signup_errors[] =
                "Le nom ne doit contenir que des lettres.";
        }



        /* ==============================
           VALIDATION EMAIL
        ============================== */

        if ($email === '') {

            $signup_errors[] =
                "L'email est obligatoire.";

        } elseif (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $signup_errors[] =
                "Le format de l'email est invalide.";

        } elseif (
            strlen($email) > 100
        ) {

            $signup_errors[] =
                "L'email est trop long.";
        }



        /* ==============================
           VALIDATION MOT DE PASSE
        ============================== */

        if ($mot_passe === '') {

            $signup_errors[] =
                "Le mot de passe est obligatoire.";

        } elseif (
            strlen($mot_passe) < 8
        ) {

            $signup_errors[] =
                "Le mot de passe doit contenir au moins 8 caractères.";

        } elseif (
            !preg_match(
                '/[0-9]/',
                $mot_passe
            )
        ) {

            $signup_errors[] =
                "Le mot de passe doit inclure au moins un chiffre.";

        } elseif (
            !preg_match(
                '/[a-zA-Z]/',
                $mot_passe
            )
        ) {

            $signup_errors[] =
                "Le mot de passe doit inclure au moins une lettre.";
        }



        /* ==============================
           SEPARER NOM / PRENOM
        ============================== */

        $parts =
            preg_split(
                '/\s+/',
                $nom_complet,
                2
            );

        $nom =
            $parts[0] ?? '';

        $prenom =
            $parts[1] ?? 'Client';



        /* ==============================
           CREATION DU CLIENT
        ============================== */

        if (empty($signup_errors)) {

            try {

                // Vérifier email existant
                $checkStmt = $pdo->prepare("
                    SELECT email
                    FROM utilisateur
                    WHERE email = :email
                    LIMIT 1
                ");

                $checkStmt->execute([
                    ':email' => $email
                ]);


                if ($checkStmt->fetchColumn()) {

                    $signup_errors[] =
                        "Cet email est déjà utilisé. Veuillez vous connecter.";

                } else {

                    $pdo->beginTransaction();


                    /* UTILISATEUR */

                    $stmt1 = $pdo->prepare("
                        INSERT INTO utilisateur
                        (
                            email,
                            mot_passe,
                            nom,
                            prenom,
                            role
                        )

                        VALUES
                        (
                            :email,
                            :mot_passe,
                            :nom,
                            :prenom,
                            :role
                        )
                    ");


                    $stmt1->execute([

                        ':email' =>
                            $email,

                        ':mot_passe' =>
                            password_hash(
                                $mot_passe,
                                PASSWORD_DEFAULT
                            ),

                        ':nom' =>
                            $nom,

                        ':prenom' =>
                            $prenom,

                        ':role' =>
                            'client'
                    ]);



                    /* CLIENT */

                    $stmt2 = $pdo->prepare("
                        INSERT INTO client
                        (
                            email,
                            date_inscription
                        )

                        VALUES
                        (
                            :email,
                            CURDATE()
                        )
                    ");


                    $stmt2->execute([
                        ':email' => $email
                    ]);


                    $pdo->commit();



                    /* ==============================
                       SESSION CLIENT
                    ============================== */

                    $_SESSION['user_email'] =
                        $email;

                    $_SESSION['user_nom'] =
                        $nom;

                    $_SESSION['user_prenom'] =
                        $prenom;

                    $_SESSION['user_role'] =
                        'client';

                    $_SESSION['email'] =
                        $email;

                    $_SESSION['role'] =
                        'client';


                    header(
                        "Location: index.php"
                    );

                    exit;
                }


            } catch (PDOException $e) {

                if ($pdo->inTransaction()) {

                    $pdo->rollBack();
                }


                $signup_errors[] =
                    "Erreur lors de la création du compte. Réessayez.";
            }
        }


        /* ==============================
           RETOUR SIGN UP SI ERREUR
        ============================== */

        if (!empty($signup_errors)) {

            $_SESSION['signup_errors'] =
                $signup_errors;

            header(
                "Location: login.php?form=signup"
            );

            exit;
        }
    }
}


/* Si quelqu'un ouvre auth.php directement */

header(
    "Location: login.php"
);

exit;
