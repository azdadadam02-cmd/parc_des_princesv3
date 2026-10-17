<?php
require_once __DIR__.'/_commun.php';
$q=is_string($_GET['q']??null)?trim($_GET['q']):'';$cat=promo_categorie(is_string($_GET['categorie']??null)?$_GET['categorie']:'');$etat=is_string($_GET['etat']??null)?$_GET['etat']:'';
$w=[];$args=[];
if($q!==''){$w[]='(nom_promo LIKE ? OR description LIKE ?)';$args[]='%'.$q.'%';$args[]='%'.$q.'%';}
if(isset(promo_categories()[$cat])){$w[]=$cat==='JOURNE'?"UPPER(categorie_ticket) IN ('JOURNE','JOURNEE','JOURNÉE')":'UPPER(categorie_ticket)=?';if($cat!=='JOURNE')$args[]=$cat;}
if(in_array($etat,['Actif','Inactif'],true)){$w[]='etat=?';$args[]=$etat;}
$s=$pdo->prepare('SELECT * FROM promotion_billet'.($w?' WHERE '.implode(' AND ',$w):'').' ORDER BY id_promo DESC');$s->execute($args);$tickets=$s->fetchAll(PDO::FETCH_ASSOC);
$catalogue=promo_catalogue($pdo);$flash=$_SESSION['promotions_flash']??'';unset($_SESSION['promotions_flash']);
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Gestion des tickets</title><link rel="stylesheet" href="../assets/css/admin.css"><link rel="stylesheet" href="../assets/css/offres-admin.css"></head><body><?php require __DIR__.'/../includes/sidebar.php';?><main>
<div class="page-header"><div><h1>Gestion des tickets</h1><p>Créez et gérez vos packs et les jeux inclus.</p></div><a href="ajouter.php" class="btn-primary">+ Ajouter un pack</a></div>
<?php if($flash):?><p class="alert-success" role="status"><?=offre_h($flash)?></p><?php endif;?>
<form class="filters-box" method="get"><input name="q" type="search" value="<?=offre_h($q)?>" placeholder="Nom ou description" aria-label="Rechercher"><select name="categorie" aria-label="Rubrique"><option value="">Toutes les rubriques</option><?php foreach(promo_categories() as $v=>$label):?><option value="<?=$v?>" <?=$cat===$v?'selected':''?>><?=$label?></option><?php endforeach;?></select><select name="etat" aria-label="État"><option value="">Tous les états</option><?php foreach(['Actif','Inactif'] as $e):?><option <?=$etat===$e?'selected':''?>><?=$e?></option><?php endforeach;?></select><button class="btn-primary">Filtrer</button><a class="btn-secondary" href="index.php">Réinitialiser</a></form>
<div class="ticket-admin-grid"><?php foreach($tickets as $p):?>
<article class="ticket-admin-card"><div class="pack-card-top"><span class="status-badge"><?=offre_h(promo_categorie($p['categorie_ticket']))?></span><span class="field-help"><?=offre_h($p['etat'])?></span></div>
<h2><?=offre_h($p['nom_promo'])?></h2>
<p class="pack-category"><?=offre_h(in_array(strtolower($p['type_promo']),['forfait','pourcentage','reduction'],true)?'Catégories à renseigner':$p['type_promo'])?></p>
<div class="annonce-meta"><span>Validité : <?=offre_h($p['delai'])?></span><span><?=(int)$p['capacite_membres']?> personne(s)</span></div>
<p class="field-help">Fin : <?=offre_h($p['date_fin']?:'Sans limite')?><?=($p['date_fin'] && $p['date_fin']<date('Y-m-d'))?' · Expiré':''?></p>
<strong class="ticket-admin-price"><?=offre_h(promo_prix($p))?></strong>
<details class="pack-details"><summary>Détails</summary><h3>Description</h3><p class="annonce-description"><?=offre_h($p['description']?:'Aucune description.')?></p><h3>Jeux inclus</h3><ul><?php foreach(promo_selection($p['jeux_inclus']) as $jid=>$qte):?><li><?=offre_h(promo_nom_jeu($catalogue,(int)$jid))?> : <?=offre_h($qte)?> ticket(s)</li><?php endforeach;?></ul><?php if(!promo_selection($p['jeux_inclus'])):?><p>Aucun jeu configuré.</p><?php endif;?></details>
<div class="actions-box"><a class="btn-edit" href="modifier.php?id=<?=(int)$p['id_promo']?>">Modifier</a><a class="btn-delete" href="desactiver.php?id=<?=(int)$p['id_promo']?>">Désactiver</a></div></article>
<?php endforeach;?></div><?php if(!$tickets):?><div class="form-box">Aucun pack trouvé.</div><?php endif;?></main></body></html>
