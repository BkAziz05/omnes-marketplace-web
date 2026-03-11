<?php
// ============================================================
// orders.php - Historique des commandes de l'acheteur
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

requireRole('acheteur');

$pdo = db();
$idAcheteur = currentUserId();

$flashes = getFlashes();

$stmt = $pdo->prepare("
    SELECT c.*, p.statut_paiement, p.type_paiement, p.numero_masque
    FROM commande c
    LEFT JOIN paiement p ON c.idCommande = p.idCommande
    WHERE c.idAcheteur = ?
    ORDER BY c.idCommande DESC
");
$stmt->execute([$idAcheteur]);
$commandes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes commandes - Omnes MarketPlace</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        body { background:#f8fafa; font-family:Arial, sans-serif; }
        .page { max-width:1100px; margin:30px auto; padding:20px; }
        .box { background:#fff; border:1px solid #d9e6e6; border-radius:10px; padding:20px; margin-bottom:20px; }
        .msg-ok { background:#e8f5e9; color:#2e7d32; padding:10px; border-radius:8px; margin-bottom:10px; }
    </style>
</head>
<body>
<div class="page">
    <h1>Mes commandes</h1>
    <p>
        <a href="<?= BASE_URL ?>/index.php">Accueil</a> |
        <a href="<?= BASE_URL ?>/cart.php">Panier</a>
    </p>

    <?php foreach ($flashes as $f): ?>
        <div class="msg-ok"><?= h($f['message']) ?></div>
    <?php endforeach; ?>

    <?php if (empty($commandes)): ?>
        <div class="box">
            <p>Vous n'avez encore aucune commande.</p>
        </div>
    <?php else: ?>
        <?php foreach ($commandes as $commande): ?>
            <div class="box">
                <h2>Commande #<?= (int)$commande['idCommande'] ?></h2>
                <p>Date : <?= formatDate($commande['date_commande']) ?></p>
                <p>Montant : <strong><?= formatPrix((float)$commande['montant_total']) ?></strong></p>
                <p>Mode : <?= h($commande['mode_validation']) ?></p>
                <p>Statut commande : <?= h($commande['status_commande']) ?></p>
                <p>Statut paiement : <?= h($commande['statut_paiement'] ?? 'non renseigné') ?></p>
                <p>Type paiement : <?= h($commande['type_paiement'] ?? 'non renseigné') ?></p>
                <p>Carte : <?= h($commande['numero_masque'] ?? 'non renseigné') ?></p>

                <h3>Adresse de livraison</h3>
                <pre style="white-space:pre-wrap;"><?= h($commande['adresse_livraison']) ?></pre>

                <h3>Articles</h3>
                <?php
                $stmt2 = $pdo->prepare("
                    SELECT lp.prix_snapshot, lp.mode_acquisition, a.nom
                    FROM ligne_panier lp
                    JOIN article a ON lp.idArticle = a.idArticle
                    WHERE lp.idPanier = ?
                ");
                $stmt2->execute([$commande['idPanier']]);
                $articles = $stmt2->fetchAll();
                ?>
                <?php foreach ($articles as $article): ?>
                    <p>- <?= h($article['nom']) ?> | <?= formatPrix((float)$article['prix_snapshot']) ?> | <?= h($article['mode_acquisition']) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>