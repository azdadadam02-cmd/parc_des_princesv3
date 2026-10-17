<?php
$adminScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$adminPosition = strrpos($adminScript, '/admin/');
$adminBase = $adminPosition !== false ? substr($adminScript, 0, $adminPosition) . '/admin' : '/admin';
$adminSection = trim(substr($adminScript, strlen($adminBase)), '/');
$adminSection = explode('/', $adminSection)[0];
$adminLinks = [
    ['dashboard.php', 'Tableau de bord', 'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z'],
    ['jeux', 'Jeux', 'M6 8h12l3 10-3 2-4-4h-4l-4 4-3-2z M7 10v4 M5 12h4 M16 11h.01 M18 13h.01'],
    ['responsables', 'Responsables', 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8 M20 8v6 M17 11h6'],
    ['agents', 'Agents de vente', 'M4 21v-3a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v3 M12 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8'],
    ['clients', 'Clients', 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8 M17 4a4 4 0 0 1 0 8 M22 21v-2a4 4 0 0 0-3-4'],
    ['signalements', 'Signalements CIN', 'M12 3L2 21h20z M12 9v5 M12 17h.01'],
    ['reservations', 'Réservations', 'M3 5h18v16H3z M7 3v4 M17 3v4 M3 11h18 M7 15h3 M14 15h3'],
    ['Ventes', 'Ventes', 'M3 3v18h18 M7 15l4-5 4 3 6-8'],
    ['promotions', 'Gestion des tickets', 'M3 7h18v12H3z M15 7v12'],
    ['offres', 'Affiches accueil', 'M3 3h8l10 10-8 8L3 11z M7 7h.01'],
    ['billets', 'Billets', 'M3 7h18v4a2 2 0 0 0 0 4v4H3v-4a2 2 0 0 0 0-4z M15 7v3 M15 12v2 M15 16v3'],
    ['depenses', 'Dépenses', 'M4 3h16v18l-4-2-4 2-4-2-4 2z M8 8h8 M8 12h8 M8 16h4'],
    ['maintenance', 'Maintenance', 'M14 6a5 5 0 0 0-6 6l-5 5a3 3 0 0 0 4 4l5-5a5 5 0 0 0 6-6l-4 3-3-3z']
];
?>
<aside class="admin-sidebar">
    <a class="brand" href="<?= htmlspecialchars($adminBase . '/dashboard.php') ?>">
        <span class="brand-logo"><img src="<?= htmlspecialchars($adminBase . '/assets/img/logo.png') ?>" alt="Logo Parc des Princes"></span>
        <span><strong>Parc des Princes</strong><small>Administration</small></span>
    </a>
    <nav class="admin-nav" aria-label="Navigation administration">
        <p class="sidebar-label">Gestion du parc</p>
        <ul>
        <?php foreach ($adminLinks as [$section, $label, $icon]): ?>
            <li><a href="<?= htmlspecialchars($adminBase . '/' . $section . ($section === 'dashboard.php' ? '' : '/index.php')) ?>"<?= $adminSection === $section ? ' aria-current="page"' : '' ?>>
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="<?= $icon ?>"/></svg>
                <span><?= $label ?></span>
            </a></li>
        <?php endforeach; ?>
        </ul>
    </nav>
    <div class="sidebar-account">
        <div><small>Compte administrateur</small><span class="sidebar-email"><?= htmlspecialchars($_SESSION['admin_email'] ?? 'Administrateur') ?></span></div>
        <a class="logout-btn" href="<?= htmlspecialchars($adminBase . '/logout.php') ?>">Déconnexion</a>
    </div>
</aside>
