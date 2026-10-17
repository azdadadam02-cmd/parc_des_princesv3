<?php
require_once __DIR__.'/_commun.php';$id=promo_id();$p=promo_lire($pdo,$id);$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!offre_csrf_valide())$error='Formulaire expiré. Rechargez la page.';
    else{try{$s=$pdo->prepare("UPDATE promotion_billet SET etat='Inactif' WHERE id_promo=?");$s->execute([$id]);promo_flash('Le ticket a été désactivé. Son historique est conservé.');}catch(Throwable $e){error_log($e->getMessage());$error='Désactivation impossible. Réessayez.';}}
}
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Désactiver un ticket</title><link rel="stylesheet" href="../assets/css/admin.css"></head><body><?php require __DIR__.'/../includes/sidebar.php';?><main><div class="page-header"><div><h1>Désactiver le ticket</h1><p>Le ticket et ses anciennes commandes seront conservés.</p></div></div><div class="form-box"><?php if($error):?><p class="alert-error" role="alert"><?=offre_h($error)?></p><?php endif;?><h2><?=offre_h($p['nom_promo'])?></h2><form method="post" class="form-actions"><input type="hidden" name="csrf" value="<?=offre_h(offre_token())?>"><button class="btn-delete">Confirmer la désactivation</button><a class="btn-secondary" href="index.php">Annuler</a></form></div></main></body></html>
