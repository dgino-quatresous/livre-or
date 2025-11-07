$
<?php
require_once __DIR__ . '/header.php';
ensure_session_started();
// Traitement du formulaire avant tout affichage HTML pour permettre l'utilisation de header()
$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = isset($_POST["login"]) ? trim($_POST["login"]) : '';
    $password = isset($_POST["password"]) ? $_POST["password"] : '';
    $confirm_password = isset($_POST["confirm_password"]) ? $_POST["confirm_password"] : '';

    if ($confirm_password !== $password) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        // Connexion à la base de données
        $conn = new mysqli("localhost", "root", "", "livreor");
        if ($conn->connect_error) {
            die("Connexion échouée: " . $conn->connect_error);
        }
        // Vérification si l'utilisateur existe déjà
        $stmt = $conn->prepare("SELECT id FROM utilisateurs WHERE login = ?");
        $stmt->bind_param("s", $login);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $error = "Nom d'utilisateur déjà pris.";
            $stmt->close();
            $conn->close();
        } else {
            // Insertion du nouvel utilisateur
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO utilisateurs (login, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $login, $hashed_password);
            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();
                // Redirection sans affichage préalable
                header("Location: connexion.php");
                exit;
            } else {
                $error = "Erreur lors de l'inscription: " . $stmt->error;
                $stmt->close();
                $conn->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
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

        <label for="confirm_password">Confirmation mot de passe:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>

        <button type="submit">S'inscrire</button>
    </form>
</body>
</html>