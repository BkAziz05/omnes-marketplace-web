<?php
// ============================================================
// articles.php - Catalogue des articles
// Version prête à coller
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/ui.php';

$pdo = db();

// ------------------------------------------------------------
// Récupération des filtres
// ------------------------------------------------------------
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 9;
$search    = clean($_GET['q'] ?? '');
$categorie = (int)($_GET['categorie'] ?? 0);
$modeVente = clean($_GET['mode'] ?? '');
$prixMax   = (float)($_GET['prix_max'] ?? 0);
$typeMarch = clean($_GET['type'] ?? '');

// ------------------------------------------------------------
// Construction dynamique du WHERE
// ------------------------------------------------------------
$where = ["a.status = 'disponible'"];
$params = [];

if ($search !== '') {
    $where[] = "(a.nom LIKE ? OR a.description_qualite LIKE ? OR a.description_defaut LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($categorie > 0) {
    $where[] = "a.idCategorie = ?";
    $params[] = $categorie;
}

if (in_array($modeVente, ['immediat', 'negotiation', 'enchere'])) {
    $where[] = "a.mode_vente = ?";
    $params[] = $modeVente;
}

if ($prixMax > 0) {
    $where[] = "a.prix_base <= ?";
    $params[] = $prixMax;
}

if (in_array($typeMarch, ['rare', 'haute_gamme', 'regulier'])) {
    $where[] = "c.type_marchandise = ?";
    $params[] = $typeMarch;
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

// ------------------------------------------------------------
// Nombre total d'articles
// ------------------------------------------------------------
$countSql = "
    SELECT COUNT(*)
    FROM article a
    JOIN categorie c ON a.idCategorie = c.idCategorie
    $whereClause
";

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

// Pagination
$pagination = paginate($total, $perPage, $page);

// ------------------------------------------------------------
// Récupération des articles
// ------------------------------------------------------------
$sql = "
    SELECT 
        a.*,
        c.libelle AS categorie_libelle,
        c.type_marchandise,
        (
            SELECT url_photo
            FROM photo_article pa
            WHERE pa.idArticle = a.idArticle
            ORDER BY ordre ASC
            LIMIT 1
        ) AS photo
    FROM article a
    JOIN categorie c ON a.idCategorie = c.idCategorie
    $whereClause
    ORDER BY a.date_publication DESC
    LIMIT ? OFFSET ?
";

$paramsArticles = $params;
$paramsArticles[] = $perPage;
$paramsArticles[] = $pagination['offset'];

$stmt = $pdo->prepare($sql);
$stmt->execute($paramsArticles);
$articles = $stmt->fetchAll();

// ------------------------------------------------------------
// Récupération des catégories pour le filtre
// ------------------------------------------------------------
$categories = $pdo->query("
    SELECT idCategorie, libelle
    FROM categorie
    ORDER BY libelle ASC
")->fetchAll();

renderHead('Tout parcourir - Omnes MarketPlace');
renderNavbar('articles');
?>

<div class="page-hero">
    <div class="container">
        <h1 class="mt-2 mb-1">Tout Parcourir</h1>
        <p style="color:rgba(255,255,255,.8);margin:0;">
            <?= $total ?> article(s) disponible(s)
        </p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">

        <!-- Colonne filtres -->
        <div class="col-lg-3">
            <div class="filter-sidebar">
                <h5 style="font-size:1.05rem;margin-bottom:20px;">
                    <i class="bi bi-funnel me-2" style="color:var(--primary);"></i>
                    Filtres
                </h5>

                <form method="GET" action="">
                    <div class="mb-3">
                        <label class="form-label-omnes">Recherche</label>
                        <input
                            type="text"
                            name="q"
                            value="<?= h($search) ?>"
                            class="form-control-omnes"
                            placeholder="Nom ou description"
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label-omnes">Catégorie</label>
                        <select name="categorie" class="form-select-omnes">
                            <option value="0">Toutes les catégories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int)$cat['idCategorie'] ?>" <?= $categorie === (int)$cat['idCategorie'] ? 'selected' : '' ?>>
                                    <?= h($cat['libelle']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-omnes">Mode de vente</label>
                        <select name="mode" class="form-select-omnes">
                            <option value="">Tous</option>
                            <option value="immediat" <?= $modeVente === 'immediat' ? 'selected' : '' ?>>Immédiat</option>
                            <option value="negotiation" <?= $modeVente === 'negotiation' ? 'selected' : '' ?>>Négociation</option>
                            <option value="enchere" <?= $modeVente === 'enchere' ? 'selected' : '' ?>>Enchère</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-omnes">Type d'article</label>
                        <select name="type" class="form-select-omnes">
                            <option value="">Tous</option>
                            <option value="rare" <?= $typeMarch === 'rare' ? 'selected' : '' ?>>Rare</option>
                            <option value="haute_gamme" <?= $typeMarch === 'haute_gamme' ? 'selected' : '' ?>>Haute gamme</option>
                            <option value="regulier" <?= $typeMarch === 'regulier' ? 'selected' : '' ?>>Régulier</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-omnes">Prix maximum</label>
                        <input
                            type="number"
                            name="prix_max"
                            step="0.01"
                            value="<?= $prixMax > 0 ? h((string)$prixMax) : '' ?>"
                            class="form-control-omnes"
                            placeholder="Ex : 1000"
                        >
                    </div>

                    <button type="submit" class="btn btn-primary-omnes w-100 mb-2">
                        Filtrer
                    </button>

                    <a href="<?= BASE_URL ?>/articles.php" class="btn btn-outline-omnes w-100">
                        Réinitialiser
                    </a>
                </form>
            </div>
        </div>

        <!-- Colonne articles -->
        <div class="col-lg-9">
            <?php if (empty($articles)): ?>
                <div class="card card-omnes p-4">
                    <h3 class="font-display mb-2">Aucun article trouvé</h3>
                    <p class="text-muted mb-0">Essaie de modifier les filtres.</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($articles as $article): ?>
                        <?php
                        $imageUrl = articleImageUrl($article['photo'] ?? '', $article['nom'] ?? '');
                        ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card card-omnes h-100">

                                <?php if ($imageUrl !== ''): ?>
                                    <img
                                        src="<?= h($imageUrl) ?>"
                                        class="card-img-top"
                                        alt="<?= h($article['nom']) ?>"
                                    >
                                <?php else: ?>
                                    <div class="img-placeholder">
                                        <i class="bi bi-image"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="card-body">
                                    <div class="mb-2">
                                        <?= renderModeBadge($article['mode_vente']) ?>
                                    </div>

                                    <h5 class="card-title font-display mb-2">
                                        <?= h($article['nom']) ?>
                                    </h5>

                                    <p class="text-muted mb-2">
                                        <?= h($article['categorie_libelle']) ?>
                                    </p>

                                    <p class="price-display price-sm mb-3">
                                        <?= formatPrix((float)$article['prix_base']) ?>
                                    </p>

                                    <a href="<?= BASE_URL ?>/article-detail.php?id=<?= (int)$article['idArticle'] ?>" class="btn btn-primary-omnes">
                                        Voir l'article
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($pagination['total_pages'] > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination">
                            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query([
                                        'page' => $i,
                                        'q' => $search,
                                        'categorie' => $categorie,
                                        'mode' => $modeVente,
                                        'prix_max' => $prixMax,
                                        'type' => $typeMarch
                                    ]) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php renderFooter(); ?><?php
// ============================================================
// articles.php - Catalogue des articles
// Version prête à coller
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/ui.php';

$pdo = db();

// ------------------------------------------------------------
// Récupération des filtres
// ------------------------------------------------------------
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 9;
$search    = clean($_GET['q'] ?? '');
$categorie = (int)($_GET['categorie'] ?? 0);
$modeVente = clean($_GET['mode'] ?? '');
$prixMax   = (float)($_GET['prix_max'] ?? 0);
$typeMarch = clean($_GET['type'] ?? '');

// ------------------------------------------------------------
// Construction dynamique du WHERE
// ------------------------------------------------------------
$where = ["a.status = 'disponible'"];
$params = [];

if ($search !== '') {
    $where[] = "(a.nom LIKE ? OR a.description_qualite LIKE ? OR a.description_defaut LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($categorie > 0) {
    $where[] = "a.idCategorie = ?";
    $params[] = $categorie;
}

if (in_array($modeVente, ['immediat', 'negotiation', 'enchere'])) {
    $where[] = "a.mode_vente = ?";
    $params[] = $modeVente;
}

if ($prixMax > 0) {
    $where[] = "a.prix_base <= ?";
    $params[] = $prixMax;
}

if (in_array($typeMarch, ['rare', 'haute_gamme', 'regulier'])) {
    $where[] = "c.type_marchandise = ?";
    $params[] = $typeMarch;
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

// ------------------------------------------------------------
// Nombre total d'articles
// ------------------------------------------------------------
$countSql = "
    SELECT COUNT(*)
    FROM article a
    JOIN categorie c ON a.idCategorie = c.idCategorie
    $whereClause
";

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

// Pagination
$pagination = paginate($total, $perPage, $page);

// ------------------------------------------------------------
// Récupération des articles
// ------------------------------------------------------------
$sql = "
    SELECT 
        a.*,
        c.libelle AS categorie_libelle,
        c.type_marchandise,
        (
            SELECT url_photo
            FROM photo_article pa
            WHERE pa.idArticle = a.idArticle
            ORDER BY ordre ASC
            LIMIT 1
        ) AS photo
    FROM article a
    JOIN categorie c ON a.idCategorie = c.idCategorie
    $whereClause
    ORDER BY a.date_publication DESC
    LIMIT ? OFFSET ?
";

$paramsArticles = $params;
$paramsArticles[] = $perPage;
$paramsArticles[] = $pagination['offset'];

$stmt = $pdo->prepare($sql);
$stmt->execute($paramsArticles);
$articles = $stmt->fetchAll();

// ------------------------------------------------------------
// Récupération des catégories pour le filtre
// ------------------------------------------------------------
$categories = $pdo->query("
    SELECT idCategorie, libelle
    FROM categorie
    ORDER BY libelle ASC
")->fetchAll();

renderHead('Tout parcourir - Omnes MarketPlace');
renderNavbar('articles');
?>

<div class="page-hero">
    <div class="container">
        <h1 class="mt-2 mb-1">Tout Parcourir</h1>
        <p style="color:rgba(255,255,255,.8);margin:0;">
            <?= $total ?> article(s) disponible(s)
        </p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">

        <!-- Colonne filtres -->
        <div class="col-lg-3">
            <div class="filter-sidebar">
                <h5 style="font-size:1.05rem;margin-bottom:20px;">
                    <i class="bi bi-funnel me-2" style="color:var(--primary);"></i>
                    Filtres
                </h5>

                <form method="GET" action="">
                    <div class="mb-3">
                        <label class="form-label-omnes">Recherche</label>
                        <input
                            type="text"
                            name="q"
                            value="<?= h($search) ?>"
                            class="form-control-omnes"
                            placeholder="Nom ou description"
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label-omnes">Catégorie</label>
                        <select name="categorie" class="form-select-omnes">
                            <option value="0">Toutes les catégories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int)$cat['idCategorie'] ?>" <?= $categorie === (int)$cat['idCategorie'] ? 'selected' : '' ?>>
                                    <?= h($cat['libelle']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-omnes">Mode de vente</label>
                        <select name="mode" class="form-select-omnes">
                            <option value="">Tous</option>
                            <option value="immediat" <?= $modeVente === 'immediat' ? 'selected' : '' ?>>Immédiat</option>
                            <option value="negotiation" <?= $modeVente === 'negotiation' ? 'selected' : '' ?>>Négociation</option>
                            <option value="enchere" <?= $modeVente === 'enchere' ? 'selected' : '' ?>>Enchère</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-omnes">Type d'article</label>
                        <select name="type" class="form-select-omnes">
                            <option value="">Tous</option>
                            <option value="rare" <?= $typeMarch === 'rare' ? 'selected' : '' ?>>Rare</option>
                            <option value="haute_gamme" <?= $typeMarch === 'haute_gamme' ? 'selected' : '' ?>>Haute gamme</option>
                            <option value="regulier" <?= $typeMarch === 'regulier' ? 'selected' : '' ?>>Régulier</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-omnes">Prix maximum</label>
                        <input
                            type="number"
                            name="prix_max"
                            step="0.01"
                            value="<?= $prixMax > 0 ? h((string)$prixMax) : '' ?>"
                            class="form-control-omnes"
                            placeholder="Ex : 1000"
                        >
                    </div>

                    <button type="submit" class="btn btn-primary-omnes w-100 mb-2">
                        Filtrer
                    </button>

                    <a href="<?= BASE_URL ?>/articles.php" class="btn btn-outline-omnes w-100">
                        Réinitialiser
                    </a>
                </form>
            </div>
        </div>

        <!-- Colonne articles -->
        <div class="col-lg-9">
            <?php if (empty($articles)): ?>
                <div class="card card-omnes p-4">
                    <h3 class="font-display mb-2">Aucun article trouvé</h3>
                    <p class="text-muted mb-0">Essaie de modifier les filtres.</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($articles as $article): ?>
                        <?php
                        $imageUrl = articleImageUrl($article['photo'] ?? '', $article['nom'] ?? '');
                        ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card card-omnes h-100">

                                <?php if ($imageUrl !== ''): ?>
                                    <img
                                        src="<?= h($imageUrl) ?>"
                                        class="card-img-top"
                                        alt="<?= h($article['nom']) ?>"
                                    >
                                <?php else: ?>
                                    <div class="img-placeholder">
                                        <i class="bi bi-image"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="card-body">
                                    <div class="mb-2">
                                        <?= renderModeBadge($article['mode_vente']) ?>
                                    </div>

                                    <h5 class="card-title font-display mb-2">
                                        <?= h($article['nom']) ?>
                                    </h5>

                                    <p class="text-muted mb-2">
                                        <?= h($article['categorie_libelle']) ?>
                                    </p>

                                    <p class="price-display price-sm mb-3">
                                        <?= formatPrix((float)$article['prix_base']) ?>
                                    </p>

                                    <a href="<?= BASE_URL ?>/article-detail.php?id=<?= (int)$article['idArticle'] ?>" class="btn btn-primary-omnes">
                                        Voir l'article
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($pagination['total_pages'] > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination">
                            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query([
                                        'page' => $i,
                                        'q' => $search,
                                        'categorie' => $categorie,
                                        'mode' => $modeVente,
                                        'prix_max' => $prixMax,
                                        'type' => $typeMarch
                                    ]) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php renderFooter(); ?>