<?php
require_once __DIR__ . '/header.php';
ensure_session_started();
$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = isset($_POST["login"]) ? trim($_POST["login"]) : '';
    $password = isset($_POST["password"]) ? $_POST["password"] : '';


    $conn = new mysqli("localhost", "root", "", "livreor");
    if ($conn->connect_error) {
        $error = "Connexion échouée: " . $conn->connect_error;
    } else {
        $stmt = $conn->prepare("SELECT password FROM utilisateurs WHERE login = ?");
        $stmt->bind_param("s", $login);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $error = "Nom d'utilisateur incorrect.";
        } else {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row["password"])) {
                $_SESSION["user"] = $login;
                $stmt->close();
                $conn->close();
                header("Location: index.php");
                exit;
            } else {
                $error = "Mot de passe incorrect.";
            }
        }
        $stmt->close();
        $conn->close();
    }
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <style>label{display:block;margin-top:8px;}input{display:block;margin-bottom:8px;}</style>
    <?php render_site_head(); ?>
</head>
<body>
    <?php render_site_header(); ?>
    <?php if (!empty($error)): ?>
        <p style="color:red"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form action="" method="post">
        <label for="login">Nom d'utilisateur:</label>
        <input type="text" id="login" name="login" required>

        <label for="password">Mot de passe:</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Se connecter</button>
    </form>
</body>
</html>

