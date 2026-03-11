<?php
// ============================================================
// cart.php - Gestion du panier
// Version simple et commentée
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';


requireRole('acheteur');

$pdo = db();
$idAcheteur = currentUserId();

/**
 * Retourne l'ID du panier ouvert de l'acheteur.
 * Si aucun panier ouvert n'existe, on le crée.
 */
function getOrCreateOpenCart(PDO $pdo, int $idAcheteur): int
{
    $stmt = $pdo->prepare("SELECT idPanier FROM panier WHERE idAcheteur = ? AND statut = 'ouvert' ORDER BY idPanier DESC LIMIT 1");
    $stmt->execute([$idAcheteur]);
    $idPanier = $stmt->fetchColumn();

    if ($idPanier) {
        return (int)$idPanier;
    }

    $stmt = $pdo->prepare("INSERT INTO panier (statut, sous_total, idAcheteur) VALUES ('ouvert', 0, ?)");
    $stmt->execute([$idAcheteur]);

    return (int)$pdo->lastInsertId();
}

/**
 * Recalcule le sous-total du panier.
 */
function recalcCartTotal(PDO $pdo, int $idPanier): void
{
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(quantite * prix_snapshot), 0)
        FROM ligne_panier
        WHERE idPanier = ?
    ");
    $stmt->execute([$idPanier]);
    $total = (float)$stmt->fetchColumn();

    $stmt = $pdo->prepare("UPDATE panier SET sous_total = ? WHERE idPanier = ?");
    $stmt->execute([$total, $idPanier]);
}

$idPanier = getOrCreateOpenCart($pdo, $idAcheteur);
$errors = [];
$success = '';

// ------------------------------------------------------------
// TRAITEMENTS POST
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Token CSRF invalide.";
    } else {
        $action = $_POST['action'] ?? '';

        // Ajouter un article au panier
        if ($action === 'add') {
            $idArticle = (int)($_POST['idArticle'] ?? 0);
            $modeAcquisition = clean($_POST['mode_acquisition'] ?? 'immediat');

            $stmt = $pdo->prepare("
                SELECT idArticle, nom, prix_base, mode_vente, status
                FROM article
                WHERE idArticle = ?
                LIMIT 1
            ");
            $stmt->execute([$idArticle]);
            $article = $stmt->fetch();

            if (!$article) {
                $errors[] = "Article introuvable.";
            } elseif ($article['status'] !== 'disponible') {
                $errors[] = "Cet article n'est plus disponible.";
            } else {
                // Vérifie si l'article est déjà dans le panier
                $stmt = $pdo->prepare("
                    SELECT idLigne
                    FROM ligne_panier
                    WHERE idPanier = ? AND idArticle = ?
                    LIMIT 1
                ");
                $stmt->execute([$idPanier, $idArticle]);
                $already = $stmt->fetchColumn();

                if ($already) {
                    $errors[] = "Cet article est déjà dans votre panier.";
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO ligne_panier (quantite, prix_snapshot, mode_acquisition, idPanier, idArticle)
                        VALUES (1, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $article['prix_base'],
                        $modeAcquisition ?: $article['mode_vente'],
                        $idPanier,
                        $idArticle
                    ]);

                    recalcCartTotal($pdo, $idPanier);
                    $success = "L'article a été ajouté au panier.";
                }
            }
        }

        // Supprimer une ligne du panier
        elseif ($action === 'remove') {
            $idLigne = (int)($_POST['idLigne'] ?? 0);

            $stmt = $pdo->prepare("DELETE FROM ligne_panier WHERE idLigne = ? AND idPanier = ?");
            $stmt->execute([$idLigne, $idPanier]);

            recalcCartTotal($pdo, $idPanier);
            $success = "L'article a été retiré du panier.";
        }

        // Vider le panier
        elseif ($action === 'clear') {
            $stmt = $pdo->prepare("DELETE FROM ligne_panier WHERE idPanier = ?");
            $stmt->execute([$idPanier]);

            recalcCartTotal($pdo, $idPanier);
            $success = "Le panier a été vidé.";
        }
    }
}

// ------------------------------------------------------------
// RÉCUPÉRATION DES LIGNES DU PANIER
// ------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT 
        lp.idLigne,
        lp.quantite,
        lp.prix_snapshot,
        lp.mode_acquisition,
        a.idArticle,
        a.nom,
        a.status,
        v.nom AS vendeur_nom,
        v.prenom AS vendeur_prenom,
        (
            SELECT url_photo
            FROM photo_article pa
            WHERE pa.idArticle = a.idArticle
            ORDER BY ordre ASC
            LIMIT 1
        ) AS photo
    FROM ligne_panier lp
    JOIN article a ON lp.idArticle = a.idArticle
    JOIN vendeur v ON a.idVendeur = v.idVendeur
    WHERE lp.idPanier = ?
    ORDER BY lp.idLigne DESC
");
$stmt->execute([$idPanier]);
$lignes = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT sous_total FROM panier WHERE idPanier = ?");
$stmt->execute([$idPanier]);
$sousTotal = (float)$stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon panier - Omnes MarketPlace</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        body { background:#f8fafa; font-family:Arial, sans-serif; }
        .page { max-width:1100px; margin:30px auto; padding:20px; }
        .top-links a { margin-right:12px; }
        .box { background:#fff; border:1px solid #d9e6e6; border-radius:10px; padding:18px; margin-bottom:18px; }
        .article-row { border-bottom:1px solid #e8eeee; padding:15px 0; }
        .article-row:last-child { border-bottom:none; }
        .photo { width:90px; height:90px; object-fit:cover; border:1px solid #ddd; border-radius:8px; display:block; margin-bottom:10px; }
        .btn { display:inline-block; padding:10px 16px; border:none; border-radius:8px; cursor:pointer; text-decoration:none; }
        .btn-primary { background:#0e8c8c; color:#fff; }
        .btn-danger { background:#c62828; color:#fff; }
        .btn-light { background:#edf2f2; color:#1e3535; }
        .msg-ok { background:#e8f5e9; color:#2e7d32; padding:10px; border-radius:8px; margin-bottom:15px; }
        .msg-err { background:#fdecea; color:#c62828; padding:10px; border-radius:8px; margin-bottom:10px; }
        .badge { display:inline-block; padding:4px 8px; border-radius:20px; font-size:12px; background:#fff3e0; color:#e65100; }
        .summary { background:#fff; border:1px solid #d9e6e6; border-radius:10px; padding:18px; }
    </style>
</head>
<body>
<div class="page">
    <h1>Mon panier</h1>

    <p class="top-links">
        <a href="<?= BASE_URL ?>/index.php">Accueil</a>
        <a href="<?= BASE_URL ?>/articles.php">Articles</a>
        <a href="<?= BASE_URL ?>/notifications.php">Notifications</a>
        <a href="<?= BASE_URL ?>/account.php">Mon compte</a>
    </p>

    <?php if ($success): ?>
        <div class="msg-ok"><?= h($success) ?></div>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <div class="msg-err"><?= h($error) ?></div>
    <?php endforeach; ?>

    <?php if (empty($lignes)): ?>
        <div class="box">
            <p>Votre panier est vide.</p>
            <p><a class="btn btn-primary" href="<?= BASE_URL ?>/articles.php">Voir les articles</a></p>
        </div>
    <?php else: ?>
        <div class="box">
            <h2>Articles du panier</h2>

            <?php foreach ($lignes as $ligne): ?>
                <div class="article-row">
                    <?php
                    $imageUrl = articleImageUrl($ligne['photo'] ?? '', $ligne['nom'] ?? '');
                    ?>

                    <?php if ($imageUrl !== ''): ?>
                        <img class="photo" src="<?= h($imageUrl) ?>" alt="<?= h($ligne['nom']) ?>">
                    <?php endif; ?>

                    <h3><?= h($ligne['nom']) ?></h3>
                    <p>Vendeur : <?= h($ligne['vendeur_prenom'] . ' ' . $ligne['vendeur_nom']) ?></p>
                    <p>Mode : <span class="badge"><?= h($ligne['mode_acquisition']) ?></span></p>
                    <p>Prix : <strong><?= formatPrix((float)$ligne['prix_snapshot']) ?></strong></p>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="idLigne" value="<?= (int)$ligne['idLigne'] ?>">
                        <button class="btn btn-danger" type="submit">Retirer</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="summary">
            <h2>Récapitulatif</h2>
            <p>Sous-total : <strong><?= formatPrix($sousTotal) ?></strong></p>

            <p>
                <a class="btn btn-primary" href="<?= BASE_URL ?>/checkout.php">Passer à la commande</a>
            </p>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="clear">
                <button class="btn btn-light" type="submit">Vider le panier</button>
            </form>
        </div>
    <?php endif; ?>
</div>
</body>
</html>