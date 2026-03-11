<?php
// ============================================================
// article-detail.php
// Page détail d'un article
// - affiche les infos de l'article
// - affiche les photos
// - permet l'achat immédiat
// - permet de démarrer une négociation
// - permet d'aller vers l'enchère
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/ui.php';

$pdo = db();

// ------------------------------------------------------------
// 1) Récupération de l'id de l'article dans l'URL
// Exemple : article-detail.php?id=3
// ------------------------------------------------------------
$idArticle = (int)($_GET['id'] ?? 0);

if ($idArticle <= 0) {
    setFlash('error', 'Article invalide.');
    redirect(BASE_URL . '/articles.php');
}

// ------------------------------------------------------------
// 2) Récupération de l'article
// On joint aussi la catégorie et le vendeur
// ------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT 
        a.*,
        c.libelle AS categorie_libelle,
        c.type_marchandise,
        v.idVendeur,
        v.pseudo AS vendeur_pseudo,
        v.nom AS vendeur_nom,
        v.prenom AS vendeur_prenom
    FROM article a
    JOIN categorie c ON a.idCategorie = c.idCategorie
    JOIN vendeur v ON a.idVendeur = v.idVendeur
    WHERE a.idArticle = ?
    LIMIT 1
");
$stmt->execute([$idArticle]);
$article = $stmt->fetch();

// Si l'article n'existe pas
if (!$article) {
    setFlash('error', 'Article introuvable.');
    redirect(BASE_URL . '/articles.php');
}

// ------------------------------------------------------------
// 3) Récupération des photos de l'article
// ------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT *
    FROM photo_article
    WHERE idArticle = ?
    ORDER BY ordre ASC, idPhoto ASC
");
$stmt->execute([$idArticle]);
$photos = $stmt->fetchAll();

// ------------------------------------------------------------
// 4) On prépare les URLs d'images à afficher
// Si les chemins BDD sont mauvais, articleImageUrl()
// essaie de trouver une image de secours dans assets/images
// ------------------------------------------------------------
$images = [];

if (!empty($photos)) {
    foreach ($photos as $photo) {
        $url = articleImageUrl($photo['url_photo'] ?? '', $article['nom'] ?? '');
        if ($url !== '') {
            $images[] = $url;
        }
    }
}

// Si aucune image n'a été trouvée, on essaie quand même avec le nom
if (empty($images)) {
    $fallback = articleImageUrl('', $article['nom'] ?? '');
    if ($fallback !== '') {
        $images[] = $fallback;
    }
}

// ------------------------------------------------------------
// 5) Si l'article est en enchère, on récupère la meilleure offre
// ------------------------------------------------------------
$meilleureOffre = null;

if (($article['mode_vente'] ?? '') === 'enchere') {
    $stmt = $pdo->prepare("
        SELECT MAX(montant_courant)
        FROM offre_enchere
        WHERE idArticle = ?
    ");
    $stmt->execute([$idArticle]);
    $meilleureOffre = $stmt->fetchColumn();
}

// ------------------------------------------------------------
// 6) Vérification simple pour savoir si l'enchère est finie
// ------------------------------------------------------------
$enchereTerminee = false;

if (
    ($article['mode_vente'] ?? '') === 'enchere' &&
    !empty($article['date_fin_enchere']) &&
    strtotime($article['date_fin_enchere']) < time()
) {
    $enchereTerminee = true;
}

// ------------------------------------------------------------
// 7) Affichage HTML
// ------------------------------------------------------------
renderHead(($article['nom'] ?? 'Article') . ' - Omnes MarketPlace');
renderNavbar('articles');
?>

<div class="page-hero">
    <div class="container">
        <h1 class="mt-2 mb-1"><?= h($article['nom']) ?></h1>
        <p style="color:rgba(255,255,255,.8);margin:0;">
            <?= h($article['categorie_libelle']) ?> - <?= h($article['type_marchandise']) ?>
        </p>
    </div>
</div>

<div class="container py-5">

    <!-- Bouton retour -->
    <div class="mb-4">
        <a href="<?= BASE_URL ?>/articles.php" class="btn btn-outline-omnes">← Retour aux articles</a>
    </div>

    <div class="row g-4">
        <!-- =====================================================
             COLONNE GAUCHE : PHOTOS
        ====================================================== -->
        <div class="col-lg-6">
            <div class="card card-omnes p-3">

                <?php if (!empty($images)): ?>
                    <!-- Image principale -->
                    <img
                        src="<?= h($images[0]) ?>"
                        alt="<?= h($article['nom']) ?>"
                        style="width:100%;height:420px;object-fit:cover;border-radius:12px;"
                    >

                    <!-- Miniatures si plusieurs images -->
                    <?php if (count($images) > 1): ?>
                        <div class="row mt-3">
                            <?php foreach ($images as $index => $img): ?>
                                <div class="col-4 mb-3">
                                    <img
                                        src="<?= h($img) ?>"
                                        alt="Photo <?= $index + 1 ?>"
                                        style="width:100%;height:110px;object-fit:cover;border-radius:10px;border:1px solid #e5e7eb;"
                                    >
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- Si aucune image -->
                    <div class="img-placeholder" style="height:420px;border-radius:12px;">
                        <i class="bi bi-image"></i>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- =====================================================
             COLONNE DROITE : INFORMATIONS
        ====================================================== -->
        <div class="col-lg-6">
            <div class="card card-omnes p-4">

                <!-- Badge du mode de vente -->
                <div class="mb-3">
                    <?= renderModeBadge($article['mode_vente']) ?>
                </div>

                <!-- Nom de l'article -->
                <h2 class="font-display mb-3"><?= h($article['nom']) ?></h2>

                <!-- Vendeur -->
                <p class="text-muted mb-2">
                    Vendeur :
                    <strong><?= h($article['vendeur_pseudo'] ?: ($article['vendeur_prenom'] . ' ' . $article['vendeur_nom'])) ?></strong>
                </p>

                <!-- Catégorie -->
                <p class="text-muted mb-3">
                    Catégorie : <?= h($article['categorie_libelle']) ?>
                </p>

                <!-- Prix -->
                <div class="price-display price-lg mb-3">
                    <?= formatPrix((float)$article['prix_base']) ?>
                </div>

                <!-- Description principale -->
                <div class="mb-3">
                    <h5 class="font-display mb-2">Description</h5>
                    <p class="mb-0">
                        <?= nl2br(h($article['description_qualite'] ?? 'Aucune description.')) ?>
                    </p>
                </div>

                <!-- Défauts éventuels -->
                <?php if (!empty($article['description_defaut'])): ?>
                    <div class="alert alert-warning">
                        <strong>Défauts signalés :</strong><br>
                        <?= nl2br(h($article['description_defaut'])) ?>
                    </div>
                <?php endif; ?>

                <!-- Vidéo éventuelle -->
                <?php if (!empty($article['video_url'])): ?>
                    <p class="mb-3">
                        <a href="<?= h($article['video_url']) ?>" target="_blank">Voir la vidéo de présentation</a>
                    </p>
                <?php endif; ?>

                <!-- Infos en plus -->
                <hr>

                <p class="mb-2">
                    <strong>Statut :</strong> <?= h($article['status']) ?>
                </p>

                <p class="mb-3">
                    <strong>Date de publication :</strong>
                    <?= !empty($article['date_publication']) ? formatDate($article['date_publication']) : 'Non renseignée' ?>
                </p>

                <!-- =================================================
                     PARTIE ACTIONS SELON LE MODE DE VENTE
                ================================================== -->

                <?php if (($article['status'] ?? '') !== 'disponible' && ($article['status'] ?? '') !== 'en_cours'): ?>
                    <div class="alert alert-danger mb-0">
                        Cet article n'est plus disponible.
                    </div>

                <?php elseif (!isLoggedIn()): ?>
                    <div class="alert alert-info mb-0">
                        <a href="<?= BASE_URL ?>/login.php">Connecte-toi</a> pour acheter, négocier ou enchérir.
                    </div>

                <?php elseif (!hasRole('acheteur')): ?>
                    <div class="alert alert-info mb-0">
                        Seul un acheteur peut effectuer cette action.
                    </div>

                <?php else: ?>

                    <!-- ---------------------------------------------
                         CAS 1 : ACHAT IMMÉDIAT
                    ---------------------------------------------- -->
                    <?php if (($article['mode_vente'] ?? '') === 'immediat'): ?>
                        <div class="mt-3">
                            <h5 class="font-display mb-3">Achat immédiat</h5>

                            <form method="POST" action="<?= BASE_URL ?>/cart.php">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="idArticle" value="<?= (int)$article['idArticle'] ?>">
                                <input type="hidden" name="mode_acquisition" value="immediat">

                                <button type="submit" class="btn btn-primary-omnes">
                                    Ajouter au panier
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <!-- ---------------------------------------------
                         CAS 2 : NÉGOCIATION
                    ---------------------------------------------- -->
                    <?php if (($article['mode_vente'] ?? '') === 'negotiation'): ?>
                        <div class="mt-3">
                            <h5 class="font-display mb-3">Démarrer une négociation</h5>

                            <form method="POST" action="<?= BASE_URL ?>/negotiation.php">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="action" value="start">
                                <input type="hidden" name="idArticle" value="<?= (int)$article['idArticle'] ?>">

                                <div class="mb-3">
                                    <label class="form-label-omnes">Ton prix proposé</label>
                                    <input
                                        type="number"
                                        name="montant"
                                        step="0.01"
                                        min="1"
                                        class="form-control-omnes"
                                        placeholder="Ex : 250"
                                        required
                                    >
                                </div>

                                <button type="submit" class="btn btn-primary-omnes">
                                    Commencer la négociation
                                </button>
                            </form>

                            <p class="text-muted mt-2 mb-0">
                                Maximum : <?= (int)NEGOCIATION_MAX_TOURS ?> tours de négociation.
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- ---------------------------------------------
                         CAS 3 : ENCHÈRE
                    ---------------------------------------------- -->
                    <?php if (($article['mode_vente'] ?? '') === 'enchere'): ?>
                        <div class="mt-3">
                            <h5 class="font-display mb-3">Informations sur l'enchère</h5>

                            <p class="mb-2">
                                <strong>Date de début :</strong>
                                <?= !empty($article['date_debut_enchere']) ? formatDate($article['date_debut_enchere']) : 'Non renseignée' ?>
                            </p>

                            <p class="mb-2">
                                <strong>Date de fin :</strong>
                                <?= !empty($article['date_fin_enchere']) ? formatDate($article['date_fin_enchere']) : 'Non renseignée' ?>
                            </p>

                            <p class="mb-3">
                                <strong>Meilleure offre actuelle :</strong>
                                <?= $meilleureOffre ? formatPrix((float)$meilleureOffre) : 'Aucune offre pour le moment' ?>
                            </p>

                            <?php if ($enchereTerminee): ?>
                                <div class="alert alert-warning">
                                    Cette enchère est terminée.
                                </div>
                            <?php endif; ?>

                            <a href="<?= BASE_URL ?>/auction.php?id=<?= (int)$article['idArticle'] ?>" class="btn btn-primary-omnes">
                                Voir l'enchère
                            </a>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>