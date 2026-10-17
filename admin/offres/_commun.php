<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

function offre_h($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); }
function offre_token(): string {
    if (empty($_SESSION['offres_csrf'])) { $_SESSION['offres_csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['offres_csrf'];
}
function offre_csrf_valide(): bool {
    return is_string($_POST['csrf'] ?? null) && hash_equals(offre_token(), $_POST['csrf']);
}
function offre_date_valide(string $value): bool {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value;
}
function offre_id(): int {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id || $id < 1) { http_response_code(404); exit('Affiche introuvable.'); }
    return $id;
}
function offre_lire(PDO $pdo, int $id): array {
    $stmt = $pdo->prepare('SELECT * FROM affiche WHERE id_affiche = ?');
    $stmt->execute([$id]);
    $offre = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$offre) { http_response_code(404); exit('Affiche introuvable.'); }
    return $offre;
}
function offre_flash(string $message): void {
    $_SESSION['offres_flash'] = $message;
    header('Location: index.php'); exit;
}

function offre_longueur(string $s): int { return preg_match_all("/./us", $s); }
function offre_resume(string $s): string { $chars = preg_split("//u", $s, -1, PREG_SPLIT_NO_EMPTY) ?: []; return implode("", array_slice($chars, 0, 100)) . (count($chars) > 100 ? "…" : ""); }

function affiche_image(string $path): string {
    if($path!=='' && preg_match('~^[a-zA-Z0-9_./-]+$~D',$path) && !in_array('..',explode('/',$path),true))return '../../'.$path;
    return '../assets/img/logo.png';
}
