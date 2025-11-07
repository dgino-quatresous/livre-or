<?php
require_once __DIR__ . '/header.php';
ensure_session_started();

// Connexion DB
$conn = new mysqli("localhost", "root", "", "livreor");
if ($conn->connect_error) {
    $dbError = "Impossible de se connecter à la base de données: " . $conn->connect_error;
} else {
    $dbError = null;
}

// Liste de tables candidates pour les commentaires
$candidates = ['commentaires','commentaire','livre_or','livreor','comments','comment','messages','posts'];
$table = null;
$columns = [];
if (!$dbError) {
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

// Détecter colonnes utiles
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
    // fallbacks
    if (!$contentCol) {
        // try common names
        foreach (['contenu','texte','body','message','comment'] as $try) {
            if (in_array($try,$columns)) { $contentCol = $try; break; }
        }
    }
    if (!$dateCol) {
        foreach (['created_at','created','date_post','date'] as $try) {
            if (in_array($try,$columns)) { $dateCol = $try; break; }
        }
    }
}

// Préparer requête d'affichage
$comments = [];
if ($table && !$dbError) {
    // Construire SELECT selon colonnes trouvées
    $selectCols = [];
    if ($dateCol) $selectCols[] = "`$dateCol`";
    if ($userCol) $selectCols[] = "`$userCol`";
    if ($contentCol) $selectCols[] = "`$contentCol`";
    // Toujours sélectionner id si présent
    if (in_array('id',$columns)) $selectCols[] = '`id`';
    if (empty($selectCols)) {
        // select all as fallback
        $sql = "SELECT * FROM `" . $conn->real_escape_string($table) . "` ORDER BY ";
        if ($dateCol) $sql .= "`$dateCol` DESC"; else $sql .= "`id` DESC";
    } else {
        $sql = "SELECT " . implode(', ', array_unique($selectCols)) . " FROM `" . $conn->real_escape_string($table) . "` ORDER BY ";
        if ($dateCol) $sql .= "`$dateCol` DESC"; elseif (in_array('id',$columns)) $sql .= "`id` DESC"; else $sql .= implode(', ', array_unique($selectCols));
    }

    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $comments[] = $row;
        }
        $res->free();
    } else {
        $dbError = "Erreur lors de la lecture des commentaires: " . $conn->error . "; Requête: " . $sql;
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Livre d'or</title>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;padding:16px}
        .comment{border:1px solid #ddd;padding:12px;margin-bottom:12px;border-radius:6px}
        .meta{color:#666;font-size:0.9em;margin-bottom:8px}
        .add-link{display:inline-block;margin:8px 0}
    </style>
</head>
<body>
    <?php render_site_header(); ?>

    <h1>Livre d'or</h1>

    <?php if ($dbError): ?>
        <p style="color:red"><?= htmlspecialchars($dbError) ?></p>
        <p>Si la base de données n'a pas de table de commentaires, créez une table (ex: <code>commentaires</code>) avec au moins : <code>id</code>, <code>login</code> ou <code>id_utilisateur</code>, <code>commentaire</code> et <code>date</code>.</p>
    <?php else: ?>

        <?php if (empty($comments)): ?>
            <p>Aucun commentaire pour le moment.</p>
        <?php else: ?>
            <?php foreach ($comments as $c): ?>
                <div class="comment">
                    <div class="meta">
                        <?php
                        // date
                        $displayDate = null;
                        if ($dateCol && isset($c[$dateCol]) && $c[$dateCol] !== null) {
                            $t = strtotime($c[$dateCol]);
                            if ($t !== false) $displayDate = date('d/m/Y', $t);
                            else $displayDate = htmlspecialchars($c[$dateCol]);
                        }
                        // user
                        $displayUser = null;
                        if ($userCol && isset($c[$userCol]) && $c[$userCol] !== null) {
                            $rawUser = $c[$userCol];
                            // if numeric, try to fetch login from utilisateurs
                            if (is_numeric($rawUser)) {
                                $uStmt = $conn->prepare("SELECT login FROM utilisateurs WHERE id = ? LIMIT 1");
                                if ($uStmt) {
                                    $uStmt->bind_param('i', $rawUser);
                                    $uStmt->execute();
                                    $uRes = $uStmt->get_result();
                                    if ($uRow = $uRes->fetch_assoc()) $displayUser = $uRow['login'];
                                    $uStmt->close();
                                }
                            } else {
                                $displayUser = $rawUser;
                            }
                        }
                        // fallback values
                        if (!$displayDate) $displayDate = 'date inconnue';
                        if (!$displayUser) $displayUser = 'anonyme';

                        echo 'posté le ' . htmlspecialchars($displayDate) . ' par ' . htmlspecialchars($displayUser);
                        ?>
                    </div>
                    <div class="body">
                        <?php
                        $text = '';
                        if ($contentCol && isset($c[$contentCol])) $text = $c[$contentCol];
                        else {
                            // try to show any text-like column
                            foreach ($c as $k=>$v) {
                                if ($k === 'id' || $k === $userCol || $k === $dateCol) continue;
                                if (is_string($v) && strlen($v) > 0) { $text = $v; break; }
                            }
                        }
                        echo nl2br(htmlspecialchars($text));
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    <?php endif; ?>

    <?php if (isset($_SESSION['user'])): ?>
        <a class="add-link" href="commentaire.php">Ajouter un commentaire</a>
    <?php endif; ?>

</body>
</html>
