<?php
// ============================================================
// alerts.php - Gestion des alertes acheteur
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

requireRole('acheteur');

$pdo = db();
$idAcheteur = currentUserId();
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Token CSRF invalide.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $motsCles = clean($_POST['mots_cles'] ?? '');
            $prixMax = $_POST['prix_max'] !== '' ? (float)$_POST['prix_max'] : null;
            $mode = clean($_POST['mode_vente_souhaite'] ?? 'tous');

            if (!in_array($mode, ['immediat', 'negotiation', 'enchere', 'tous'])) {
                $errors[] = "Mode de vente invalide.";
            }

            if (empty($errors)) {
                $stmt = $pdo->prepare("
                    INSERT INTO alerte (mots_cles, prix_max, mode_vente_souhaite, active, idAcheteur)
                    VALUES (?, ?, ?, 1, ?)
                ");
                $stmt->execute([$motsCles ?: null, $prixMax, $mode, $idAcheteur]);
                $success = "Alerte ajoutée avec succès.";
            }
        }

        elseif ($action === 'delete') {
            $idAlerte = (int)($_POST['idAlerte'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM alerte WHERE idAlerte = ? AND idAcheteur = ?");
            $stmt->execute([$idAlerte, $idAcheteur]);
            $success = "Alerte supprimée.";
        }

        elseif ($action === 'toggle') {
            $idAlerte = (int)($_POST['idAlerte'] ?? 0);
            $stmt = $pdo->prepare("
                UPDATE alerte
                SET active = IF(active = 1, 0, 1)
                WHERE idAlerte = ? AND idAcheteur = ?
            ");
            $stmt->execute([$idAlerte, $idAcheteur]);
            $success = "État de l'alerte modifié.";
        }
    }
}

$stmt = $pdo->prepare("
    SELECT *
    FROM alerte
    WHERE idAcheteur = ?
    ORDER BY idAlerte DESC
");
$stmt->execute([$idAcheteur]);
$alertes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes alertes - Omnes MarketPlace</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        body { background:#f8fafa; font-family:Arial, sans-serif; }
        .page { max-width:1100px; margin:30px auto; padding:20px; }
        .box { background:#fff; border:1px solid #d9e6e6; border-radius:10px; padding:20px; margin-bottom:20px; }
        .field { margin-bottom:12px; }
        .field label { display:block; margin-bottom:5px; font-weight:bold; }
        .field input, .field select { width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; }
        .btn { display:inline-block; padding:10px 16px; border:none; border-radius:8px; cursor:pointer; text-decoration:none; margin-right:10px; }
        .btn-primary { background:#0e8c8c; color:#fff; }
        .btn-light { background:#edf2f2; color:#1e3535; }
        .btn-danger { background:#c62828; color:#fff; }
        .msg-ok { background:#e8f5e9; color:#2e7d32; padding:10px; border-radius:8px; margin-bottom:10px; }
        .msg-err { background:#fdecea; color:#c62828; padding:10px; border-radius:8px; margin-bottom:10px; }
    </style>
</head>
<body>
<div class="page">
    <h1>Mes alertes</h1>

    <?php if ($success): ?>
        <div class="msg-ok"><?= h($success) ?></div>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <div class="msg-err"><?= h($error) ?></div>
    <?php endforeach; ?>

    <div class="box">
        <h2>Créer une alerte</h2>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="add">

            <div class="field">
                <label>Mots-clés</label>
                <input type="text" name="mots_cles" placeholder="ex : Rolex, MacBook, Hermès">
            </div>

            <div class="field">
                <label>Prix maximum</label>
                <input type="number" step="0.01" name="prix_max" placeholder="ex : 1000">
            </div>

            <div class="field">
                <label>Mode de vente souhaité</label>
                <select name="mode_vente_souhaite">
                    <option value="tous">Tous</option>
                    <option value="immediat">Immédiat</option>
                    <option value="negotiation">Négociation</option>
                    <option value="enchere">Enchère</option>
                </select>
            </div>

            <button class="btn btn-primary" type="submit">Ajouter l'alerte</button>
        </form>
    </div>

    <div class="box">
        <h2>Mes alertes enregistrées</h2>

        <?php if (empty($alertes)): ?>
            <p>Aucune alerte enregistrée.</p>
        <?php else: ?>
            <?php foreach ($alertes as $alerte): ?>
                <div style="border-bottom:1px solid #e8eeee; padding:12px 0;">
                    <p><strong>Alerte #<?= (int)$alerte['idAlerte'] ?></strong></p>
                    <p>Mots-clés : <?= h($alerte['mots_cles'] ?? 'aucun') ?></p>
                    <p>Prix max : <?= $alerte['prix_max'] !== null ? formatPrix((float)$alerte['prix_max']) : 'non défini' ?></p>
                    <p>Mode : <?= h($alerte['mode_vente_souhaite']) ?></p>
                    <p>Active : <?= (int)$alerte['active'] === 1 ? 'Oui' : 'Non' ?></p>

                    <form method="POST" action="" style="display:inline-block;">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="idAlerte" value="<?= (int)$alerte['idAlerte'] ?>">
                        <button class="btn btn-light" type="submit">Activer / désactiver</button>
                    </form>

                    <form method="POST" action="" style="display:inline-block;">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="idAlerte" value="<?= (int)$alerte['idAlerte'] ?>">
                        <button class="btn btn-danger" type="submit">Supprimer</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>