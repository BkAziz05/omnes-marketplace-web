<?php
// ============================================================
// admin.php
// Espace administrateur simple
// - accès réservé à l'admin connecté
// - liste les vendeurs
// - ajoute un vendeur
// - supprime un vendeur
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
// 1) Vérifier que l'utilisateur connecté est bien admin
// ------------------------------------------------------------
if (currentUserRole() !== 'admin') {
    setFlash('error', 'Accès refusé.');
    redirect(BASE_URL . '/login.php');
}

$idAdmin = currentUserId();

$stmt = $pdo->prepare("SELECT * FROM administrateur WHERE idAdmin = ? LIMIT 1");
$stmt->execute([$idAdmin]);
$admin = $stmt->fetch();

if (!$admin) {
    session_unset();
    session_destroy();
    redirect(BASE_URL . '/login.php');
}

// ------------------------------------------------------------
// 2) Petite fonction utilitaire : savoir si une colonne existe
// ------------------------------------------------------------
function adminColumnExists(PDO $pdo, string $tableName, string $columnName): bool
{
    $sql = "SHOW COLUMNS FROM `$tableName`";
    $stmt = $pdo->query($sql);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        if (($col['Field'] ?? '') === $columnName) {
            return true;
        }
    }

    return false;
}

// ------------------------------------------------------------
// 3) Traitement des formulaires
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Token CSRF invalide.";
    } else {
        $action = $_POST['action'] ?? '';

        // =====================================================
        // AJOUT D'UN VENDEUR
        // =====================================================
        if ($action === 'add_vendor') {
            $prenom = trim($_POST['prenom'] ?? '');
            $nom = trim($_POST['nom'] ?? '');
            $pseudo = trim($_POST['pseudo'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if ($prenom === '') $errors[] = "Le prénom est obligatoire.";
            if ($nom === '') $errors[] = "Le nom est obligatoire.";
            if ($pseudo === '') $errors[] = "Le pseudo est obligatoire.";
            if ($email === '') $errors[] = "L'email est obligatoire.";
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "L'email est invalide.";
            if ($password === '') $errors[] = "Le mot de passe est obligatoire.";

            if (empty($errors)) {
                $stmt = $pdo->prepare("SELECT idVendeur FROM vendeur WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);

                if ($stmt->fetch()) {
                    $errors[] = "Un vendeur existe déjà avec cet email.";
                } else {
                $stmt = $pdo->prepare("
                    INSERT INTO vendeur (nom, prenom, pseudo, email, mot_de_passe, idAdmin)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $nom,
                    $prenom,
                    $pseudo,
                    $email,
                    $password,
                    $idAdmin
                ]);

                    $success = "Le vendeur a bien été ajouté.";
                }
            }
        }

        // =====================================================
        // SUPPRESSION D'UN VENDEUR
        // =====================================================
        if ($action === 'delete_vendor') {
            $idVendeur = (int)($_POST['idVendeur'] ?? 0);

            if ($idVendeur <= 0) {
                $errors[] = "ID vendeur invalide.";
            } else {
                try {
                    $pdo->beginTransaction();

                    // Supprimer d'abord les photos liées aux articles du vendeur
                    $stmt = $pdo->prepare("
                        DELETE pa
                        FROM photo_article pa
                        INNER JOIN article a ON pa.idArticle = a.idArticle
                        WHERE a.idVendeur = ?
                    ");
                    $stmt->execute([$idVendeur]);

                    // Supprimer ensuite les articles du vendeur
                    $stmt = $pdo->prepare("DELETE FROM article WHERE idVendeur = ?");
                    $stmt->execute([$idVendeur]);

                    // Supprimer enfin le vendeur
                    $stmt = $pdo->prepare("DELETE FROM vendeur WHERE idVendeur = ?");
                    $stmt->execute([$idVendeur]);

                    $pdo->commit();
                    $success = "Le vendeur a été supprimé.";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $errors[] = "Erreur lors de la suppression : " . $e->getMessage();
                }
            }
        }
    }
}

// ------------------------------------------------------------
// 4) Statistiques simples
// ------------------------------------------------------------
$nbVendeurs = (int)$pdo->query("SELECT COUNT(*) FROM vendeur")->fetchColumn();
$nbArticles = (int)$pdo->query("SELECT COUNT(*) FROM article")->fetchColumn();
$nbAcheteurs = 0;

try {
    $nbAcheteurs = (int)$pdo->query("SELECT COUNT(*) FROM acheteur")->fetchColumn();
} catch (Exception $e) {
    $nbAcheteurs = 0;
}

// ------------------------------------------------------------
// 5) Liste des vendeurs
// ------------------------------------------------------------
$stmt = $pdo->query("
    SELECT 
        v.*,
        (
            SELECT COUNT(*)
            FROM article a
            WHERE a.idVendeur = v.idVendeur
        ) AS nb_articles
    FROM vendeur v
    ORDER BY v.idVendeur DESC
");
$vendors = $stmt->fetchAll();

renderHead('Admin - Omnes MarketPlace');
renderNavbar();
?>

<div class="page-hero">
    <div class="container">
        <h1 class="mt-2 mb-1">Espace administrateur</h1>
        <p style="color:rgba(255,255,255,.8);margin:0;">Gestion des vendeurs et supervision simple de la plateforme</p>
    </div>
</div>

<div class="container py-5">

    <!-- Statistiques -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card card-omnes p-4">
                <h3 class="font-display mb-2"><?= $nbVendeurs ?></h3>
                <p class="mb-0 text-muted">Vendeurs</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-omnes p-4">
                <h3 class="font-display mb-2"><?= $nbArticles ?></h3>
                <p class="mb-0 text-muted">Articles</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-omnes p-4">
                <h3 class="font-display mb-2"><?= $nbAcheteurs ?></h3>
                <p class="mb-0 text-muted">Acheteurs</p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Bloc ajout vendeur -->
        <div class="col-lg-4">
            <div class="card card-omnes p-4">
                <h2 class="font-display mb-4">Ajouter un vendeur</h2>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?= h($success) ?></div>
                <?php endif; ?>

                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-danger"><?= h($error) ?></div>
                <?php endforeach; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="add_vendor">

                    <div class="mb-3">
                        <label class="form-label-omnes">Prénom</label>
                        <input type="text" name="prenom" class="form-control-omnes" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-omnes">Nom</label>
                        <input type="text" name="nom" class="form-control-omnes" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-omnes">Pseudo</label>
                        <input type="text" name="pseudo" class="form-control-omnes" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-omnes">Email</label>
                        <input type="email" name="email" class="form-control-omnes" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-omnes">Mot de passe</label>
                        <input type="text" name="password" class="form-control-omnes" required>
                    </div>

                    <button type="submit" class="btn btn-primary-omnes w-100">
                        Ajouter le vendeur
                    </button>
                </form>
            </div>
        </div>

        <!-- Bloc liste vendeurs -->
        <div class="col-lg-8">
            <div class="card card-omnes p-4">
                <h2 class="font-display mb-4">Liste des vendeurs</h2>

                <?php if (empty($vendors)): ?>
                    <p class="mb-0">Aucun vendeur enregistré.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Pseudo</th>
                                    <th>Email</th>
                                    <th>Articles</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vendors as $vendor): ?>
                                    <tr>
                                        <td><?= (int)$vendor['idVendeur'] ?></td>
                                        <td><?= h(($vendor['prenom'] ?? '') . ' ' . ($vendor['nom'] ?? '')) ?></td>
                                        <td><?= h($vendor['pseudo'] ?? '') ?></td>
                                        <td><?= h($vendor['email'] ?? '') ?></td>
                                        <td><?= (int)($vendor['nb_articles'] ?? 0) ?></td>
                                        <td>
                                            <form method="POST" action="" onsubmit="return confirm('Supprimer ce vendeur ?');">
                                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                                <input type="hidden" name="action" value="delete_vendor">
                                                <input type="hidden" name="idVendeur" value="<?= (int)$vendor['idVendeur'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    Supprimer
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>