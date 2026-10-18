<?php
require_once __DIR__.'/_commun.php';
$affiches=$pdo->query('SELECT * FROM affiche ORDER BY date_ajout DESC,id_affiche DESC')->fetchAll(PDO::FETCH_ASSOC);
$flash=$_SESSION['offres_flash']??'';unset($_SESSION['offres_flash']);
?>
<!doctype html>
<html lang="fr">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Gestion des affiches</title>
        <link rel="stylesheet" href="../assets/css/admin.css">
        <link rel="stylesheet" href="../assets/css/offres-admin.css">
    </head>

    <body><?php require __DIR__.'/../includes/sidebar.php';?>
    <main>
<div class="page-header">
    <div>
        <h1>Gestion des affiches</h1>
        <p>Photos et dates des annonces de l’accueil.</p>
    </div>
    <a class="btn-primary" href="ajouter.php">+ Ajouter une affiche</a>
</div>
<?php if($flash):?>
    <p class="alert-success" role="status"><?=offre_h($flash)?></p>
    <?php endif;?>
<div class="ticket-admin-grid"><?php foreach($affiches as $a):?>
    <article class="ticket-admin-card">
        <h2>Affiche #<?=(int)$a['id_affiche']?></h2>
    <img class="affiche-preview" src="<?=offre_h(affiche_image($a['img_affiche']))?>" alt="Affiche #<?=(int)$a['id_affiche']?>">
    <p>Date d’ajout : <?=offre_h($a['date_ajout'])?></p>
    <div class="actions-box">
        <a class="btn-edit" href="modifier.php?id=<?=(int)$a['id_affiche']?>">Modifier</a>
        <a class="btn-delete" href="supprimer.php?id=<?=(int)$a['id_affiche']?>">Supprimer</a>
    </div>

</article><?php endforeach;?>
</div>
<?php if(!$affiches):?>
    <div class="form-box">Aucune affiche. Ajoutez votre première photo.</div><?php endif;?>
</main>
</body>
</html>
