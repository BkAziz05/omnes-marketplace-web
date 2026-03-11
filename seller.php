<?php
// ============================================================
// seller.php
// Espace vendeur simple
// - accès réservé au vendeur connecté
// - affiche ses informations
// - affiche ses articles
// - liens vers ajout / modification / suppression
// ============================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/ui.php';

requireLogin();

$pdo = db();
$errors = [];
$success = '';

// ------------------------------------------------------------
// 1) Vérifier que l'utilisateur connecté est bien vendeur
// ------------------------------------------------------------
if (currentUserRole() !== 'vendeur') {
    setFlash('error', 'Accès refusé.');
    redirect(BASE_URL . '/login.php');
}

$idVendeur = currentUserId();

$stmt = $pdo->prepare("SELECT * FROM vendeur WHERE idVendeur = ? LIMIT 1");
$stmt->execute([$idVendeur]);
$vendeur = $stmt->fetch();

if (!$vendeur) {
    session_unset();
    session_destroy();
    redirect(BASE_URL . '/login.php');
}

// ------------------------------------------------------------
// 2) Messages flash
// ------------------------------------------------------------
$flashes = getFlashes();
foreach ($flashes as $flash) {
    if (($flash['type'] ?? '') === 'success') {
        $success = $flash['message'] ?? '';
    } else {
        $errors[] = $flash['message'] ?? 'Erreur.';
    }
}

// ------------------------------------------------------------
// 3) Statistiques simples du vendeur
// ------------------------------------------------------------
$stmt = $pdo->prepare("SELECT COUNT(*) FROM article WHERE idVendeur = ?");
$stmt->execute([$idVendeur]);
$nbArticles = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM article WHERE idVendeur = ? AND status = 'disponible'");
$stmt->execute([$idVendeur]);
$nbDisponibles = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM article WHERE idVendeur = ? AND status = 'vendu'");
$stmt->execute([$idVendeur]);
$nbVendus = (int)$stmt->fetchColumn();

// ------------------------------------------------------------
// 4) Récupération des articles du vendeur
// ------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT
        a.*,
        c.libelle AS categorie_libelle,
        (
            SELECT url_photo
            FROM photo_article pa
            WHERE pa.idArticle = a.idArticle
            ORDER BY ordre ASC
            LIMIT 1
        ) AS photo
    FROM article a
    LEFT JOIN categorie c ON a.idCategorie = c.idCategorie
    WHERE a.idVendeur = ?
    ORDER BY a.idArticle DESC
");
$stmt->execute([$idVendeur]);
$articles = $stmt->fetchAll();

renderHead('Espace vendeur - Omnes MarketPlace');
renderNavbar();
?>

<div class="page-hero">
    <div class="container">
        <h1 class="mt-2 mb-1">Espace vendeur</h1>
        <p style="color:rgba(255,255,255,.8);margin:0;">
            Gère tes articles et ton activité vendeur
        </p>
    </div>
</div>

<div class="container py-5">

    <?php if ($success): ?>
        <div class="alert alert-success"><?= h($success) ?></div>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endforeach; ?>

    <!-- Infos vendeur -->
    <div class="card card-omnes p-4 mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="font-display mb-2">
                    <?= h(($vendeur['prenom'] ?? '') . ' ' . ($vendeur['nom'] ?? '')) ?>
                </h2>
                <p class="mb-1"><strong>Pseudo :</strong> <?= h($vendeur['pseudo'] ?? '') ?></p>
                <p class="mb-0"><strong>Email :</strong> <?= h($vendeur['email'] ?? '') ?></p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="<?= BASE_URL ?>/add-article.php" class="btn btn-primary-omnes">
                    Ajouter un article
                </a>
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card card-omnes p-4">
                <h3 class="font-display mb-2"><?= $nbArticles ?></h3>
                <p class="mb-0 text-muted">Articles au total</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-omnes p-4">
                <h3 class="font-display mb-2"><?= $nbDisponibles ?></h3>
                <p class="mb-0 text-muted">Articles disponibles</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-omnes p-4">
                <h3 class="font-display mb-2"><?= $nbVendus ?></h3>
                <p class="mb-0 text-muted">Articles vendus</p>
            </div>
        </div>
    </div>

    <!-- Liste des articles -->
    <div class="card card-omnes p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="font-display mb-0">Mes articles</h2>
            <a href="<?= BASE_URL ?>/add-article.php" class="btn btn-outline-omnes">
                Nouvel article
            </a>
        </div>

        <?php if (empty($articles)): ?>
            <p class="mb-0">Tu n’as encore publié aucun article.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Nom</th>
                            <th>Catégorie</th>
                            <th>Mode</th>
                            <th>Prix</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($articles as $article): ?>
                            <?php $imageUrl = articleImageUrl($article['photo'] ?? '', $article['nom'] ?? ''); ?>
                            <tr>
                                <td>
                                    <?php if ($imageUrl !== ''): ?>
                                        <img src="<?= h($imageUrl) ?>"
                                             alt="<?= h($article['nom']) ?>"
                                             style="width:70px;height:70px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;">
                                    <?php else: ?>
                                        <div style="width:70px;height:70px;border-radius:8px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;">
                                            <i class="bi bi-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= h($article['nom']) ?></strong>
                                </td>
                                <td><?= h($article['categorie_libelle'] ?? 'Non renseignée') ?></td>
                                <td><?= renderModeBadge($article['mode_vente']) ?></td>
                                <td><?= formatPrix((float)$article['prix_base']) ?></td>
                                <td><?= h($article['status']) ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="<?= BASE_URL ?>/article-detail.php?id=<?= (int)$article['idArticle'] ?>" class="btn btn-sm btn-outline-omnes">
                                            Voir
                                        </a>
                                        <a href="<?= BASE_URL ?>/edit-article.php?id=<?= (int)$article['idArticle'] ?>" class="btn btn-sm btn-outline-omnes">
                                            Modifier
                                        </a>
                                        <a href="<?= BASE_URL ?>/delete-article.php?id=<?= (int)$article['idArticle'] ?>"
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Supprimer cet article ?');">
                                            Supprimer
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php renderFooter(); ?>