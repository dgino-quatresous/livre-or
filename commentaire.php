<?php
require_once __DIR__ . '/header.php';
ensure_session_started();

// Accessible uniquement aux utilisateurs connectés
if (!isset($_SESSION['user'])) {
    header('Location: connexion.php');
    exit;
}

$error = '';
$success = false;

// Connexion DB
$conn = new mysqli("localhost", "root", "", "livreor");
if ($conn->connect_error) {
    $error = "Impossible de se connecter à la base de données: " . $conn->connect_error;
}

// Déterminer la table et les colonnes comme dans livre-or.php
$table = null;
$columns = [];
$candidates = ['commentaires','commentaire','livre_or','livreor','comments','comment','messages','posts'];
if (empty($error)) {
    foreach ($candidates as $cand) {
        $res = $conn->query("DESCRIBE `" . $conn->real_escape_string($cand) . "`");
        if ($res && $res->num_rows > 0) {
            $table = $cand;
            while ($col = $res->fetch_assoc()) {
                $columns[] = $col['Field'];
            }
            break;
        }
        if ($res) $res->free();
    }
}

$contentCol = null; $userCol = null; $dateCol = null;
if ($table) {
    foreach ($columns as $c) {
        $lower = strtolower($c);
        if (!$contentCol && (strpos($lower,'comment')!==false || strpos($lower,'message')!==false || strpos($lower,'texte')!==false || strpos($lower,'content')!==false || strpos($lower,'post')!==false)) {
            $contentCol = $c;
        }
        if (!$userCol && (strpos($lower,'login')!==false || strpos($lower,'user')!==false || strpos($lower,'utilis')!==false || strpos($lower,'author')!==false || strpos($lower,'id_util')!==false || $lower==='user_id' || $lower==='id_user')) {
            $userCol = $c;
        }
        if (!$dateCol && (strpos($lower,'date')!==false || strpos($lower,'created')!==false || strpos($lower,'time')!==false || strpos($lower,'timestamp')!==false)) {
            $dateCol = $c;
        }
    }
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $commentText = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    if ($commentText === '') {
        $error = 'Le commentaire ne peut pas être vide.';
    } elseif (!$table) {
        $error = 'Aucune table de commentaires trouvée dans la base de données.';
    } else {
        // Préparer valeurs à insérer
        $cols = [];
        $vals = [];
        $types = '';

        // contenu
        if ($contentCol) {
            $cols[] = "`$contentCol`";
            $vals[] = $commentText;
            $types .= 's';
        } else {
            // pas de colonne texte détectée -> essayer d'insérer dans n'importe quelle colonne string
            // chercher une colonne textuelle
            $foundText = false;
            foreach ($columns as $c) {
                if (in_array($c, ['id','date','created_at','created'])) continue;
                $cols[] = "`$c`";
                $vals[] = $commentText;
                $types .= 's';
                $foundText = true;
                break;
            }
            if (!$foundText) {
                $error = 'Aucune colonne disponible pour stocker le commentaire.';
            }
        }

        // utilisateur
        if ($userCol) {
            // si colonne contient "id" ou ressemble à id, insérer id utilisateur
            $lower = strtolower($userCol);
            if (strpos($lower,'id') !== false && $lower !== 'login') {
                // récupérer id de l'utilisateur connecté
                $uId = null;
                $uStmt = $conn->prepare("SELECT id FROM utilisateurs WHERE login = ? LIMIT 1");
                if ($uStmt) {
                    $uStmt->bind_param('s', $_SESSION['user']);
                    $uStmt->execute();
                    $uRes = $uStmt->get_result();
                    if ($uRow = $uRes->fetch_assoc()) $uId = $uRow['id'];
                    $uStmt->close();
                }
                $cols[] = "`$userCol`";
                $vals[] = $uId;
                $types .= 'i';
            } else {
                // colonne login ou similaire
                $cols[] = "`$userCol`";
                $vals[] = $_SESSION['user'];
                $types .= 's';
            }
        }

        // date
        if ($dateCol) {
            $cols[] = "`$dateCol`";
            $now = date('Y-m-d H:i:s');
            $vals[] = $now;
            $types .= 's';
        }

        if (empty($error)) {
            $colsStr = implode(', ', $cols);
            $placeholders = implode(', ', array_fill(0, count($vals), '?'));
            $sql = "INSERT INTO `" . $conn->real_escape_string($table) . "` ($colsStr) VALUES ($placeholders)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $error = 'Erreur préparation insertion: ' . $conn->error;
            } else {
                // bind params dynamically
                $bindNames = [];
                $bindNames[] = $types;
                for ($i=0;$i<count($vals);$i++) {
                    $bindNames[] = & $vals[$i];
                }
                call_user_func_array([$stmt, 'bind_param'], $bindNames);
                if ($stmt->execute()) {
                    $success = true;
                    $stmt->close();
                    // Redirection vers le livre d'or
                    header('Location: livre-or.php');
                    exit;
                } else {
                    $error = 'Erreur lors de l\'enregistrement: ' . $stmt->error;
                    $stmt->close();
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Ajouter un commentaire</title>
    <style>label{display:block;margin-top:8px;}textarea{width:100%;min-height:120px}</style>
    <?php render_site_head(); ?>
</head>
<body>
    <?php render_site_header(); ?>

    <h1>Ajouter un commentaire</h1>

    <?php if (!empty($error)): ?>
        <p style="color:red"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form action="" method="post">
        <label for="comment">Votre commentaire :</label>
        <textarea id="comment" name="comment" required><?php if (isset($_POST['comment'])) echo htmlspecialchars($_POST['comment']); ?></textarea>
        <button type="submit">Poster</button>
    </form>

</body>
</html>
