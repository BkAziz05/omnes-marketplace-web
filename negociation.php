<?php
// ============================================================
// negotiation.php - Liste + démarrage + consultation des négociations
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
$idNegotiation = (int)($_GET['id'] ?? 0);

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

/**
 * Retourne le panier ouvert ou le crée.
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
 * Recalcule le total du panier.
 */
function recalcCartTotal(PDO $pdo, int $idPanier): void
{
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantite * prix_snapshot), 0) FROM ligne_panier WHERE idPanier = ?");
    $stmt->execute([$idPanier]);
    $total = (float)$stmt->fetchColumn();

    $stmt = $pdo->prepare("UPDATE panier SET sous_total = ? WHERE idPanier = ?");
    $stmt->execute([$total, $idPanier]);
}

// ------------------------------------------------------------
// TRAITEMENTS POST
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Token CSRF invalide.";
    } else {
        $action = $_POST['action'] ?? '';

        // Démarrer une négociation
        if ($action === 'start') {
            $idArticle = (int)($_POST['idArticle'] ?? 0);
            $montant   = (float)($_POST['montant'] ?? 0);

            $stmt = $pdo->prepare("
                SELECT a.*, v.idVendeur
                FROM article a
                JOIN vendeur v ON a.idVendeur = v.idVendeur
                WHERE a.idArticle = ?
                LIMIT 1
            ");
            $stmt->execute([$idArticle]);
            $article = $stmt->fetch();

            if (!$article) {
                $errors[] = "Article introuvable.";
            } elseif ($article['mode_vente'] !== 'negotiation') {
                $errors[] = "Cet article n'est pas négociable.";
            } elseif ($article['status'] !== 'disponible') {
                $errors[] = "Cet article n'est plus disponible.";
            } elseif ($montant <= 0) {
                $errors[] = "Le montant proposé est invalide.";
            } else {
                $stmt = $pdo->prepare("
                    SELECT id_negociation
                    FROM negociation
                    WHERE idArticle = ? AND idAcheteur = ? AND statut = 'en_cours'
                    LIMIT 1
                ");
                $stmt->execute([$idArticle, $idAcheteur]);
                $already = $stmt->fetchColumn();

                if ($already) {
                    $errors[] = "Une négociation est déjà en cours pour cet article.";
                } else {
                    $pdo->beginTransaction();

                    $stmt = $pdo->prepare("
                        INSERT INTO negociation (statut, nb_tours, idArticle, idAcheteur, idVendeur)
                        VALUES ('en_cours', 1, ?, ?, ?)
                    ");
                    $stmt->execute([$idArticle, $idAcheteur, $article['idVendeur']]);
                    $idNegotiationCreated = (int)$pdo->lastInsertId();

                    $stmt = $pdo->prepare("
                        INSERT INTO proposition (montant, emetteur, statut, id_negociation)
                        VALUES (?, 'acheteur', 'en_attente', ?)
                    ");
                    $stmt->execute([$montant, $idNegotiationCreated]);

                    createNotification(
                        $pdo,
                        "Nouvelle proposition de négociation pour l'article \"" . $article['nom'] . "\".",
                        'negociation',
                        null,
                        (int)$article['idVendeur']
                    );

                    $pdo->commit();
                    redirect(BASE_URL . '/negotiation.php?id=' . $idNegotiationCreated);
                }
            }
        }

        // Accepter la négociation (version simple pour la démo)
        elseif ($action === 'accept') {
            $idNegociationPost = (int)($_POST['id_negociation'] ?? 0);

            $stmt = $pdo->prepare("
                SELECT n.*, a.nom, a.prix_base
                FROM negociation n
                JOIN article a ON n.idArticle = a.idArticle
                WHERE n.id_negociation = ? AND n.idAcheteur = ?
                LIMIT 1
            ");
            $stmt->execute([$idNegociationPost, $idAcheteur]);
            $nego = $stmt->fetch();

            if (!$nego) {
                $errors[] = "Négociation introuvable.";
            } elseif ($nego['statut'] !== 'en_cours') {
                $errors[] = "Cette négociation n'est plus active.";
            } else {
                $stmt = $pdo->prepare("
                    SELECT montant
                    FROM proposition
                    WHERE id_negociation = ?
                    ORDER BY idProposition DESC
                    LIMIT 1
                ");
                $stmt->execute([$idNegociationPost]);
                $prixFinal = (float)$stmt->fetchColumn();

                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    UPDATE negociation
                    SET statut = 'acceptee', prix_final = ?
                    WHERE id_negociation = ?
                ");
                $stmt->execute([$prixFinal, $idNegociationPost]);

                $stmt = $pdo->prepare("UPDATE article SET status = 'en_cours' WHERE idArticle = ?");
                $stmt->execute([$nego['idArticle']]);

                $idPanier = getOrCreateOpenCart($pdo, $idAcheteur);

                $stmt = $pdo->prepare("
                    SELECT idLigne
                    FROM ligne_panier
                    WHERE idPanier = ? AND idArticle = ?
                    LIMIT 1
                ");
                $stmt->execute([$idPanier, $nego['idArticle']]);
                $already = $stmt->fetchColumn();

                if (!$already) {
                    $stmt = $pdo->prepare("
                        INSERT INTO ligne_panier (quantite, prix_snapshot, mode_acquisition, idPanier, idArticle)
                        VALUES (1, ?, 'negotiation', ?, ?)
                    ");
                    $stmt->execute([$prixFinal, $idPanier, $nego['idArticle']]);
                    recalcCartTotal($pdo, $idPanier);
                }

                createNotification(
                    $pdo,
                    "La négociation pour l'article \"" . $nego['nom'] . "\" a été acceptée.",
                    'negociation',
                    $idAcheteur,
                    null
                );

                createNotification(
                    $pdo,
                    "Votre négociation pour l'article \"" . $nego['nom'] . "\" a été acceptée côté acheteur.",
                    'negociation',
                    null,
                    (int)$nego['idVendeur']
                );

                $pdo->commit();

                setFlash('success', 'Négociation acceptée. L\'article a été ajouté au panier.');
                redirect(BASE_URL . '/cart.php');
            }
        }

        // Refuser la négociation
        elseif ($action === 'refuse') {
            $idNegociationPost = (int)($_POST['id_negociation'] ?? 0);

            $stmt = $pdo->prepare("
                UPDATE negociation
                SET statut = 'refusee'
                WHERE id_negociation = ? AND idAcheteur = ? AND statut = 'en_cours'
            ");
            $stmt->execute([$idNegociationPost, $idAcheteur]);

            $success = "La négociation a été refusée.";
        }
    }
}

// ------------------------------------------------------------
// LISTE DES NÉGOCIATIONS DE L'ACHETEUR
// ------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT n.*, a.nom AS article_nom
    FROM negociation n
    JOIN article a ON n.idArticle = a.idArticle
    WHERE n.idAcheteur = ?
    ORDER BY n.id_negociation DESC
");
$stmt->execute([$idAcheteur]);
$negociations = $stmt->fetchAll();

$negociationCourante = null;
$propositions = [];

if ($idNegotiation > 0) {
    $stmt = $pdo->prepare("
        SELECT n.*, a.nom AS article_nom
        FROM negociation n
        JOIN article a ON n.idArticle = a.idArticle
        WHERE n.id_negociation = ? AND n.idAcheteur = ?
        LIMIT 1
    ");
    $stmt->execute([$idNegotiation, $idAcheteur]);
    $negociationCourante = $stmt->fetch();

    if ($negociationCourante) {
        $stmt = $pdo->prepare("
            SELECT *
            FROM proposition
            WHERE id_negociation = ?
            ORDER BY idProposition ASC
        ");
        $stmt->execute([$idNegotiation]);
        $propositions = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Négociations - Omnes MarketPlace</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        body { background:#f8fafa; font-family:Arial, sans-serif; }
        .page { max-width:1100px; margin:30px auto; padding:20px; }
        .box { background:#fff; border:1px solid #d9e6e6; border-radius:10px; padding:20px; margin-bottom:20px; }
        .msg-ok { background:#e8f5e9; color:#2e7d32; padding:10px; border-radius:8px; margin-bottom:10px; }
        .msg-err { background:#fdecea; color:#c62828; padding:10px; border-radius:8px; margin-bottom:10px; }
        .btn { display:inline-block; padding:10px 16px; border:none; border-radius:8px; cursor:pointer; text-decoration:none; background:#0e8c8c; color:#fff; margin-right:10px; }
        .btn-light { background:#edf2f2; color:#1e3535; }
        .field { margin-bottom:12px; }
        .field label { display:block; margin-bottom:5px; font-weight:bold; }
        .field input { width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; }
    </style>
</head>
<body>
<div class="page">
    <h1>Mes négociations</h1>

    <?php if ($success): ?>
        <div class="msg-ok"><?= h($success) ?></div>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <div class="msg-err"><?= h($error) ?></div>
    <?php endforeach; ?>

    <div class="box">
        <h2>Voir toutes mes négociations</h2>
        <?php if (empty($negociations)): ?>
            <p>Aucune négociation pour le moment.</p>
        <?php else: ?>
            <?php foreach ($negociations as $n): ?>
                <p>
                    <a href="<?= BASE_URL ?>/negotiation.php?id=<?= (int)$n['id_negociation'] ?>">
                        #<?= (int)$n['id_negociation'] ?> - <?= h($n['article_nom']) ?>
                    </a>
                    | statut : <?= h($n['statut']) ?>
                    | tours : <?= (int)$n['nb_tours'] ?>
                </p>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if ($negociationCourante): ?>
        <div class="box">
            <h2>Détail de la négociation #<?= (int)$negociationCourante['id_negociation'] ?></h2>
            <p>Article : <?= h($negociationCourante['article_nom']) ?></p>
            <p>Statut : <?= h($negociationCourante['statut']) ?></p>
            <p>Nombre de tours : <?= (int)$negociationCourante['nb_tours'] ?> / <?= (int)NEGOCIATION_MAX_TOURS ?></p>

            <h3>Historique</h3>
            <?php foreach ($propositions as $prop): ?>
                <p>
                    - <?= formatDate($prop['date_proposition']) ?>
                    | <?= h($prop['emetteur']) ?>
                    | <?= formatPrix((float)$prop['montant']) ?>
                    | <?= h($prop['statut']) ?>
                </p>
            <?php endforeach; ?>

            <?php if ($negociationCourante['statut'] === 'en_cours' && (int)$negociationCourante['nb_tours'] < (int)NEGOCIATION_MAX_TOURS): ?>
                <p>
                    <a class="btn" href="<?= BASE_URL ?>/proposal.php?id_negociation=<?= (int)$negociationCourante['id_negociation'] ?>">
                        Faire une nouvelle proposition
                    </a>
                </p>
            <?php endif; ?>

            <?php if ($negociationCourante['statut'] === 'en_cours'): ?>
                <form method="POST" action="" style="margin-bottom:10px;">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="accept">
                    <input type="hidden" name="id_negociation" value="<?= (int)$negociationCourante['id_negociation'] ?>">
                    <button class="btn" type="submit">Accepter et mettre au panier</button>
                </form>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="refuse">
                    <input type="hidden" name="id_negociation" value="<?= (int)$negociationCourante['id_negociation'] ?>">
                    <button class="btn btn-light" type="submit">Refuser</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>