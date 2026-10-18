<?php
require_once __DIR__.'/_commun.php';$id=offre_id();$a=offre_lire($pdo,$id);$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!offre_csrf_valide())$error='Formulaire expiré. Rechargez la page.';
    else{try{$s=$pdo->prepare('DELETE FROM affiche WHERE id_affiche=?');$s->execute([$id]);offre_flash('L’affiche a été supprimée.');}catch(PDOException $e){error_log($e->getMessage());$error='Suppression impossible. Réessayez.';}}
}
?>
<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Supprimer l’affiche</title>
        
        <link rel="stylesheet" href="../assets/css/admin.css">
        <link rel="stylesheet" href="../assets/css/offres-admin.css">
    </head>
    
    <body>
        <?php require __DIR__.'/../includes/sidebar.php';?>
        <main>
            <div class="page-header">
                <h1>Supprimer l’affiche #<?=$id?></h1>
            </div>
            
            <div class="form-box"><?php if($error):?>
                <p class="alert-error"><?=offre_h($error)?>
                </p><?php endif;?>
                <img class="affiche-preview" src="<?=offre_h(affiche_image($a['img_affiche']))?>" alt="Affiche à supprimer">
                <p>Retirer cette annonce de la liste des affiches ?</p>
                <form method="post" class="form-actions">
                    <input type="hidden" name="csrf" value="<?=offre_h(offre_token())?>">
                    <button class="btn-delete">Supprimer l’affiche</button>
                    <a class="btn-secondary" href="index.php">Annuler</a>
                </form>
            </div>
        </main>
    </body>
</html>
