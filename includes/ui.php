<?php
// ============================================================
// ui.php - Fonctions d'affichage communes
// Sert à réutiliser le style front sur plusieurs pages PHP
// ============================================================

require_once __DIR__ . '/helpers.php';

/**
 * Affiche le début HTML + Bootstrap + CSS du front
 */
function renderHead(string $title): void
{
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= h($title) ?></title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
        <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    </head>
    <body>
    <?php
}

/**
 * Affiche la navbar version PHP
 */
function renderNavbar(string $active = ''): void
{
    ?>
    <nav class="navbar navbar-expand-lg sticky-top" style="background:white;border-bottom:1px solid var(--border);box-shadow:0 2px 12px rgba(14,140,140,.07);z-index:1000;">
        <div class="container">
            <a href="<?= BASE_URL ?>/index.php" class="navbar-brand d-flex align-items-center text-decoration-none" style="gap:0!important;">
                <span style="font-family:'Playfair Display', serif;font-weight:700;font-size:1.35rem;color:var(--primary);letter-spacing:-0.5px;line-height:1;">Omnes</span>
                <span style="font-family:'DM Sans', sans-serif;font-weight:300;font-size:1.1rem;color:var(--primary-dark);letter-spacing:1px;text-transform:uppercase;line-height:1;margin-left:4px;">MarketPlace</span>
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <i class="bi bi-list" style="font-size:1.5rem;color:var(--primary);"></i>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav mx-auto gap-1">
                    <li class="nav-item">
                        <a class="nav-link <?= $active === 'home' ? 'active' : '' ?>" href="<?= BASE_URL ?>/index.php" style="font-size:.875rem;font-weight:<?= $active === 'home' ? '700' : '500' ?>;color:<?= $active === 'home' ? 'var(--primary)' : 'var(--text)' ?>;">
                            <i class="bi bi-house-door me-1"></i>Accueil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active === 'articles' ? 'active' : '' ?>" href="<?= BASE_URL ?>/articles.php" style="font-size:.875rem;font-weight:<?= $active === 'articles' ? '700' : '500' ?>;color:<?= $active === 'articles' ? 'var(--primary)' : 'var(--text)' ?>;">
                            <i class="bi bi-grid me-1"></i>Tout Parcourir
                        </a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                <?php if (isLoggedIn()): ?>
                    <?php $role = currentUserRole(); ?>

                    <?php if ($role === 'vendeur'): ?>
                        <a href="<?= BASE_URL ?>/notifications.php" class="position-relative d-flex align-items-center justify-content-center"
                        style="width:38px;height:38px;border-radius:50%;background:<?= $active === 'notifications' ? 'var(--primary-pale)' : 'var(--gray-100)' ?>;text-decoration:none;color:var(--text);"
                        title="Notifications">
                            <i class="bi bi-bell" style="font-size:1rem;<?= $active === 'notifications' ? 'color:var(--primary);' : '' ?>"></i>
                        </a>

                        <a href="<?= BASE_URL ?>/seller.php"
                        style="background:var(--primary);color:white;padding:7px 16px;border-radius:50px;text-decoration:none;font-size:.8rem;font-weight:600;display:flex;align-items:center;gap:6px;">
                            <i class="bi bi-shop"></i>Espace vendeur
                        </a>

                        <a href="<?= BASE_URL ?>/account.php"
                        style="background:var(--gray-100);color:var(--text);padding:7px 14px;border-radius:50px;text-decoration:none;font-size:.8rem;font-weight:600;">
                            Mon compte
                        </a>

                        <a href="<?= BASE_URL ?>/logout.php"
                        style="background:var(--gray-100);color:var(--text);padding:7px 14px;border-radius:50px;text-decoration:none;font-size:.8rem;font-weight:600;">
                            Déconnexion
                        </a>

                    <?php elseif ($role === 'admin'): ?>
                        <a href="<?= BASE_URL ?>/admin.php"
                        style="background:var(--primary);color:white;padding:7px 16px;border-radius:50px;text-decoration:none;font-size:.8rem;font-weight:600;display:flex;align-items:center;gap:6px;">
                            <i class="bi bi-shield-lock"></i>Admin
                        </a>

                        <a href="<?= BASE_URL ?>/logout.php"
                        style="background:var(--gray-100);color:var(--text);padding:7px 14px;border-radius:50px;text-decoration:none;font-size:.8rem;font-weight:600;">
                            Déconnexion
                        </a>

                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/notifications.php" class="position-relative d-flex align-items-center justify-content-center"
                        style="width:38px;height:38px;border-radius:50%;background:<?= $active === 'notifications' ? 'var(--primary-pale)' : 'var(--gray-100)' ?>;text-decoration:none;color:var(--text);"
                        title="Notifications">
                            <i class="bi bi-bell" style="font-size:1rem;<?= $active === 'notifications' ? 'color:var(--primary);' : '' ?>"></i>
                        </a>

                        <a href="<?= BASE_URL ?>/cart.php" class="position-relative d-flex align-items-center justify-content-center"
                        style="width:38px;height:38px;border-radius:50%;background:<?= $active === 'cart' ? 'var(--primary-pale)' : 'var(--gray-100)' ?>;text-decoration:none;color:var(--text);"
                        title="Mon panier">
                            <i class="bi bi-bag" style="font-size:1rem;<?= $active === 'cart' ? 'color:var(--primary);' : '' ?>"></i>
                        </a>

                        <a href="<?= BASE_URL ?>/account.php"
                        style="background:var(--primary);color:white;padding:7px 16px;border-radius:50px;text-decoration:none;font-size:.8rem;font-weight:600;display:flex;align-items:center;gap:6px;"
                        title="Mon compte">
                            <i class="bi bi-person-fill"></i><?= h($_SESSION['user_nom'] ?? 'Mon compte') ?>
                        </a>

                        <a href="<?= BASE_URL ?>/logout.php"
                        style="background:var(--gray-100);color:var(--text);padding:7px 14px;border-radius:50px;text-decoration:none;font-size:.8rem;font-weight:600;">
                            Déconnexion
                        </a>
                    <?php endif; ?>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/login.php"
                           style="background:var(--gray-100);color:var(--text);padding:7px 14px;border-radius:50px;text-decoration:none;font-size:.8rem;font-weight:600;">
                            Connexion
                        </a>
                        <a href="<?= BASE_URL ?>/register.php"
                           style="background:var(--primary);color:white;padding:7px 16px;border-radius:50px;text-decoration:none;font-size:.8rem;font-weight:600;">
                            Inscription
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <?php
}

/**
 * Affiche les messages flash
 */
function renderFlashes(array $flashes): void
{
    if (empty($flashes)) {
        return;
    }
    ?>
    <div class="container mt-3">
        <?php foreach ($flashes as $f): ?>
            <div class="alert <?= $f['type'] === 'success' ? 'alert-success' : 'alert-danger' ?>">
                <?= h($f['message']) ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

/**
 * Badge mode de vente
 */
function renderModeBadge(string $mode): string
{
    $classes = [
        'immediat' => 'badge-immediat',
        'negotiation' => 'badge-negotiation',
        'enchere' => 'badge-enchere'
    ];

    $labels = [
        'immediat' => 'Achat immédiat',
        'negotiation' => 'Négociation',
        'enchere' => 'Enchère'
    ];

    $class = $classes[$mode] ?? 'badge-regulier';
    $label = $labels[$mode] ?? $mode;

    return '<span class="mode-badge ' . $class . '">' . h($label) . '</span>';
}

/**
 * Carte article simple
 */
function renderArticleCard(array $article): void
{
    $photo = articleImageUrl($article['photo'] ?? '', $article['nom'] ?? '');
    ?>
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card card-omnes h-100">
            <?php if ($photo !== ''): ?>
                <img src="<?= h($photo) ?>" class="card-img-top" alt="<?= h($article['nom']) ?>">
            <?php else: ?>
                <div class="img-placeholder">
                    <i class="bi bi-image"></i>
                </div>
            <?php endif; ?>

            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <?= renderModeBadge($article['mode_vente']) ?>
                </div>

                <h5 class="card-title font-display mb-2"><?= h($article['nom']) ?></h5>
                <p class="text-muted mb-2"><?= h($article['categorie'] ?? $article['categorie_libelle'] ?? '') ?></p>
                <p class="price-display price-sm mb-3"><?= formatPrix((float)$article['prix_base']) ?></p>

                <a href="<?= BASE_URL ?>/article-detail.php?id=<?= (int)$article['idArticle'] ?>" class="btn btn-primary-omnes">
                    Voir l'article
                </a>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Footer commun
 */
function renderFooter(): void
{
    ?>
    <footer style="background:var(--primary-dark);color:rgba(255,255,255,.85);padding:56px 0 24px;margin-top:60px;">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-lg-5">
                    <div style="display:flex;align-items:center;margin-bottom:16px;">
                        <span style="font-family:'Playfair Display',serif;font-weight:700;font-size:1.4rem;color:white;">Omnes</span>
                        <span style="font-family:'DM Sans',sans-serif;font-weight:300;font-size:1rem;color:rgba(255,255,255,.6);letter-spacing:1px;text-transform:uppercase;margin-left:4px;">MarketPlace</span>
                    </div>
                    <p style="color:rgba(255,255,255,.6);font-size:.875rem;line-height:1.7;">
                        La marketplace de la communauté Omnes Education. Achetez, négociez et enchérissez en toute sécurité.
                    </p>
                </div>

                <div class="col-lg-3">
                    <h6 style="font-weight:700;color:white;margin-bottom:16px;">Navigation</h6>
                    <p><a href="<?= BASE_URL ?>/index.php" style="color:rgba(255,255,255,.7);text-decoration:none;">Accueil</a></p>
                    <p><a href="<?= BASE_URL ?>/articles.php" style="color:rgba(255,255,255,.7);text-decoration:none;">Tout parcourir</a></p>
                    <p><a href="<?= BASE_URL ?>/notifications.php" style="color:rgba(255,255,255,.7);text-decoration:none;">Notifications</a></p>
                </div>

                <div class="col-lg-4">
                    <h6 style="font-weight:700;color:white;margin-bottom:16px;">Infos</h6>
                    <p style="color:rgba(255,255,255,.6);font-size:.875rem;">Adresse : 10 Rue Sextius Michel, 75015 Paris</p>
                    <p style="color:rgba(255,255,255,.6);font-size:.875rem;">Email : marketplace@omnes-education.fr</p>
                    <p style="color:rgba(255,255,255,.6);font-size:.875rem;">Paiement sécurisé</p>
                </div>
            </div>

            <hr style="border-color:rgba(255,255,255,.1);">
            <p style="margin:0;color:rgba(255,255,255,.5);font-size:.85rem;">
                © <?= date('Y') ?> Omnes MarketPlace
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}