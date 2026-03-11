<?php
// ============================================================
// bid.php
// Déposer une offre sur une enchère
// ============================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/ui.php';

requireRole('acheteur');

$pdo = db();
$idAcheteur = currentUserId();
$errors = [];
$idArticle = (int)($_GET['id'] ?? $_POST['idArticle'] ?? 0);

if ($idArticle <= 0) {
    setFlash('error', 'Article invalide.');
    redirect(BASE_URL . '/articles.php');
}

$stmt = $pdo->prepare("SELECT * FROM article WHERE idArticle = ? LIMIT 1");
$stmt->execute([$idArticle]);
$article = $stmt->fetch();

if (!$article) {
    setFlash('error', 'Article introuvable.');
    redirect(BASE_URL . '/articles.php');
}

if (($article['mode_vente'] ?? '') !== 'enchere') {
    setFlash('error', 'Cet article n’est pas en enchère.');
    redirect(BASE_URL . '/article-detail.php?id=' . $idArticle);
}

if (!empty($article['date_fin_enchere']) && strtotime($article['date_fin_enchere']) < time()) {
    setFlash('error', 'Cette enchère est terminée.');
    redirect(BASE_URL . '/auction.php?id=' . $idArticle);
}

$stmt = $pdo->prepare("SELECT MAX(montant_courant) FROM offre_enchere WHERE idArticle = ?");
$stmt->execute([$idArticle]);
$maxCourant = (float)$stmt->fetchColumn();

$minimum = $maxCourant > 0 ? ($maxCourant + 1) : (float)$article['prix_base'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Token CSRF invalide.";
    } else {
        $montant = (float)($_POST['montant_max'] ?? 0);

        if ($montant < $minimum) {
            $errors[] = "L'offre doit être au minimum de " . formatPrix($minimum) . ".";
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("
                INSERT INTO offre_enchere (montant_max, montant_courant, statut, idArticle, idAcheteur)
                VALUES (?, ?, 'active', ?, ?)
            ");
            $stmt->execute([$montant, $montant, $idArticle, $idAcheteur]);

            setFlash('success', 'Votre offre a été enregistrée.');
            redirect(BASE_URL . '/auction.php?id=' . $idArticle);
        }
    }
}

renderHead('Enchérir - Omnes MarketPlace');
renderNavbar('articles');
?>

<div class="page-hero">
    <div class="container">
        <h1 class="mt-2 mb-1">Placer une offre</h1>
        <p style="color:rgba(255,255,255,.8);margin:0;"><?= h($article['nom']) ?></p>
    </div>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card card-omnes p-4">
                <h2 class="font-display mb-4">Votre enchère</h2>

                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-danger"><?= h($error) ?></div>
                <?php endforeach; ?>

                <p>Montant minimum : <strong><?= formatPrix($minimum) ?></strong></p>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="idArticle" value="<?= (int)$idArticle ?>">

                    <div class="mb-4">
                        <label class="form-label-omnes">Votre prix maximum</label>
                        <input type="number" name="montant_max" step="0.01" min="<?= h((string)$minimum) ?>" class="form-control-omnes" required>
                    </div>

                    <button type="submit" class="btn btn-primary-omnes">Valider l’offre</button>
                    <a href="<?= BASE_URL ?>/auction.php?id=<?= (int)$idArticle ?>" class="btn btn-outline-omnes">Retour</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>