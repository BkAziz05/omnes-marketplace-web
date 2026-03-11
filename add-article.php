<?php
// ============================================================
// add-article.php
// Ajout d'un article par un vendeur
// Version simple :
// - formulaire classique
// - choix catégorie
// - choix mode de vente
// - ajout facultatif d'une image via chemin texte
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
// 2) Charger les catégories
// ------------------------------------------------------------
$stmt = $pdo->query("SELECT idCategorie, libelle FROM categorie ORDER BY libelle ASC");
$categories = $stmt->fetchAll();

// ------------------------------------------------------------
// 3) Valeurs par défaut du formulaire
// ------------------------------------------------------------
$nom = '';
$descriptionQualite = '';
$descriptionDefaut = '';
$videoUrl = '';
$prixBase = '';
$modeVente = 'immediat';
$status = 'disponible';
$idCategorie = 0;
$dateDebutEnchere = '';
$dateFinEnchere = '';
$imagePath = '';

// ------------------------------------------------------------
// 4) Traitement formulaire
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Token CSRF invalide.";
    } else {
        $nom = trim($_POST['nom'] ?? '');
        $descriptionQualite = trim($_POST['description_qualite'] ?? '');
        $descriptionDefaut = trim($_POST['description_defaut'] ?? '');
        $videoUrl = trim($_POST['video_url'] ?? '');
        $prixBase = trim($_POST['prix_base'] ?? '');
        $modeVente = trim($_POST['mode_vente'] ?? 'immediat');
        $status = trim($_POST['status'] ?? 'disponible');
        $idCategorie = (int)($_POST['idCategorie'] ?? 0);
        $dateDebutEnchere = trim($_POST['date_debut_enchere'] ?? '');
        $dateFinEnchere = trim($_POST['date_fin_enchere'] ?? '');
        $imagePath = trim($_POST['image_path'] ?? '');

        if ($nom === '') $errors[] = "Le nom de l'article est obligatoire.";
        if ($descriptionQualite === '') $errors[] = "La description est obligatoire.";
        if ($prixBase === '' || !is_numeric($prixBase)) $errors[] = "Le prix de base est invalide.";
        if ($idCategorie <= 0) $errors[] = "La catégorie est obligatoire.";
        if (!in_array($modeVente, ['immediat', 'negotiation', 'enchere'])) $errors[] = "Le mode de vente est invalide.";
        if (!in_array($status, ['disponible', 'en_cours', 'vendu'])) $errors[] = "Le statut est invalide.";

        // Si c'est une enchère, on demande les dates
        if ($modeVente === 'enchere') {
            if ($dateDebutEnchere === '') $errors[] = "La date de début de l'enchère est obligatoire.";
            if ($dateFinEnchere === '') $errors[] = "La date de fin de l'enchère est obligatoire.";

            if ($dateDebutEnchere !== '' && $dateFinEnchere !== '') {
                if (strtotime($dateFinEnchere) <= strtotime($dateDebutEnchere)) {
                    $errors[] = "La date de fin doit être après la date de début.";
                }
            }
        } else {
            $dateDebutEnchere = null;
            $dateFinEnchere = null;
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // ------------------------------------------------
                // Insertion article
                // ------------------------------------------------
                $stmt = $pdo->prepare("
                    INSERT INTO article (
                        nom,
                        description_qualite,
                        description_defaut,
                        video_url,
                        prix_base,
                        mode_vente,
                        status,
                        date_publication,
                        date_debut_enchere,
                        date_fin_enchere,
                        idCategorie,
                        idVendeur
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $nom,
                    $descriptionQualite,
                    $descriptionDefaut !== '' ? $descriptionDefaut : null,
                    $videoUrl !== '' ? $videoUrl : null,
                    round((float)$prixBase, 2),
                    $modeVente,
                    $status,
                    $dateDebutEnchere !== '' ? $dateDebutEnchere : null,
                    $dateFinEnchere !== '' ? $dateFinEnchere : null,
                    $idCategorie,
                    $idVendeur
                ]);

                $idArticle = (int)$pdo->lastInsertId();

                // ------------------------------------------------
                // Ajout photo si un chemin est donné
                // Exemple possible : assets/images/macbook.jpg
                // ------------------------------------------------
                if ($imagePath !== '') {
                    $stmt = $pdo->prepare("
                        INSERT INTO photo_article (url_photo, ordre, idArticle)
                        VALUES (?, 1, ?)
                    ");
                    $stmt->execute([$imagePath, $idArticle]);
                }

                $pdo->commit();

                setFlash('success', 'Article ajouté avec succès.');
                redirect(BASE_URL . '/seller.php');
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = "Erreur lors de l'ajout : " . $e->getMessage();
            }
        }
    }
}

renderHead('Ajouter un article - Omnes MarketPlace');
renderNavbar();
?>

<div class="page-hero">
    <div class="container">
        <h1 class="mt-2 mb-1">Ajouter un article</h1>
        <p style="color:rgba(255,255,255,.8);margin:0;">Publie un nouvel article à vendre</p>
    </div>
</div>

<div class="container py-5">
    <div class="mb-4">
        <a href="<?= BASE_URL ?>/seller.php" class="btn btn-outline-omnes">← Retour à l’espace vendeur</a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card card-omnes p-4 p-lg-5">
                <h2 class="font-display mb-4">Formulaire d’ajout</h2>

                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-danger"><?= h($error) ?></div>
                <?php endforeach; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                    <div class="mb-3">
                        <label class="form-label-omnes">Nom de l’article</label>
                        <input type="text" name="nom" value="<?= h($nom) ?>" class="form-control-omnes" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-omnes">Description principale</label>
                        <textarea name="description_qualite" class="form-control-omnes" rows="4" required><?= h($descriptionQualite) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-omnes">Défauts éventuels</label>
                        <textarea name="description_defaut" class="form-control-omnes" rows="3"><?= h($descriptionDefaut) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-omnes">Lien vidéo (optionnel)</label>
                        <input type="text" name="video_url" value="<?= h($videoUrl) ?>" class="form-control-omnes">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label-omnes">Prix de base</label>
                            <input type="number" name="prix_base" step="0.01" value="<?= h($prixBase) ?>" class="form-control-omnes" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label-omnes">Catégorie</label>
                            <select name="idCategorie" class="form-select-omnes" required>
                                <option value="0">Choisir une catégorie</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int)$cat['idCategorie'] ?>" <?= $idCategorie === (int)$cat['idCategorie'] ? 'selected' : '' ?>>
                                        <?= h($cat['libelle']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label-omnes">Mode de vente</label>
                            <select name="mode_vente" class="form-select-omnes" required>
                                <option value="immediat" <?= $modeVente === 'immediat' ? 'selected' : '' ?>>Achat immédiat</option>
                                <option value="negotiation" <?= $modeVente === 'negotiation' ? 'selected' : '' ?>>Négociation</option>
                                <option value="enchere" <?= $modeVente === 'enchere' ? 'selected' : '' ?>>Enchère</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label-omnes">Statut</label>
                            <select name="status" class="form-select-omnes" required>
                                <option value="disponible" <?= $status === 'disponible' ? 'selected' : '' ?>>Disponible</option>
                                <option value="en_cours" <?= $status === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                                <option value="vendu" <?= $status === 'vendu' ? 'selected' : '' ?>>Vendu</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label-omnes">Début enchère</label>
                            <input type="datetime-local" name="date_debut_enchere" value="<?= h($dateDebutEnchere) ?>" class="form-control-omnes">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label-omnes">Fin enchère</label>
                            <input type="datetime-local" name="date_fin_enchere" value="<?= h($dateFinEnchere) ?>" class="form-control-omnes">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-omnes">Chemin image (optionnel)</label>
                        <input type="text" name="image_path" value="<?= h($imagePath) ?>" class="form-control-omnes" placeholder="Ex : assets/images/macbook.jpg">
                        <small class="text-muted">Tu peux mettre une image déjà présente dans assets/images.</small>
                    </div>

                    <button type="submit" class="btn btn-primary-omnes">
                        Ajouter l’article
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>