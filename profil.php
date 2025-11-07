<?php
require_once __DIR__ . '/header.php';
ensure_session_started();
// Protection de la page : redirection si pas connecté
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil</title>
    <style>label{display:block;margin-top:8px;}input{display:block;margin-bottom:8px;}</style>
</head>
<body>
    <?php render_site_header(); ?>
    <p>Bienvenue, <?= htmlspecialchars($_SESSION['user']) ?>!</p>
    <form action="" method="post">
        <label for="login">Nom d'utilisateur:</label>
        <input type="text" id="login" name="login" value="<?= htmlspecialchars($_SESSION['user']) ?>" disabled>

        <label for="password">Mot de passe:</label>
        <input type="password" id="password" name="password">

        <button type="submit">Mettre à jour le profil</button>
    </form>
</body>
</html>