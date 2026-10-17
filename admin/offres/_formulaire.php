<?php
require_once __DIR__.'/_commun.php';
$id=$afficheId??null;$a=$id?offre_lire($pdo,$id):['img_affiche'=>'','date_ajout'=>date('Y-m-d H:i:s')];
$imageUrl='';
$dateValeur=str_replace(' ','T',(string)$a['date_ajout']);$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $dateValeur=is_string($_POST['date_ajout']??null)?$_POST['date_ajout']:'';
    $dt=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $dateValeur)?:DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s', $dateValeur);
    $imageUrl=is_string($_POST['img_affiche_url']??null)?trim($_POST['img_affiche_url']):'';
    $nouveauChemin=null;
    try{
        if(!offre_csrf_valide())throw new DomainException('Formulaire expiré. Rechargez la page.');
        if(!$dt || !in_array($dateValeur,[$dt->format('Y-m-d\TH:i'),$dt->format('Y-m-d\TH:i:s')],true))throw new DomainException('Date invalide.');
        $file=$_FILES['img_affiche']??null;
        if($file && $file['error']!==UPLOAD_ERR_NO_FILE){
            if($file['error']!==UPLOAD_ERR_OK || $file['size']>8*1024*1024)throw new DomainException('Image non reçue ou trop lourde (8 Mo maximum).');
            $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $formats=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
            if(!isset($formats[$mime]) || !getimagesize($file['tmp_name']))throw new DomainException('Choisissez une image JPG, PNG ou WEBP.');
            $dir=__DIR__.'/../uploads/affiches';
            if(!is_dir($dir) && !mkdir($dir,0755,true))throw new RuntimeException('Dossier inaccessible.');
            $path='admin/uploads/affiches/'.bin2hex(random_bytes(16)).'.'.$formats[$mime];
            $nouveauChemin=dirname(__DIR__,2).'/'.$path;
            if(!move_uploaded_file($file['tmp_name'],$nouveauChemin))throw new RuntimeException('Échec de l’envoi.');
        }elseif($imageUrl!==''){
            if(!affiche_url_valide($imageUrl))throw new DomainException('Saisissez un lien HTTP ou HTTPS valide (255 caractères maximum).');
            $path=$imageUrl;
        }else{$path=$a['img_affiche'];if(!$id)throw new DomainException('Choisissez une image sur votre PC ou saisissez son URL.');}
        if($id){$s=$pdo->prepare('UPDATE affiche SET img_affiche=?,date_ajout=? WHERE id_affiche=?');$s->execute([$path,$dt->format('Y-m-d H:i:s'),$id]);}
        else{$s=$pdo->prepare('INSERT INTO affiche (img_affiche,date_ajout) VALUES (?,?)');$s->execute([$path,$dt->format('Y-m-d H:i:s')]);}
        offre_flash('L’affiche a été enregistrée.');
    }catch(Throwable $e){if($nouveauChemin && is_file($nouveauChemin))unlink($nouveauChemin);if($e instanceof DomainException)$error=$e->getMessage();else{error_log('Affiche : '.$e->getMessage());$error='Enregistrement impossible. Vérifiez le dossier des images et réessayez.';}}
}
$title=$id?'Modifier l’affiche':'Ajouter une affiche';
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=offre_h($title)?></title><link rel="stylesheet" href="../assets/css/admin.css"><link rel="stylesheet" href="../assets/css/offres-admin.css"></head><body><?php require __DIR__.'/../includes/sidebar.php';?><main><div class="page-header"><div><h1><?=offre_h($title)?></h1><p>Choisissez une photo depuis votre PC ou par URL, puis sa date d’ajout.</p></div><a class="btn-secondary" href="index.php">Retour aux affiches</a></div>
<div class="form-box"><?php if($error):?><p class="alert-error" role="alert"><?=offre_h($error)?></p><?php endif;?><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=offre_h(offre_token())?>"><div class="form-grid"><div class="form-group full-width"><label for="img_affiche">Photo de l’annonce<?=$id?'':' *'?></label><input id="img_affiche" type="file" name="img_affiche" accept="image/jpeg,image/png,image/webp"><p class="field-help">JPG, PNG ou WEBP, 8 Mo maximum.</p></div><div class="form-group full-width"><label for="img_affiche_url">Ou URL de l’image</label><input id="img_affiche_url" name="img_affiche_url" type="url" maxlength="255" placeholder="https://exemple.com/image.jpg" value="<?=offre_h($imageUrl)?>"><p class="field-help">Lien direct vers une image HTTP ou HTTPS. Si les deux sont renseignés, le fichier PC est utilisé.<?=$id?' Laissez les deux champs vides pour conserver la photo actuelle.':''?></p><?php if($a['img_affiche']):?><img class="affiche-preview" src="<?=offre_h(affiche_image($a['img_affiche']))?>" alt="Photo actuelle"><?php endif;?></div><div class="form-group"><label for="date_ajout">Date d’ajout *</label><input id="date_ajout" name="date_ajout" type="datetime-local" step="1" required value="<?=offre_h($dateValeur)?>"></div></div><div class="form-actions"><button class="btn-primary">Enregistrer l’affiche</button><a class="btn-secondary" href="index.php">Annuler</a></div></form></div></main></body></html>
