<?php
// ============================================================
// auction.php
// Page détail d'une enchère
// ============================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/ui.php';

$pdo = db();
$idArticle = (int)($_GET['id'] ?? 0);

if ($idArticle <= 0) {
    setFlash('error', 'Article invalide.');
    redirect(BASE_URL . '/articles.php');
}

$stmt = $pdo->prepare("
    SELECT 
        a.*,
        c.libelle AS categorie_libelle,
        v.nom AS vendeur_nom,
        v.prenom AS vendeur_prenom,
        (
            SELECT url_photo
            FROM photo_article pa
            WHERE pa.idArticle = a.idArticle
            ORDER BY ordre ASC
            LIMIT 1
        ) AS photo
    FROM article a
    JOIN categorie c ON a.idCategorie = c.idCategorie
    JOIN vendeur v ON a.idVendeur = v.idVendeur
    WHERE a.idArticle = ?
    LIMIT 1
");
$stmt->execute([$idArticle]);
$article = $stmt->fetch();

if (!$article) {
    setFlash('error', 'Article introuvable.');
    redirect(BASE_URL . '/articles.php');
}

$stmt = $pdo->prepare("
    SELECT oe.*, ach.nom, ach.prenom
    FROM offre_enchere oe
    JOIN acheteur ach ON oe.idAcheteur = ach.idAcheteur
    WHERE oe.idArticle = ?
    ORDER BY oe.montant_courant DESC, oe.idOffre DESC
");
$stmt->execute([$idArticle]);
$offres = $stmt->fetchAll();

$meilleureOffre = null;
if (!empty($offres)) {
    $meilleureOffre = $offres[0];
}

$imageUrl = articleImageUrl($article['photo'] ?? '', $article['nom'] ?? '');

$enchereTerminee = false;
if (!empty($article['date_fin_enchere']) && strtotime($article['date_fin_enchere']) < time()) {
    $enchereTerminee = true;
}

renderHead('Enchère - Omnes MarketPlace');
renderNavbar('articles');
?>

<div class="page-hero">
    <div class="container">
        <h1 class="mt-2 mb-1">Enchère</h1>
        <p style="color:rgba(255,255,255,.8);margin:0;"><?= h($article['nom']) ?></p>
    </div>
</div>

<div class="container py-5">
    <div class="mb-4">
        <a href="<?= BASE_URL ?>/article-detail.php?id=<?= (int)$article['idArticle'] ?>" class="btn btn-outline-omnes">← Retour à l'article</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card card-omnes p-3">
                <?php if ($imageUrl !== ''): ?>
                    <img src="<?= h($imageUrl) ?>" alt="<?= h($article['nom']) ?>" style="width:100%;height:380px;object-fit:cover;border-radius:12px;">
                <?php else: ?>
                    <div class="img-placeholder" style="height:380px;border-radius:12px;">
                        <i class="bi bi-image"></i>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card card-omnes p-4">
                <div class="mb-3"><?= renderModeBadge('enchere') ?></div>

                <h2 class="font-display mb-3"><?= h($article['nom']) ?></h2>
                <p>Catégorie : <?= h($article['categorie_libelle']) ?></p>
                <p>Vendeur : <?= h(($article['vendeur_prenom'] ?? '') . ' ' . ($article['vendeur_nom'] ?? '')) ?></p>
                <p>Prix de départ : <strong><?= formatPrix((float)$article['prix_base']) ?></strong></p>
                <p>Fin : <strong><?= !empty($article['date_fin_enchere']) ? formatDate($article['date_fin_enchere']) : 'Non renseignée' ?></strong></p>

                <?php if ($meilleureOffre): ?>
                    <p>Meilleure offre actuelle :
                        <strong><?= formatPrix((float)$meilleureOffre['montant_courant']) ?></strong>
                    </p>
                <?php else: ?>
                    <p>Aucune offre pour le moment.</p>
                <?php endif; ?>

                <?php if ($enchereTerminee): ?>
                    <div class="alert alert-warning">Cette enchère est terminée.</div>
                <?php elseif (isLoggedIn() && hasRole('acheteur')): ?>
                    <a href="<?= BASE_URL ?>/bid.php?id=<?= (int)$article['idArticle'] ?>" class="btn btn-primary-omnes">Placer une offre</a>
                <?php else: ?>
                    <div class="alert alert-info">
                        <a href="<?= BASE_URL ?>/login.php">Connecte-toi</a> pour enchérir.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card card-omnes p-4 mt-4">
        <h3 class="font-display mb-3">Historique des offres</h3>

        <?php if (empty($offres)): ?>
            <p class="mb-0">Aucune offre enregistrée.</p>
        <?php else: ?>
            <?php foreach ($offres as $offre): ?>
                <div style="border-bottom:1px solid #e5e7eb;padding:12px 0;">
                    <p class="mb-1"><strong><?= h(($offre['prenom'] ?? '') . ' ' . ($offre['nom'] ?? '')) ?></strong></p>
                    <p class="mb-1">Offre max : <?= formatPrix((float)$offre['montant_max']) ?></p>
                    <p class="mb-1">Offre courante : <?= formatPrix((float)$offre['montant_courant']) ?></p>
                    <p class="mb-0 text-muted"><?= !empty($offre['date_offre']) ? formatDate($offre['date_offre']) : 'Date non renseignée' ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php renderFooter(); ?>