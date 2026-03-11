<?php
// ============================================================
// register.php
// Inscription compatible avec une base qui peut contenir :
// - mdp
// - ou mot_de_passe_hash
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/ui.php';

if (isLoggedIn()) {
    redirect(BASE_URL . '/index.php');
}

$pdo = db();
$errors = [];

$prenom = '';
$nom = '';
$email = '';
$role = 'acheteur';
$pseudo = '';
$adresse = '';
$telephone = '';

/**
 * Vérifie si une colonne existe dans une table.
 */
function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return (bool)$stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Token CSRF invalide.";
    } else {
        $prenom = trim($_POST['prenom'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $role = trim($_POST['role'] ?? 'acheteur');
        $pseudo = trim($_POST['pseudo'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');

        if ($prenom === '') $errors[] = "Le prénom est obligatoire.";
        if ($nom === '') $errors[] = "Le nom est obligatoire.";
        if ($email === '') $errors[] = "L'email est obligatoire.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";
        if ($password === '') $errors[] = "Le mot de passe est obligatoire.";
        if (strlen($password) < 3) $errors[] = "Le mot de passe est trop court.";
        if ($password !== $passwordConfirm) $errors[] = "Les mots de passe ne correspondent pas.";
        if (!in_array($role, ['acheteur', 'vendeur'])) $errors[] = "Le rôle est invalide.";

        if ($role === 'vendeur' && $pseudo === '') {
            $errors[] = "Le pseudo vendeur est obligatoire.";
        }

        if (empty($errors)) {
            if ($role === 'acheteur') {
                $stmt = $pdo->prepare("SELECT idAcheteur FROM acheteur WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);

                if ($stmt->fetch()) {
                    $errors[] = "Un compte acheteur existe déjà avec cet email.";
                } else {
                    $hasHash = columnExists($pdo, 'acheteur', 'mot_de_passe_hash');
                    $hasMdp = columnExists($pdo, 'acheteur', 'mdp');

                    if ($hasHash && $hasMdp) {
                        $stmt = $pdo->prepare("
                            INSERT INTO acheteur (nom, prenom, email, mdp, mot_de_passe_hash, adresse, NumTelephone)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $nom, $prenom, $email, $password, password_hash($password, PASSWORD_DEFAULT),
                            $adresse !== '' ? $adresse : null,
                            $telephone !== '' ? $telephone : null
                        ]);
                    } elseif ($hasMdp) {
                        $stmt = $pdo->prepare("
                            INSERT INTO acheteur (nom, prenom, email, mdp, adresse, NumTelephone)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $nom, $prenom, $email, $password,
                            $adresse !== '' ? $adresse : null,
                            $telephone !== '' ? $telephone : null
                        ]);
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO acheteur (nom, prenom, email, mot_de_passe_hash, adresse, NumTelephone)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $nom, $prenom, $email, password_hash($password, PASSWORD_DEFAULT),
                            $adresse !== '' ? $adresse : null,
                            $telephone !== '' ? $telephone : null
                        ]);
                    }

                    setFlash('success', 'Compte acheteur créé. Tu peux te connecter.');
                    redirect(BASE_URL . '/login.php');
                }
            } else {
                $stmt = $pdo->prepare("SELECT idVendeur FROM vendeur WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);

                if ($stmt->fetch()) {
                    $errors[] = "Un compte vendeur existe déjà avec cet email.";
                } else {
                    $hasHash = columnExists($pdo, 'vendeur', 'mot_de_passe_hash');
                    $hasMdp = columnExists($pdo, 'vendeur', 'mdp');

                    if ($hasHash && $hasMdp) {
                        $stmt = $pdo->prepare("
                            INSERT INTO vendeur (nom, prenom, pseudo, email, mdp, mot_de_passe_hash)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $nom, $prenom, $pseudo, $email, $password, password_hash($password, PASSWORD_DEFAULT)
                        ]);
                    } elseif ($hasMdp) {
                        $stmt = $pdo->prepare("
                            INSERT INTO vendeur (nom, prenom, pseudo, email, mdp)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $nom, $prenom, $pseudo, $email, $password
                        ]);
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO vendeur (nom, prenom, pseudo, email, mot_de_passe_hash)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $nom, $prenom, $pseudo, $email, password_hash($password, PASSWORD_DEFAULT)
                        ]);
                    }

                    setFlash('success', 'Compte vendeur créé. Tu peux te connecter.');
                    redirect(BASE_URL . '/login.php');
                }
            }
        }
    }
}

renderHead('Inscription - Omnes MarketPlace');
renderNavbar();
?>

<div class="page-hero">
    <div class="container">
        <h1 class="mt-2 mb-1">Inscription</h1>
        <p style="color:rgba(255,255,255,.8);margin:0;">Créer un compte</p>
    </div>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card card-omnes p-4 p-lg-5">
                <h2 class="font-display mb-4">Créer un compte</h2>

                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-danger"><?= h($error) ?></div>
                <?php endforeach; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                    <div class="mb-3">
                        <label class="form-label-omnes">Type de compte</label>
                        <select name="role" class="form-select-omnes">
                            <option value="acheteur" <?= $role === 'acheteur' ? 'selected' : '' ?>>Acheteur</option>
                            <option value="vendeur" <?= $role === 'vendeur' ? 'selected' : '' ?>>Vendeur</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label-omnes">Prénom</label>
                            <input type="text" name="prenom" value="<?= h($prenom) ?>" class="form-control-omnes" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label-omnes">Nom</label>
                            <input type="text" name="nom" value="<?= h($nom) ?>" class="form-control-omnes" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-omnes">Email</label>
                        <input type="email" name="email" value="<?= h($email) ?>" class="form-control-omnes" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-omnes">Pseudo vendeur</label>
                        <input type="text" name="pseudo" value="<?= h($pseudo) ?>" class="form-control-omnes">
                    </div>

                    <div class="mb-3">
                        <label class="form-label-omnes">Adresse</label>
                        <input type="text" name="adresse" value="<?= h($adresse) ?>" class="form-control-omnes">
                    </div>

                    <div class="mb-3">
                        <label class="form-label-omnes">Téléphone</label>
                        <input type="text" name="telephone" value="<?= h($telephone) ?>" class="form-control-omnes">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label-omnes">Mot de passe</label>
                            <input type="password" name="password" class="form-control-omnes" required>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label-omnes">Confirmer le mot de passe</label>
                            <input type="password" name="password_confirm" class="form-control-omnes" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-omnes w-100">Créer le compte</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>