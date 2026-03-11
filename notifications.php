<?php
// ============================================================
// notifications.php
// Notifications compatibles acheteur / vendeur / admin
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

$idUser = currentUserId();
$role = currentUserRole();

// ------------------------------------------------------------
// Traitement POST
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Token CSRF invalide.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'read_all') {
            if ($role === 'acheteur') {
                $stmt = $pdo->prepare("UPDATE notification SET lue = 1 WHERE idAcheteur = ?");
                $stmt->execute([$idUser]);
            } elseif ($role === 'vendeur') {
                $stmt = $pdo->prepare("UPDATE notification SET lue = 1 WHERE idVendeur = ?");
                $stmt->execute([$idUser]);
            } else {
                $stmt = $pdo->query("UPDATE notification SET lue = 1");
            }

            $success = "Toutes les notifications ont été marquées comme lues.";
        }

        if ($action === 'read_one') {
            $idNotification = (int)($_POST['idNotification'] ?? 0);

            if ($role === 'acheteur') {
                $stmt = $pdo->prepare("UPDATE notification SET lue = 1 WHERE idNotification = ? AND idAcheteur = ?");
                $stmt->execute([$idNotification, $idUser]);
            } elseif ($role === 'vendeur') {
                $stmt = $pdo->prepare("UPDATE notification SET lue = 1 WHERE idNotification = ? AND idVendeur = ?");
                $stmt->execute([$idNotification, $idUser]);
            } else {
                $stmt = $pdo->prepare("UPDATE notification SET lue = 1 WHERE idNotification = ?");
                $stmt->execute([$idNotification]);
            }

            $success = "Notification marquée comme lue.";
        }
    }
}

// ------------------------------------------------------------
// Charger les notifications selon le rôle
// ------------------------------------------------------------
if ($role === 'acheteur') {
    $stmt = $pdo->prepare("SELECT * FROM notification WHERE idAcheteur = ? ORDER BY idNotification DESC");
    $stmt->execute([$idUser]);
    $notifications = $stmt->fetchAll();
} elseif ($role === 'vendeur') {
    $stmt = $pdo->prepare("SELECT * FROM notification WHERE idVendeur = ? ORDER BY idNotification DESC");
    $stmt->execute([$idUser]);
    $notifications = $stmt->fetchAll();
} else {
    $stmt = $pdo->query("SELECT * FROM notification ORDER BY idNotification DESC");
    $notifications = $stmt->fetchAll();
}

renderHead('Notifications - Omnes MarketPlace');
renderNavbar('notifications');
?>

<div class="page-hero">
    <div class="container">
        <h1 class="mt-2 mb-1">Notifications</h1>
        <p style="color:rgba(255,255,255,.8);margin:0;">Messages liés à ton activité</p>
    </div>
</div>

<div class="container py-5">
    <?php if ($success): ?>
        <div class="alert alert-success"><?= h($success) ?></div>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endforeach; ?>

    <div class="card card-omnes p-4 mb-4">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="read_all">
            <button type="submit" class="btn btn-primary-omnes">Tout marquer comme lu</button>
        </form>
    </div>

    <div class="card card-omnes p-4">
        <h2 class="font-display mb-4">Liste des notifications</h2>

        <?php if (empty($notifications)): ?>
            <p class="mb-0">Aucune notification pour le moment.</p>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <div style="border-bottom:1px solid #e5e7eb;padding:16px 0;">
                    <p class="mb-1">
                        <strong><?= h($notification['type'] ?? 'notification') ?></strong>
                    </p>

                    <p class="mb-1"><?= h($notification['message'] ?? '') ?></p>

                    <p class="text-muted mb-2">
                        <?= !empty($notification['created_at']) ? formatDate($notification['created_at']) : 'Date non renseignée' ?>
                    </p>

                    <p class="mb-2">
                        État :
                        <strong><?= (int)($notification['lue'] ?? 0) === 1 ? 'Lue' : 'Non lue' ?></strong>
                    </p>

                    <?php if ((int)($notification['lue'] ?? 0) === 0): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="read_one">
                            <input type="hidden" name="idNotification" value="<?= (int)$notification['idNotification'] ?>">
                            <button type="submit" class="btn btn-outline-omnes">Marquer comme lue</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php renderFooter(); ?>