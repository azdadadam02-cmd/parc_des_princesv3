<?php
require_once __DIR__.'/../offres/_commun.php';
function promo_categories(): array { return ['GROUPS'=>'GROUPS','SPECIAL'=>'SPECIAL','JOURNE'=>'JOURNE']; }
function promo_categorie(string $v): string {
    $v=strtoupper(trim($v)); return in_array($v,['JOURNEE','JOURNÉE'],true)?'JOURNE':$v;
}
function promo_prix(array $p): string { return number_format((float)$p['valeur'],2,',',' ').' DH'; }
function promo_lire(PDO $pdo,int $id): array {
    $s=$pdo->prepare('SELECT * FROM promotion_billet WHERE id_promo=?');$s->execute([$id]);$p=$s->fetch(PDO::FETCH_ASSOC);
    if(!$p){http_response_code(404);exit('Ticket introuvable.');}return $p;
}
function promo_id(): int { return offre_id(); }
function promo_flash(string $msg): void { $_SESSION['promotions_flash']=$msg;header('Location: index.php');exit; }
function promo_selection($json): array {
    $data=json_decode((string)$json,true);return is_array($data)?$data:[];
}
function promo_catalogue(PDO $pdo): array {
    return $pdo->query('SELECT j.id_jeu,j.nom,j.etat,c.nom_categorie FROM jeu j LEFT JOIN categorie c ON c.id_categorie=j.id_categorie ORDER BY c.nom_categorie,j.nom')->fetchAll(PDO::FETCH_ASSOC);
}
function promo_nom_jeu(array $catalogue,int $id): string {
    foreach($catalogue as $j)if((int)$j['id_jeu']===$id)return $j['nom'];
    return 'Jeu #'.$id.' (absent du catalogue)';
}
