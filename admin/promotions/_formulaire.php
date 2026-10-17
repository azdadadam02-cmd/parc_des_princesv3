<?php
require_once __DIR__.'/_commun.php';
$id=$promoId??null;
$f=$id?promo_lire($pdo,$id):['nom_promo'=>'','categorie_ticket'=>'GROUPS','type_promo'=>'','valeur'=>'','capacite_membres'=>1,'delai'=>'24/H','date_fin'=>'','etat'=>'Actif','description'=>'','jeux_inclus'=>'{}'];
$f['categorie_ticket']=promo_categorie($f['categorie_ticket']);
if(in_array(strtolower($f['type_promo']),['forfait','pourcentage','reduction'],true))$f['type_promo']='';
$catalogue=promo_catalogue($pdo);$connus=array_column($catalogue,null,'id_jeu');
$selection=promo_selection($f['jeux_inclus']);$erreurs=[];
$heures=preg_match('/^(\d+)\s*\/H$/i',trim((string)$f['delai']),$m)?$m[1]:'';
if($_SERVER['REQUEST_METHOD']==='POST'){
    foreach(['nom_promo','categorie_ticket','type_promo','valeur','capacite_membres','date_fin','etat','description'] as $key)$f[$key]=is_string($_POST[$key]??null)?trim($_POST[$key]):'';
    $heures=is_string($_POST['validite_heures']??null)?trim($_POST['validite_heures']):'';
    $f['valeur']=str_replace(',','.',$f['valeur']);
    if(!offre_csrf_valide())$erreurs[]='Formulaire expiré. Rechargez la page.';
    if($f['nom_promo']==='' || offre_longueur($f['nom_promo'])>100)$erreurs[]='Nom du pack : 1 à 100 caractères.';
    if(!isset(promo_categories()[$f['categorie_ticket']]))$erreurs[]='Choisissez GROUPS, SPECIAL ou JOURNE.';
    if($f['type_promo']==='' || offre_longueur($f['type_promo'])>50)$erreurs[]='Catégories de jeux : 1 à 50 caractères.';
    if(!preg_match('/^\d{1,8}(\.\d{1,2})?$/D',$f['valeur']))$erreurs[]='Saisissez un prix positif ou nul avec deux décimales maximum.';
    if(filter_var($f['capacite_membres'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>1000]])===false)$erreurs[]='Capacité : de 1 à 1000 personnes.';
    if(filter_var($heures,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>8760]])===false)$erreurs[]='Validité : de 1 à 8760 heures.';
    if($f['date_fin']!=='' && !offre_date_valide($f['date_fin']))$erreurs[]='Date de fin invalide.';
    if(!in_array($f['etat'],['Actif','Inactif'],true))$erreurs[]='État invalide.';
    if(strlen($f['description'])>60000)$erreurs[]='Description trop longue.';
    $selection=[];$quantites=$_POST['quantites']??[];
    if(!is_array($quantites)){$erreurs[]='Sélection de jeux invalide.';$quantites=[];}
    foreach($quantites as $jeuId=>$qte){
        if(!is_string($qte)){$erreurs[]='Quantité invalide.';continue;}
        if($qte==='' || $qte==='0')continue;
        if(!ctype_digit((string)$jeuId) || !isset($connus[(int)$jeuId])){$erreurs[]='Un jeu sélectionné n’existe plus. Rechargez le formulaire.';continue;}
        if(filter_var($qte,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>1000]])===false){$erreurs[]='Quantité de tickets par jeu : de 0 à 1000.';continue;}
        $selection[(int)$jeuId]=(int)$qte;
    }
    if(!$selection)$erreurs[]='Sélectionnez au moins un jeu en indiquant sa quantité de tickets.';
    if(!$erreurs){
        $f['delai']=(int)$heures.'/H';
        $args=[$f['nom_promo'],$f['type_promo'],$f['date_fin']?:null,$f['delai'],$f['description'],(int)$f['capacite_membres'],$f['valeur'],$f['etat'],$f['categorie_ticket'],json_encode((object)$selection,JSON_THROW_ON_ERROR)];
        try{
            if($id){$s=$pdo->prepare('UPDATE promotion_billet SET nom_promo=?,type_promo=?,date_fin=?,delai=?,description=?,capacite_membres=?,valeur=?,etat=?,categorie_ticket=?,jeux_inclus=? WHERE id_promo=?');$s->execute(array_merge($args,[$id]));}
            else{$s=$pdo->prepare('INSERT INTO promotion_billet (nom_promo,type_promo,date_fin,delai,description,capacite_membres,valeur,etat,categorie_ticket,jeux_inclus) VALUES (?,?,?,?,?,?,?,?,?,?)');$s->execute($args);}
            promo_flash('Le pack a été enregistré.');
        }catch(PDOException $e){error_log('Pack : '.$e->getMessage());$erreurs[]='Enregistrement impossible. Réessayez.';}
    }
}
$title=$id?'Modifier le pack':'Ajouter un pack';
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=offre_h($title)?></title><link rel="stylesheet" href="../assets/css/admin.css"><link rel="stylesheet" href="../assets/css/offres-admin.css"></head><body><?php require __DIR__.'/../includes/sidebar.php';?><main>
<div class="page-header"><div><h1><?=offre_h($title)?></h1><p>Définissez le pack, son prix et ses jeux inclus.</p></div><a class="btn-secondary" href="index.php">Retour aux tickets</a></div>
<?php if($erreurs):?><div class="alert-error" role="alert"><ul><?php foreach(array_unique($erreurs) as $e):?><li><?=offre_h($e)?></li><?php endforeach;?></ul></div><?php endif;?>
<div class="form-box"><form method="post"><input type="hidden" name="csrf" value="<?=offre_h(offre_token())?>"><div class="form-grid">
<div class="form-group"><label for="nom_promo">Nom du pack *</label><input id="nom_promo" name="nom_promo" maxlength="100" required placeholder="PACK FAMILLE" value="<?=offre_h($f['nom_promo'])?>"></div>
<div class="form-group"><label for="categorie_ticket">Rubrique du ticket *</label><select id="categorie_ticket" name="categorie_ticket"><?php foreach(promo_categories() as $v=>$label):?><option value="<?=$v?>" <?=$f['categorie_ticket']===$v?'selected':''?>><?=$label?></option><?php endforeach;?></select></div>
<div class="form-group full-width"><label for="type_promo">Catégories de jeux affichées *</label><input id="type_promo" name="type_promo" maxlength="50" required placeholder="CLASSIC + ACTION" value="<?=offre_h($f['type_promo'])?>"><p class="field-help">Texte affiché sous le nom du pack, par exemple PS5 + ACTION. Le prix est le prix total du pack.</p></div>
<div class="form-group"><label for="validite_heures">Validité (heures) *</label><input id="validite_heures" name="validite_heures" type="number" min="1" max="8760" required value="<?=offre_h($heures)?>"><span class="field-help">48 sera enregistré et affiché 48/H.</span></div>
<div class="form-group"><label for="capacite_membres">Capacité (personnes) *</label><input id="capacite_membres" name="capacite_membres" type="number" min="1" max="1000" required value="<?=offre_h($f['capacite_membres'])?>"></div>
<div class="form-group"><label for="valeur">Prix total du pack (DH) *</label><input id="valeur" name="valeur" type="number" min="0" max="99999999.99" step=".01" required value="<?=offre_h($f['valeur'])?>"></div>
<div class="form-group"><label for="date_fin">Date de fin</label><input id="date_fin" name="date_fin" type="date" value="<?=offre_h($f['date_fin'])?>"><span class="field-help">Vide : aucune date de fin.</span></div>
<div class="form-group"><label for="etat">État *</label><select id="etat" name="etat"><?php foreach(['Actif','Inactif'] as $e):?><option <?=$f['etat']===$e?'selected':''?>><?=$e?></option><?php endforeach;?></select></div>
<div class="form-group full-width"><label for="description">Description</label><textarea id="description" name="description" rows="4"><?=offre_h($f['description'])?></textarea></div>
<div class="form-group full-width"><label>Jeux inclus *</label><p class="field-help">Nombre de tickets de chaque jeu dans un pack. Laissez 0 pour exclure le jeu.</p>
<?php foreach($selection as $ancienId=>$qte):if(!isset($connus[$ancienId])):?><p class="alert-error">Jeu #<?=offre_h($ancienId)?> absent du catalogue : choisissez son remplacement ci-dessous. Cette ancienne entrée sera retirée lors de l’enregistrement.</p><?php endif;endforeach;?>
<div class="pack-game-list"><?php foreach($catalogue as $j):$jid=(int)$j['id_jeu'];?><div class="pack-game-row"><label for="qte-<?=$jid?>"><strong><?=offre_h($j['nom'])?></strong><small><?=offre_h($j['nom_categorie'].' · '.$j['etat'])?> · #<?=$jid?></small></label><input aria-label="Tickets pour <?=offre_h($j['nom'])?>" id="qte-<?=$jid?>" name="quantites[<?=$jid?>]" type="number" min="0" max="1000" value="<?=offre_h($selection[$jid]??0)?>"></div><?php endforeach;?></div></div>
</div><div class="form-actions"><button class="btn-primary">Enregistrer le pack</button><a class="btn-secondary" href="index.php">Annuler</a></div></form></div>
</main></body></html>
