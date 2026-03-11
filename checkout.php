<?php
// ============================================================
// checkout.php - Adresse de livraison avant paiement
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

requireRole('acheteur');

$pdo = db();
$idAcheteur = currentUserId();
$errors = [];

/**
 * Retourne le panier ouvert de l'acheteur.
 */
function getOpenCart(PDO $pdo, int $idAcheteur): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM panier WHERE idAcheteur = ? AND statut = 'ouvert' ORDER BY idPanier DESC LIMIT 1");
    $stmt->execute([$idAcheteur]);
    $cart = $stmt->fetch();

    return $cart ?: null;
}

$panier = getOpenCart($pdo, $idAcheteur);

if (!$panier) {
    setFlash('error', 'Aucun panier ouvert trouvé.');
    redirect(BASE_URL . '/cart.php');
}

$stmt = $pdo->prepare("
    SELECT lp.idLigne, lp.prix_snapshot, lp.mode_acquisition, a.nom
    FROM ligne_panier lp
    JOIN article a ON lp.idArticle = a.idArticle
    WHERE lp.idPanier = ?
");
$stmt->execute([$panier['idPanier']]);
$lignes = $stmt->fetchAll();

if (empty($lignes)) {
    setFlash('error', 'Votre panier est vide.');
    redirect(BASE_URL . '/cart.php');
}

$stmt = $pdo->prepare("SELECT * FROM acheteur WHERE idAcheteur = ?");
$stmt->execute([$idAcheteur]);
$acheteur = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Token CSRF invalide.";
    } else {
        $prenom  = clean($_POST['prenom'] ?? '');
        $nom     = clean($_POST['nom'] ?? '');
        $adresse = clean($_POST['adresse'] ?? '');
        $ville   = clean($_POST['ville'] ?? '');
        $code    = clean($_POST['code_postal'] ?? '');
        $tel     = clean($_POST['telephone'] ?? '');

        if ($prenom === '') $errors[] = "Le prénom est obligatoire.";
        if ($nom === '') $errors[] = "Le nom est obligatoire.";
        if ($adresse === '') $errors[] = "L'adresse est obligatoire.";
        if ($ville === '') $errors[] = "La ville est obligatoire.";
        if ($code === '') $errors[] = "Le code postal est obligatoire.";

        if (empty($errors)) {
            $_SESSION['checkout_data'] = [
                'prenom'      => $prenom,
                'nom'         => $nom,
                'adresse'     => $adresse,
                'ville'       => $ville,
                'code_postal' => $code,
                'telephone'   => $tel
            ];

            redirect(BASE_URL . '/payment.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Livraison - Omnes MarketPlace</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        body { background:#f8fafa; font-family:Arial, sans-serif; }
        .page { max-width:1100px; margin:30px auto; padding:20px; }
        .box { background:#fff; border:1px solid #d9e6e6; border-radius:10px; padding:20px; margin-bottom:20px; }
        .field { margin-bottom:12px; }
        .field label { display:block; margin-bottom:5px; font-weight:bold; }
        .field input { width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; }
        .btn { display:inline-block; padding:10px 16px; border:none; border-radius:8px; cursor:pointer; text-decoration:none; }
        .btn-primary { background:#0e8c8c; color:#fff; }
        .msg-err { background:#fdecea; color:#c62828; padding:10px; border-radius:8px; margin-bottom:10px; }
    </style>
</head>
<body>
<div class="page">
    <h1>Finaliser la commande</h1>
    <p><a href="<?= BASE_URL ?>/cart.php">Retour au panier</a></p>

    <?php foreach ($errors as $error): ?>
        <div class="msg-err"><?= h($error) ?></div>
    <?php endforeach; ?>

    <div class="box">
        <h2>Adresse de livraison</h2>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="field">
                <label>Prénom</label>
                <input type="text" name="prenom" value="<?= h($_POST['prenom'] ?? $acheteur['prenom'] ?? '') ?>">
            </div>

            <div class="field">
                <label>Nom</label>
                <input type="text" name="nom" value="<?= h($_POST['nom'] ?? $acheteur['nom'] ?? '') ?>">
            </div>

            <div class="field">
                <label>Adresse</label>
                <input type="text" name="adresse" value="<?= h($_POST['adresse'] ?? $acheteur['adresse'] ?? '') ?>">
            </div>

            <div class="field">
                <label>Ville</label>
                <input type="text" name="ville" value="<?= h($_POST['ville'] ?? '') ?>">
            </div>

            <div class="field">
                <label>Code postal</label>
                <input type="text" name="code_postal" value="<?= h($_POST['code_postal'] ?? '') ?>">
            </div>

            <div class="field">
                <label>Téléphone</label>
                <input type="text" name="telephone" value="<?= h($_POST['telephone'] ?? $acheteur['NumTelephone'] ?? '') ?>">
            </div>

            <button class="btn btn-primary" type="submit">Continuer vers le paiement</button>
        </form>
    </div>

    <div class="box">
        <h2>Récapitulatif</h2>
        <?php foreach ($lignes as $ligne): ?>
            <p><?= h($ligne['nom']) ?> - <?= formatPrix((float)$ligne['prix_snapshot']) ?> (<?= h($ligne['mode_acquisition']) ?>)</p>
        <?php endforeach; ?>
        <p><strong>Total : <?= formatPrix((float)$panier['sous_total']) ?></strong></p>
    </div>
</div>
</body>
</html>