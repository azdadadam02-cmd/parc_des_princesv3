<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: text/plain; charset=utf-8');

$email = $_SESSION['user_email'] ?? '';
if ($email === '') {
    http_response_code(401);
    echo 'NON_CONNECTE';
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE commande SET vu = 1 WHERE email_client = ?");
    $stmt->execute([$email]);

    $stmt = $pdo->prepare("UPDATE reservation SET vu = 1 WHERE email_client = ?");
    $stmt->execute([$email]);

    $stmt = $pdo->prepare(
        "UPDATE billet b
         INNER JOIN commande c ON c.num_commande = b.num_commande
         SET b.vu = 1
         WHERE c.email_client = ?"
    );
    $stmt->execute([$email]);

    $pdo->commit();
    echo 'OK';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo 'ERREUR';
}
