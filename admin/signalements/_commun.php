<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

function exp_h($value): string {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function exp_token(): string {
    if (empty($_SESSION['signalements_csrf'])) {
        $_SESSION['signalements_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['signalements_csrf'];
}

function exp_csrf(): void {
    $token = $_POST['csrf'] ?? '';

    if (!is_string($token) || !hash_equals(exp_token(), $token)) {
        throw new DomainException(
            'Formulaire expiré. Rechargez la page.'
        );
    }
}

function exp_cin(string $cin): string {
    return strtoupper(
        preg_replace('/[\s-]+/', '', trim($cin))
    );
}

function exp_cin_valide(string $cin): bool {
    return (bool) preg_match('/^[A-Z0-9]{4,20}$/D', $cin);
}