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
    <header class="site-header">
        <div class="site-header-inner">
            <div class="site-brand"><a class="brand-link" href="index.php">Livre d'or</a></div>
            <nav class="site-nav">
                <?php if (!$user): ?>
                    <a class="nav-link" href="index.php">Accueil</a>
                    <a class="nav-link" href="inscription.php">Inscription</a>
                    <a class="nav-link" href="connexion.php">Connexion</a>
                <?php else: ?>
                    <span class="welcome">Bienvenue, <?= $user ?></span>
                    <a class="nav-link" href="index.php">Accueil</a>
                    <a class="nav-link" href="livre-or.php">Livre d'or</a>
                    <a class="nav-link" href="profil.php">Profil</a>
                    <a class="nav-link" href="deconnexion.php">Déconnexion</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <?php
}

// Emet les balises à placer dans le <head> (feuille de style du site, meta theme-color)
function render_site_head() {
    // Chemin relatif attendu : ./styles.css à la racine du projet
    echo "    <link rel=\"stylesheet\" href=\"styles.css\">\n";
    echo "    <meta name=\"theme-color\" content=\"#b22222\">\n";
}

?>
