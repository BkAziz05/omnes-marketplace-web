<?php
// ============================================================
// proposal.php - Ajouter une nouvelle proposition dans une négociation
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

requireRole('acheteur');

$pdo = db();
$idAcheteur = currentUserId();
$errors = [];
$idNegociation = (int)($_GET['id_negociation'] ?? $_POST['id_negociation'] ?? 0);

/**
 * Crée une notification simple.
 */
function createNotification(PDO $pdo, string $message, string $type, ?int $idAcheteur = null, ?int $idVendeur = null): void
{
    $stmt = $pdo->prepare("
        INSERT INTO notification (message, type, idAcheteur, idVendeur)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$message, $type, $idAcheteur, $idVendeur]);
}

$stmt = $pdo->prepare("
    SELECT n.*, a.nom AS article_nom
    FROM negociation n
    JOIN article a ON n.idArticle = a.idArticle
    WHERE n.id_negociation = ? AND n.idAcheteur = ?
    LIMIT 1
");
$stmt->execute([$idNegociation, $idAcheteur]);
$negociation = $stmt->fetch();

if (!$negociation) {
    setFlash('error', 'Négociation introuvable.');
    redirect(BASE_URL . '/negotiation.php');
}

if ($negociation['statut'] !== 'en_cours') {
    setFlash('error', 'Cette négociation n\'est plus en cours.');
    redirect(BASE_URL . '/negotiation.php?id=' . $idNegociation);
}

if ((int)$negociation['nb_tours'] >= (int)NEGOCIATION_MAX_TOURS) {
    setFlash('error', 'Le nombre maximum de tours a été atteint.');
    redirect(BASE_URL . '/negotiation.php?id=' . $idNegociation);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Token CSRF invalide.";
    } else {
        $montant = (float)($_POST['montant'] ?? 0);

        if ($montant <= 0) {
            $errors[] = "Le montant doit être supérieur à 0.";
        }

        if (empty($errors)) {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO proposition (montant, emetteur, statut, id_negociation)
                VALUES (?, 'acheteur', 'en_attente', ?)
            ");
            $stmt->execute([$montant, $idNegociation]);

            $stmt = $pdo->prepare("
                UPDATE negociation
                SET nb_tours = nb_tours + 1
                WHERE id_negociation = ?
            ");
            $stmt->execute([$idNegociation]);

            createNotification(
                $pdo,
                "Nouvelle proposition dans la négociation de l'article \"" . $negociation['article_nom'] . "\".",
                'negociation',
                null,
                (int)$negociation['idVendeur']
            );

            $pdo->commit();

            setFlash('success', 'Votre proposition a été envoyée.');
            redirect(BASE_URL . '/negotiation.php?id=' . $idNegociation);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Nouvelle proposition - Omnes MarketPlace</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        body { background:#f8fafa; font-family:Arial, sans-serif; }
        .page { max-width:700px; margin:30px auto; padding:20px; }
        .box { background:#fff; border:1px solid #d9e6e6; border-radius:10px; padding:20px; }
        .field { margin-bottom:12px; }
        .field label { display:block; margin-bottom:5px; font-weight:bold; }
        .field input { width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; }
        .btn { display:inline-block; padding:10px 16px; border:none; border-radius:8px; cursor:pointer; text-decoration:none; background:#0e8c8c; color:#fff; }
        .msg-err { background:#fdecea; color:#c62828; padding:10px; border-radius:8px; margin-bottom:10px; }
    </style>
</head>
<body>
<div class="page">
    <div class="box">
        <h1>Faire une proposition</h1>
        <p>Article : <?= h($negociation['article_nom']) ?></p>
        <p>Tours utilisés : <?= (int)$negociation['nb_tours'] ?> / <?= (int)NEGOCIATION_MAX_TOURS ?></p>

        <?php foreach ($errors as $error): ?>
            <div class="msg-err"><?= h($error) ?></div>
        <?php endforeach; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="id_negociation" value="<?= (int)$idNegociation ?>">

            <div class="field">
                <label>Votre nouveau prix proposé</label>
                <input type="number" name="montant" step="0.01" min="1" required>
            </div>

            <button class="btn" type="submit">Envoyer la proposition</button>
        </form>

        <p style="margin-top:15px;">
            <a href="<?= BASE_URL ?>/negotiation.php?id=<?= (int)$idNegociation ?>">Retour à la négociation</a>
        </p>
    </div>
</div>
</body>
</html>