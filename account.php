<?php
// ============================================================
// account.php - Mon compte
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/ui.php';

requireLogin();

$pdo = db();
$idUser = currentUserId();
$role = currentUserRole();
$errors = [];
$success = '';

if ($role === 'acheteur') {
    $stmt = $pdo->prepare("SELECT * FROM acheteur WHERE idAcheteur = ? LIMIT 1");
    $stmt->execute([$idUser]);
    $user = $stmt->fetch();
} elseif ($role === 'vendeur') {
    $stmt = $pdo->prepare("SELECT * FROM vendeur WHERE idVendeur = ? LIMIT 1");
    $stmt->execute([$idUser]);
    $user = $stmt->fetch();
} else {
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE idAdmin = ? LIMIT 1");
    $stmt->execute([$idUser]);
    $user = $stmt->fetch();
}

if (!$user) {
    session_unset();
    session_destroy();
    redirect(BASE_URL . '/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Token CSRF invalide.";
    } else {
        $prenom = clean($_POST['prenom'] ?? '');
        $nom = clean($_POST['nom'] ?? '');
        $email = clean($_POST['email'] ?? '');

        if ($prenom === '') $errors[] = "Le prénom est obligatoire.";
        if ($nom === '') $errors[] = "Le nom est obligatoire.";
        if ($email === '') $errors[] = "L'email est obligatoire.";

        if (empty($errors)) {
            if ($role === 'acheteur') {
                $adresse = clean($_POST['adresse'] ?? '');
                $telephone = clean($_POST['telephone'] ?? '');

                $stmt = $pdo->prepare("
                    UPDATE acheteur
                    SET nom = ?, prenom = ?, email = ?, adresse = ?, NumTelephone = ?
                    WHERE idAcheteur = ?
                ");
                $stmt->execute([$nom, $prenom, $email, $adresse ?: null, $telephone ?: null, $idUser]);
            } elseif ($role === 'vendeur') {
                $pseudo = clean($_POST['pseudo'] ?? '');

                if ($pseudo === '') {
                    $errors[] = "Le pseudo est obligatoire pour un vendeur.";
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE vendeur
                        SET nom = ?, prenom = ?, email = ?, pseudo = ?
                        WHERE idVendeur = ?
                    ");
                    $stmt->execute([$nom, $prenom, $email, $pseudo, $idUser]);
                }
            } else {
                $stmt = $pdo->prepare("
                    UPDATE admin
                    SET nom = ?, prenom = ?, email = ?
                    WHERE idAdmin = ?
                ");
                $stmt->execute([$nom, $prenom, $email, $idUser]);
            }

            if (empty($errors)) {
                $_SESSION['user_nom'] = trim($prenom . ' ' . $nom);
                $success = "Votre compte a été mis à jour.";
            }
        }
    }

    if ($role === 'acheteur') {
        $stmt = $pdo->prepare("SELECT * FROM acheteur WHERE idAcheteur = ? LIMIT 1");
        $stmt->execute([$idUser]);
        $user = $stmt->fetch();
    } elseif ($role === 'vendeur') {
        $stmt = $pdo->prepare("SELECT * FROM vendeur WHERE idVendeur = ? LIMIT 1");
        $stmt->execute([$idUser]);
        $user = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE idAdmin = ? LIMIT 1");
        $stmt->execute([$idUser]);
        $user = $stmt->fetch();
    }
}

renderHead('Mon compte - Omnes MarketPlace');
renderNavbar();
?>

<div class="page-hero">
    <div class="container">
        <h1 class="mt-2 mb-1">Mon compte</h1>
        <p style="color:rgba(255,255,255,.8);margin:0;">Gère tes informations personnelles</p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card card-omnes p-4">
                <div style="width:80px;height:80px;border-radius:50%;background:var(--primary-pale);display:flex;align-items:center;justify-content:center;margin-bottom:16px;">
                    <i class="bi bi-person-fill" style="font-size:2rem;color:var(--primary);"></i>
                </div>

                <h3 class="font-display mb-2"><?= h(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?></h3>
                <p class="text-muted mb-2"><?= h($user['email'] ?? '') ?></p>
                <p class="mb-0"><?= h(ucfirst($role)) ?></p>
            </div>

            <?php if ($role === 'acheteur'): ?>
                <div class="card card-omnes p-4 mt-4">
                    <h5 class="font-display mb-3">Raccourcis</h5>
                    <p><a href="<?= BASE_URL ?>/cart.php">Voir mon panier</a></p>
                    <p><a href="<?= BASE_URL ?>/orders.php">Voir mes commandes</a></p>
                    <p><a href="<?= BASE_URL ?>/alerts.php">Voir mes alertes</a></p>
                    <p class="mb-0"><a href="<?= BASE_URL ?>/notifications.php">Voir mes notifications</a></p>
                </div>
            <?php elseif ($role === 'vendeur'): ?>
                <div class="card card-omnes p-4 mt-4">
                    <h5 class="font-display mb-3">Raccourcis</h5>
                    <p><a href="<?= BASE_URL ?>/vendor/dashboard.php">Dashboard vendeur</a></p>
                    <p class="mb-0"><a href="<?= BASE_URL ?>/notifications.php">Voir mes notifications</a></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-8">
            <div class="card card-omnes p-4 p-lg-5">
                <h2 class="font-display mb-4">Modifier mes informations</h2>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?= h($success) ?></div>
                <?php endif; ?>

                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-danger"><?= h($error) ?></div>
                <?php endforeach; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label-omnes">Prénom</label>
                            <input type="text" name="prenom" value="<?= h($user['prenom'] ?? '') ?>" class="form-control-omnes" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label-omnes">Nom</label>
                            <input type="text" name="nom" value="<?= h($user['nom'] ?? '') ?>" class="form-control-omnes" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-omnes">Email</label>
                        <input type="email" name="email" value="<?= h($user['email'] ?? '') ?>" class="form-control-omnes" required>
                    </div>

                    <?php if ($role === 'acheteur'): ?>
                        <div class="mb-3">
                            <label class="form-label-omnes">Adresse</label>
                            <input type="text" name="adresse" value="<?= h($user['adresse'] ?? '') ?>" class="form-control-omnes">
                        </div>

                        <div class="mb-4">
                            <label class="form-label-omnes">Téléphone</label>
                            <input type="text" name="telephone" value="<?= h($user['NumTelephone'] ?? '') ?>" class="form-control-omnes">
                        </div>
                    <?php elseif ($role === 'vendeur'): ?>
                        <div class="mb-4">
                            <label class="form-label-omnes">Pseudo</label>
                            <input type="text" name="pseudo" value="<?= h($user['pseudo'] ?? '') ?>" class="form-control-omnes" required>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary-omnes">Enregistrer les modifications</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>