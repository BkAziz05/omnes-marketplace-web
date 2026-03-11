<?php
// ============================================================
// delete-article.php
// Suppression d'un article par le vendeur connecté
// - le vendeur ne peut supprimer que ses propres articles
// - supprime d'abord les photos liées
// - supprime ensuite l'article
// ============================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

requireLogin();

$pdo = db();

// ------------------------------------------------------------
// 1) Vérifier que l'utilisateur connecté est bien vendeur
// ------------------------------------------------------------
if (currentUserRole() !== 'vendeur') {
    setFlash('error', 'Accès refusé.');
    redirect(BASE_URL . '/login.php');
}

$idVendeur = currentUserId();
$idArticle = (int)($_GET['id'] ?? $_POST['idArticle'] ?? 0);

if ($idArticle <= 0) {
    setFlash('error', 'Article invalide.');
    redirect(BASE_URL . '/seller.php');
}

// ------------------------------------------------------------
// 2) Vérifier que l'article appartient bien au vendeur connecté
// ------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT idArticle, nom
    FROM article
    WHERE idArticle = ? AND idVendeur = ?
    LIMIT 1
");
$stmt->execute([$idArticle, $idVendeur]);
$article = $stmt->fetch();

if (!$article) {
    setFlash('error', 'Article introuvable ou non autorisé.');
    redirect(BASE_URL . '/seller.php');
}

// ------------------------------------------------------------
// 3) Suppression
// ------------------------------------------------------------
try {
    $pdo->beginTransaction();

    // Supprimer les photos liées à l'article
    $stmt = $pdo->prepare("DELETE FROM photo_article WHERE idArticle = ?");
    $stmt->execute([$idArticle]);

    // Supprimer l'article
    $stmt = $pdo->prepare("DELETE FROM article WHERE idArticle = ? AND idVendeur = ?");
    $stmt->execute([$idArticle, $idVendeur]);

    $pdo->commit();

    setFlash('success', 'Article supprimé avec succès.');
    redirect(BASE_URL . '/seller.php');
} catch (Exception $e) {
    $pdo->rollBack();
    setFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
    redirect(BASE_URL . '/seller.php');
}