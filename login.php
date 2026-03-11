<?php
// ============================================================
// login.php
// Connexion compatible avec plusieurs structures :
// - acheteur / vendeur / admin ou administrateur
// - mdp / mot_de_passe / mot_de_passe_hash
// ============================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

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
$email = '';
$role = '';

/**
 * Vérifie si une table existe.
 */
function tableExistsLogin(PDO $pdo, string $tableName): bool
{
    $sql = "SHOW TABLES LIKE " . $pdo->quote($tableName);
    $stmt = $pdo->query($sql);
    return (bool)$stmt->fetchColumn();
}

/**
 * Vérifie un mot de passe selon la structure de la table.
 */
function passwordMatchesLogin(array $user, string $password): bool
{
    if (isset($user['mdp']) && $user['mdp'] === $password) {
        return true;
    }

    if (isset($user['mot_de_passe']) && $user['mot_de_passe'] === $password) {
        return true;
    }

    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Token CSRF invalide.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = trim($_POST['role'] ?? '');

        if ($email === '') {
            $errors[] = "L'email est obligatoire.";
        }

        if ($password === '') {
            $errors[] = "Le mot de passe est obligatoire.";
        }

        if (!in_array($role, ['acheteur', 'vendeur', 'admin'])) {
            $errors[] = "Le rôle est invalide.";
        }

        if (empty($errors)) {
            $user = null;
            $userId = null;
            $userName = '';

            // -----------------------------------------
            // ACHETEUR
            // -----------------------------------------
            if ($role === 'acheteur') {
                $stmt = $pdo->prepare("SELECT * FROM acheteur WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user) {
                    $userId = $user['idAcheteur'] ?? null;
                    $userName = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
                }
            }

            // -----------------------------------------
            // VENDEUR
            // -----------------------------------------
            elseif ($role === 'vendeur') {
                $stmt = $pdo->prepare("SELECT * FROM vendeur WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user) {
                    $userId = $user['idVendeur'] ?? null;
                    $userName = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
                }
            }

            // -----------------------------------------
            // ADMIN / ADMINISTRATEUR
            // -----------------------------------------
            elseif ($role === 'admin') {
                if (tableExistsLogin($pdo, 'admin')) {
                    $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ? LIMIT 1");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();

                    if ($user) {
                        $userId = $user['idAdmin'] ?? null;
                        $userName = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
                    }
                } elseif (tableExistsLogin($pdo, 'administrateur')) {
                    $stmt = $pdo->prepare("SELECT * FROM administrateur WHERE email = ? LIMIT 1");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();

                    if ($user) {
                        $userId = $user['idAdministrateur'] ?? ($user['idAdmin'] ?? null);
                        $userName = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
                    }
                } else {
                    $errors[] = "Aucune table admin ou administrateur trouvée dans la base.";
                }
            }

            if (empty($errors)) {
                if (!$user) {
                    $errors[] = "Aucun compte trouvé avec cet email.";
                } elseif (!passwordMatchesLogin($user, $password)) {
                    $errors[] = "Email ou mot de passe incorrect.";
                } else {
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_role'] = $role;
                    $_SESSION['user_nom'] = $userName !== '' ? $userName : ($user['email'] ?? 'Utilisateur');

                    setFlash('success', 'Connexion réussie.');

                    if ($role === 'admin') {
                        redirect(BASE_URL . '/admin.php');
                    } elseif ($role === 'vendeur') {
                        redirect(BASE_URL . '/seller.php');
                    } else {
                        redirect(BASE_URL . '/index.php');
                    }
                }
            }
        }
    }
}

$flashes = getFlashes();

renderHead('Connexion - Omnes MarketPlace');
renderNavbar();
renderFlashes($flashes);
?>

<div class="page-hero">
    <div class="container">
        <h1 class="mt-2 mb-1">Connexion</h1>
        <p style="color:rgba(255,255,255,.8);margin:0;">Connecte-toi à ton compte</p>
    </div>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card card-omnes p-4 p-lg-5">
                <h2 class="font-display mb-4">Se connecter</h2>

                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-danger"><?= h($error) ?></div>
                <?php endforeach; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                    <div class="mb-3">
                        <label class="form-label-omnes">Rôle</label>
                        <select name="role" class="form-select-omnes" required>
                            <option value="">Choisir</option>
                            <option value="acheteur" <?= $role === 'acheteur' ? 'selected' : '' ?>>Acheteur</option>
                            <option value="vendeur" <?= $role === 'vendeur' ? 'selected' : '' ?>>Vendeur</option>
                            <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-omnes">Email</label>
                        <input type="email" name="email" value="<?= h($email) ?>" class="form-control-omnes" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-omnes">Mot de passe</label>
                        <input type="password" name="password" class="form-control-omnes" required>
                    </div>

                    <button type="submit" class="btn btn-primary-omnes w-100">Connexion</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>