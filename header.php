<?php
// header.php
// Fournit ensure_session_started() et render_site_header() pour afficher
// un header commun sur toutes les pages sans provoquer d'envoi prématuré d'entêtes.

function ensure_session_started() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function render_site_header() {
    // Assurer la session (au cas où)
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $user = isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']) : null;
    ?>
    <header style="padding:12px;border-bottom:1px solid #ddd;margin-bottom:16px;">
        <nav>
            <?php if (!$user): ?>
                <a href="index.php">Accueil</a>
                <a href="inscription.php">Inscription</a>
                <a href="connexion.php">Connexion</a>
            <?php else: ?>
                <span>Bienvenue, <?= $user ?></span>
                <a href="index.php">Accueil</a>
                <a href="livre-or.php">Livre d'or</a>
                <a href="profil.php">Profil</a>
                <a href="deconnexion.php">Déconnexion</a>
            <?php endif; ?>
        </nav>
    </header>
    <?php
}

?>
