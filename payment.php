<?php
// ============================================================
// payment.php - Paiement simple + création commande
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

requireRole('acheteur');

$pdo = db();
$idAcheteur = currentUserId();
$errors = [];
$checkoutData = $_SESSION['checkout_data'] ?? null;

if (!$checkoutData) {
    setFlash('error', 'Veuillez remplir l\'adresse de livraison avant le paiement.');
    redirect(BASE_URL . '/checkout.php');
}

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

$stmt = $pdo->prepare("SELECT * FROM panier WHERE idAcheteur = ? AND statut = 'ouvert' ORDER BY idPanier DESC LIMIT 1");
$stmt->execute([$idAcheteur]);
$panier = $stmt->fetch();

if (!$panier) {
    setFlash('error', 'Panier introuvable.');
    redirect(BASE_URL . '/cart.php');
}

$stmt = $pdo->prepare("
    SELECT lp.*, a.nom, a.idVendeur
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Token CSRF invalide.";
    } else {
        $typePaiement = clean($_POST['type_paiement'] ?? '');
        $nomCarte     = clean($_POST['nom_carte'] ?? '');
        $numeroCarte  = preg_replace('/\s+/', '', $_POST['numero_carte'] ?? '');
        $expiration   = clean($_POST['expiration'] ?? '');

        if (!in_array($typePaiement, ['visa', 'mastercard', 'amex', 'paypal'])) {
            $errors[] = "Type de paiement invalide.";
        }
        if ($nomCarte === '') {
            $errors[] = "Le nom sur la carte est obligatoire.";
        }
        if ($numeroCarte === '' || strlen($numeroCarte) < 12) {
            $errors[] = "Le numéro de carte est invalide.";
        }
        if ($expiration === '') {
            $errors[] = "La date d'expiration est obligatoire.";
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // On prend le premier mode du panier
                $modeValidation = $lignes[0]['mode_acquisition'];

                // Adresse finale sous forme de texte simple
                $adresseFinale = $checkoutData['prenom'] . ' ' . $checkoutData['nom'] . "\n"
                    . $checkoutData['adresse'] . "\n"
                    . $checkoutData['code_postal'] . ' ' . $checkoutData['ville'] . "\n"
                    . 'Téléphone : ' . $checkoutData['telephone'];

                // Création de la commande
                $stmt = $pdo->prepare("
                    INSERT INTO commande (montant_total, mode_validation, status_commande, adresse_livraison, idAcheteur, idPanier)
                    VALUES (?, ?, 'validee', ?, ?, ?)
                ");
                $stmt->execute([
                    $panier['sous_total'],
                    $modeValidation,
                    $adresseFinale,
                    $idAcheteur,
                    $panier['idPanier']
                ]);
                $idCommande = (int)$pdo->lastInsertId();

                // Enregistrement du paiement
                $stmt = $pdo->prepare("
                    INSERT INTO paiement (numero_masque, nom_carte, expiration, statut_paiement, type_paiement, idCommande, idAcheteur)
                    VALUES (?, ?, ?, 'approuve', ?, ?, ?)
                ");
                $stmt->execute([
                    masquerCarte($numeroCarte),
                    $nomCarte,
                    $expiration,
                    $typePaiement,
                    $idCommande,
                    $idAcheteur
                ]);

                // Validation du panier
                $stmt = $pdo->prepare("UPDATE panier SET statut = 'valide' WHERE idPanier = ?");
                $stmt->execute([$panier['idPanier']]);

                // Les articles du panier passent en vendu
                $stmtUpdateArticle = $pdo->prepare("UPDATE article SET status = 'vendu' WHERE idArticle = ?");
                foreach ($lignes as $ligne) {
                    $stmtUpdateArticle->execute([$ligne['idArticle']]);

                    createNotification(
                        $pdo,
                        "Votre commande pour l'article \"" . $ligne['nom'] . "\" a été validée.",
                        'commande',
                        $idAcheteur,
                        null
                    );

                    createNotification(
                        $pdo,
                        "L'article \"" . $ligne['nom'] . "\" a été acheté et payé.",
                        'commande',
                        null,
                        (int)$ligne['idVendeur']
                    );
                }

                $pdo->commit();

                unset($_SESSION['checkout_data']);
                setFlash('success', 'Paiement validé avec succès.');
                redirect(BASE_URL . '/orders.php');
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = "Erreur pendant le paiement : " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paiement - Omnes MarketPlace</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        body { background:#f8fafa; font-family:Arial, sans-serif; }
        .page { max-width:1100px; margin:30px auto; padding:20px; }
        .box { background:#fff; border:1px solid #d9e6e6; border-radius:10px; padding:20px; margin-bottom:20px; }
        .field { margin-bottom:12px; }
        .field label { display:block; margin-bottom:5px; font-weight:bold; }
        .field input, .field select { width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; }
        .btn { display:inline-block; padding:10px 16px; border:none; border-radius:8px; cursor:pointer; text-decoration:none; background:#0e8c8c; color:#fff; }
        .msg-err { background:#fdecea; color:#c62828; padding:10px; border-radius:8px; margin-bottom:10px; }
    </style>
</head>
<body>
<div class="page">
    <h1>Paiement</h1>
    <p><a href="<?= BASE_URL ?>/checkout.php">Retour à la livraison</a></p>

    <?php foreach ($errors as $error): ?>
        <div class="msg-err"><?= h($error) ?></div>
    <?php endforeach; ?>

    <div class="box">
        <h2>Adresse de livraison</h2>
        <p><?= h($checkoutData['prenom'] . ' ' . $checkoutData['nom']) ?></p>
        <p><?= h($checkoutData['adresse']) ?></p>
        <p><?= h($checkoutData['code_postal'] . ' ' . $checkoutData['ville']) ?></p>
        <p><?= h($checkoutData['telephone']) ?></p>
    </div>

    <div class="box">
        <h2>Informations de paiement</h2>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="field">
                <label>Type de paiement</label>
                <select name="type_paiement">
                    <option value="visa">Visa</option>
                    <option value="mastercard">MasterCard</option>
                    <option value="amex">Amex</option>
                    <option value="paypal">PayPal</option>
                </select>
            </div>

            <div class="field">
                <label>Nom sur la carte</label>
                <input type="text" name="nom_carte">
            </div>

            <div class="field">
                <label>Numéro de carte</label>
                <input type="text" name="numero_carte" placeholder="1111222233334444">
            </div>

            <div class="field">
                <label>Expiration</label>
                <input type="text" name="expiration" placeholder="MM/YYYY">
            </div>

            <button class="btn" type="submit">Payer maintenant</button>
        </form>
    </div>

    <div class="box">
        <h2>Montant total</h2>
        <p><strong><?= formatPrix((float)$panier['sous_total']) ?></strong></p>
    </div>
</div>
</body>
</html>