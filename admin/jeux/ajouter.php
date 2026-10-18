<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../offres/_commun.php';
require_once __DIR__ . '/_image.php';


$message = "";
$error = "";




$stmt = $pdo->query("
    SELECT *
    FROM categorie
    ORDER BY nom_categorie
");

$categories =
    $stmt->fetchAll(PDO::FETCH_ASSOC);




$sqlResponsables = "

    SELECT
        r.email,
        u.nom,
        u.prenom

    FROM responsable_de_jeu r

    INNER JOIN utilisateur u
        ON r.email = u.email

    WHERE LOWER(COALESCE(r.statut_responsable, 'actif')) = 'actif'

    ORDER BY u.nom
";

$stmtResponsables =
    $pdo->query($sqlResponsables);

$responsables =
    $stmtResponsables->fetchAll(PDO::FETCH_ASSOC);




if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tarif_promo = is_string($_POST['tarif_promo'] ?? null) ? str_replace(',', '.', trim($_POST['tarif_promo'])) : '';
    $tarif_normal = is_string($_POST['tarif'] ?? null) ? str_replace(',', '.', trim($_POST['tarif'])) : '';
    $erreur_tarif = '';
    if (!offre_csrf_valide()) $erreur_tarif = 'Formulaire expiré. Rechargez la page.';
    elseif (!preg_match('/^\d{1,8}(\.\d{1,2})?$/D', $tarif_normal)) $erreur_tarif = 'Tarif normal invalide.';
    elseif ($tarif_promo !== '' && (!preg_match('/^\d{1,8}(\.\d{1,2})?$/D', $tarif_promo) || (float)$tarif_promo >= (float)$tarif_normal)) $erreur_tarif = 'Le prix promotionnel doit être positif ou nul et inférieur au tarif normal.';


    $nom =
        trim($_POST['nom']);

    $description =
        trim($_POST['description']);

    $age_min =
        $_POST['age_min'];

    $capacite =
        $_POST['capacite'];

    $duree_session =
        $_POST['duree_session'];

    $tarif =
        $tarif_normal;

    $etat =
        $_POST['etat'];

    $date_mise_service =
        $_POST['date_mise_service'];

    $id_categorie = filter_input(
        INPUT_POST,
        'id_categorie',
        FILTER_VALIDATE_INT
    );

    $email_responsable =
        !empty($_POST['email_responsable'])
        ? $_POST['email_responsable']
        : null;


    if ($erreur_tarif !== '') {
        $error = $erreur_tarif;
    } elseif (
    empty($nom) ||
    $capacite === '' ||
    $tarif === '' ||
    !$id_categorie
    ) {

        $error = "Veuillez remplir les champs obligatoires et choisir une catégorie.";

    } elseif (offre_longueur($nom) < 2) {

        $error = "Le nom du jeu doit contenir au moins 2 caractères.";

    } elseif (
        $age_min !== '' &&
        (!is_numeric($age_min) || $age_min < 0)
    ) {

        $error = "L'âge minimum doit être supérieur ou égal à 0.";

    } elseif (
        !is_numeric($capacite) ||
        $capacite <= 0
    ) {

        $error = "La capacité doit être supérieure à 0.";

    } elseif (
        $duree_session !== '' &&
        (!is_numeric($duree_session) || $duree_session <= 0)
    ) {

        $error = "La durée de session doit être supérieure à 0.";

    } elseif (
        !is_numeric($tarif) ||
        $tarif < 0
    ) {

        $error = "Le tarif doit être supérieur ou égal à 0.";

    }  
      else {

        $nouvelle_image = null;

        try {
            [$img_jeu, $nouvelle_image] = jeu_recevoir_image(null, true);
        } catch (Throwable $e) {
            if ($e instanceof DomainException) {
                $error = $e->getMessage();
            } else {
                error_log($e->getMessage());
                $error = "Impossible d'enregistrer l'image. Vérifiez le dossier uploads/jeux.";
            }
        }

if (empty($error)) {
        $sql = "

            INSERT INTO jeu
            (
                nom,
                description,
                age_min,
                capacite,
                duree_session,
                tarif,
                tarif_promo,
                etat,
                date_mise_service,
                id_categorie,
                email_responsable,
                email_admin,
                img_jeu
            )

            VALUES
            (
                :nom,
                :description,
                :age_min,
                :capacite,
                :duree_session,
                :tarif,
                :tarif_promo,
                :etat,
                :date_mise_service,
                :id_categorie,
                :email_responsable,
                :email_admin,
                :img_jeu
            )

        ";


        try {
        $stmt =
            $pdo->prepare($sql);


        $stmt->execute([

            ':nom' =>
                $nom,

            ':description' =>
                $description,

            ':age_min' =>
                $age_min === '' ? null : $age_min,

            ':capacite' =>
                $capacite,

            ':duree_session' =>
                $duree_session === '' ? null : $duree_session,

            ':tarif' =>
                $tarif,
            ':tarif_promo' => $tarif_promo === '' ? null : $tarif_promo,

            ':etat' =>
                $etat,

            ':date_mise_service' =>
                $date_mise_service === '' ? null : $date_mise_service,

            ':id_categorie' =>
                $id_categorie,

            ':email_responsable' =>
                $email_responsable,

            ':email_admin' =>
                $_SESSION['admin_email'],

            ':img_jeu' => $img_jeu

        ]);


        header(
            "Location: index.php"
        );

        exit;
        } catch (PDOException $e) {
            if ($nouvelle_image && is_file($nouvelle_image)) {
                unlink($nouvelle_image);
            }
            error_log($e->getMessage());
            $error = "Enregistrement impossible. Réessayez.";
        }
        }
    }
}

?>

<!DOCTYPE html>

<html lang="fr">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta charset="UTF-8">

    <title>Ajouter un jeu</title>

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >

</head>


<body>

<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>


<main>


    <div class="page-header">

        <div>

            <h1>Ajouter un jeu</h1>

            <p>
                Ajouter un nouveau jeu au parc
            </p>

        </div>


        <a
            href="index.php"
            class="btn-secondary"
        >
            Retour
        </a>

    </div>


    <div class="form-box">


        <?php if ($error): ?>

            <div class="alert-error">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <form method="POST" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= offre_h(offre_token()) ?>">


            <div class="form-grid">


                <div class="form-group">

                    <label>
                        Nom du jeu *
                    </label>

                    <input
    type="text"
    name="nom"
    minlength="2"
    maxlength="100"
    required
>

                </div>


                <div class="form-group">

                    <label>
                        Catégorie
                    </label>

                    <select name="id_categorie" required>

                        <option value="">
                            Choisir
                        </option>


                        <?php foreach ($categories as $categorie): ?>

                            <option
                                value="<?= $categorie['id_categorie'] ?>"
                            >

                                <?= htmlspecialchars(
                                    $categorie['nom_categorie']
                                ) ?>

                            </option>

                        <?php endforeach; ?>


                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Âge minimum
                    </label>

                    <input
                        type="number"
                        name="age_min"
                        min="0"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Capacité *
                    </label>

                    <input
                        type="number"
                        name="capacite"
                        min="1"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Durée session
                    </label>

                    <input
                        type="number"
                        name="duree_session"
                        min="1"
                        placeholder="Minutes"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Tarif *
                    </label>

                    <input
                        type="number"
                        name="tarif"
                        step="0.01"
                        min="0"
                        required
                    >

                </div>


                <div class="form-group">
                    <label for="tarif_promo">Prix promotionnel (DH)</label>
                    <input id="tarif_promo" type="number" name="tarif_promo" min="0" max="99999999.99" step="0.01" value="<?= offre_h(($_POST['tarif_promo'] ?? '')) ?>">
                    <small>Laissez vide pour retirer la promotion. Le tarif normal est conservé.</small>
                </div>

                <div class="form-group">

                    <label>
                        État
                    </label>

                    <select name="etat">

                        <option value="disponible">
                            Disponible
                        </option>

                        <option value="en fonctionnement">
                            En fonctionnement
                        </option>

                        <option value="en maintenance">
                            En maintenance
                        </option>

                        <option value="hors service">
                            Hors service
                        </option>

                        <option value="fermé temporairement">
                            Fermé temporairement
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Date mise en service
                    </label>

                    <input
                        type="date"
                        name="date_mise_service"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Responsable
                    </label>

                    <select name="email_responsable">

                        <option value="">
                            Aucun responsable
                        </option>


                        <?php foreach ($responsables as $responsable): ?>

                            <option
                                value="<?= htmlspecialchars(
                                    $responsable['email']
                                ) ?>"
                            >

                                <?= htmlspecialchars(
                                    $responsable['prenom']
                                    . ' '
                                    . $responsable['nom']
                                ) ?>

                            </option>

                        <?php endforeach; ?>


                    </select>

                </div>


                <div class="form-group full-width">

                    <label>
                        Description
                    </label>

                    <textarea
                        name="description"
                        rows="5"
                    ></textarea>

                </div>



                <div class="form-group full-width">
                    <label>Image du jeu</label>
                    <p>Choisissez un fichier sur votre PC ou renseignez un lien direct vers une image.</p>
                    
                </div>

                <div class="form-group">
                    <label for="img_jeu">Fichier depuis votre PC</label>
                    <input
                        id="img_jeu"
                        type="file"
                        name="img_jeu"
                        accept="image/jpeg,image/png,image/webp"
                    >
                    <small>JPG, PNG ou WEBP, 8 Mo maximum.</small>
                </div>

                <div class="form-group">
                    <label for="img_jeu_url">Ou URL de l’image</label>
                    <input
                        id="img_jeu_url"
                        type="url"
                        name="img_jeu_url"
                        maxlength="255"
                        placeholder="https://exemple.com/image.jpg"
                        value="<?= offre_h(is_string($_POST['img_jeu_url'] ?? null) ? $_POST['img_jeu_url'] : '') ?>"
                    >
                    <small>Si un fichier est choisi, il est utilisé en priorité.</small>
                </div>



            </div>


            <button
                type="submit"
                class="btn-primary"
            >
                Enregistrer le jeu
            </button>


        </form>

    </div>


</main>

</body>

</html>