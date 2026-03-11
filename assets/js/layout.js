/**
 * ============================================================
 * layout.js — Omnes MarketPlace
 * Fichier central pour la navbar et le footer partagés.
 * Utilisé sur toutes les pages via renderNavbar() et renderFooter().
 * ============================================================
 */

/**
 * renderNavbar(activePage)
 * Génère et injecte la barre de navigation en haut du <body>.
 * @param {string} activePage - Identifiant de la page active
 *   Valeurs possibles : 'home', 'articles', 'cart', 'notifications', ''
 */
function renderNavbar(activePage) {

    /* --- Logo texte stylisé : deux parties avec typographie contrastée --- */
    const logoHTML = `
        <a href="index.html" class="navbar-brand d-flex align-items-center gap-2 text-decoration-none" style="gap:0!important;">
            <!-- Partie "Omnes" en gras avec couleur primaire -->
            <span style="
                font-family:'Playfair Display', serif;
                font-weight:700;
                font-size:1.35rem;
                color:var(--primary);
                letter-spacing:-0.5px;
                line-height:1;
            ">Omnes</span>
            <!-- Partie "MarketPlace" en léger avec teinte foncée -->
            <span style="
                font-family:'DM Sans', sans-serif;
                font-weight:300;
                font-size:1.1rem;
                color:var(--primary-dark);
                letter-spacing:1px;
                text-transform:uppercase;
                line-height:1;
                margin-left:4px;
            ">MarketPlace</span>
        </a>`;

    /* --- Liens de navigation principaux avec état actif --- */
    const navLinks = [
        { id: 'home',          href: 'index.html',         label: 'Accueil',     icon: 'bi-house' },
        { id: 'articles',      href: 'articles.html',      label: 'Parcourir',   icon: 'bi-grid' },
    ];

    const linksHTML = navLinks.map(link => `
        <li class="nav-item">
            <a class="nav-link ${activePage === link.id ? 'active' : ''}"
               href="${link.href}"
               style="font-size:.875rem; font-weight:${activePage === link.id ? '700' : '500'}; color:${activePage === link.id ? 'var(--primary)' : 'var(--text)'};">
                <i class="bi ${link.icon} me-1"></i>${link.label}
            </a>
        </li>`).join('');

    /* --- Icônes d'actions (notifications, panier, compte) --- */
    const actionsHTML = `
        <!-- Lien Notifications avec badge -->
        <a href="notifications.html" class="position-relative d-flex align-items-center justify-content-center"
           style="width:38px;height:38px;border-radius:50%;background:${activePage === 'notifications' ? 'var(--primary-pale)' : 'var(--gray-100)'};text-decoration:none;color:var(--text);"
           title="Notifications">
            <i class="bi bi-bell" style="font-size:1rem;${activePage === 'notifications' ? 'color:var(--primary);' : ''}"></i>
            <!-- Badge nombre de notifications non lues -->
            <span class="position-absolute top-0 end-0" style="width:16px;height:16px;background:var(--accent);border-radius:50%;font-size:.6rem;font-weight:700;color:#333;display:flex;align-items:center;justify-content:center;border:2px solid white;">3</span>
        </a>

        <!-- Lien Panier avec badge -->
        <a href="cart.html" class="position-relative d-flex align-items-center justify-content-center"
           style="width:38px;height:38px;border-radius:50%;background:${activePage === 'cart' ? 'var(--primary-pale)' : 'var(--gray-100)'};text-decoration:none;color:var(--text);"
           title="Mon panier">
            <i class="bi bi-bag" style="font-size:1rem;${activePage === 'cart' ? 'color:var(--primary);' : ''}"></i>
            <!-- Badge nombre d'articles dans le panier -->
            <span class="position-absolute top-0 end-0" style="width:16px;height:16px;background:var(--primary);border-radius:50%;font-size:.6rem;font-weight:700;color:white;display:flex;align-items:center;justify-content:center;border:2px solid white;">3</span>
        </a>

        <!-- Lien Mon compte -->
        <a href="account.html"
           style="background:var(--primary);color:white;padding:7px 16px;border-radius:50px;text-decoration:none;font-size:.8rem;font-weight:600;display:flex;align-items:center;gap:6px;"
           title="Mon compte">
            <i class="bi bi-person-fill"></i>Mon compte
        </a>`;

    /* --- Assemblage complet de la navbar Bootstrap --- */
    const navbarHTML = `
    <!-- 
         NAVBAR PRINCIPALE — partagée sur toutes les pages
         Sticky en haut, avec logo, liens et actions.
     -->
    <nav class="navbar navbar-expand-lg sticky-top" style="background:white;border-bottom:1px solid var(--border);box-shadow:0 2px 12px rgba(14,140,140,.07);z-index:1000;">
        <div class="container">

            <!-- LOGO texte stylisé "Omnes MarketPlace" -->
            ${logoHTML}

            <!-- Bouton hamburger pour mobile -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-label="Menu">
                <i class="bi bi-list" style="font-size:1.5rem;color:var(--primary);"></i>
            </button>

            <!-- Liens et actions (collapsibles sur mobile) -->
            <div class="collapse navbar-collapse" id="navbarMain">
                <!-- Liens de navigation centrés -->
                <ul class="navbar-nav mx-auto gap-1">
                    ${linksHTML}
                </ul>
                <!-- Actions à droite : notifications, panier, compte -->
                <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                    ${actionsHTML}
                </div>
            </div>
        </div>
    </nav>`;

    /* --- Injection dans le DOM en début de <body> --- */
    document.body.insertAdjacentHTML('afterbegin', navbarHTML);

    /* --- Chargement Bootstrap JS (si pas déjà chargé) --- */
    if (!window._bootstrapLoaded) {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js';
        document.head.appendChild(script);
        window._bootstrapLoaded = true;
    }
}

/**
 * renderFooter()
 * Génère et injecte le pied de page en bas du <body>.
 * Contient les liens utiles, infos légales et copyright.
 */
function renderFooter() {
    const footerHTML = `
    <!-- 
         FOOTER — partagé sur toutes les pages
         Liens utiles, modes d'achat, légal, copyright.
     -->
    <footer style="background:var(--primary-dark);color:rgba(255,255,255,.85);padding:56px 0 24px;margin-top:60px;">
        <div class="container">
            <div class="row g-4 mb-5">

                <!-- Colonne 1 : Logo et description de la plateforme -->
                <div class="col-lg-4">
                    <!-- Logo texte stylisé en version footer (fond sombre) -->
                    <div style="display:flex;align-items:center;margin-bottom:16px;">
                        <span style="font-family:'Playfair Display',serif;font-weight:700;font-size:1.4rem;color:white;letter-spacing:-0.5px;">Omnes</span>
                        <span style="font-family:'DM Sans',sans-serif;font-weight:300;font-size:1rem;color:rgba(255,255,255,.6);letter-spacing:1px;text-transform:uppercase;margin-left:4px;">MarketPlace</span>
                    </div>
                    <p style="color:rgba(255,255,255,.6);font-size:.875rem;line-height:1.7;margin-bottom:20px;">
                        La marketplace de confiance de la communauté Omnes Education. Achetez, vendez et enchérissez en toute sécurité.
                    </p>
                    <!-- Statistiques rapides -->
                    <div class="d-flex gap-3">
                        <div style="text-align:center;">
                            <div style="font-family:'Playfair Display',serif;font-weight:700;font-size:1.1rem;color:var(--accent);">248</div>
                            <div style="font-size:.72rem;color:rgba(255,255,255,.5);">Articles</div>
                        </div>
                        <div style="text-align:center;">
                            <div style="font-family:'Playfair Display',serif;font-weight:700;font-size:1.1rem;color:var(--accent);">32</div>
                            <div style="font-size:.72rem;color:rgba(255,255,255,.5);">Vendeurs</div>
                        </div>
                        <div style="text-align:center;">
                            <div style="font-family:'Playfair Display',serif;font-weight:700;font-size:1.1rem;color:var(--accent);">1 204</div>
                            <div style="font-size:.72rem;color:rgba(255,255,255,.5);">Membres</div>
                        </div>
                    </div>
                </div>

                <!-- Colonne 2 : Navigation rapide -->
                <div class="col-6 col-lg-2 offset-lg-1">
                    <h6 style="font-weight:700;color:white;margin-bottom:16px;font-size:.85rem;text-transform:uppercase;letter-spacing:.5px;">Navigation</h6>
                    <ul style="list-style:none;padding:0;margin:0;">
                        <li style="margin-bottom:10px;"><a href="index.html"    style="color:rgba(255,255,255,.6);text-decoration:none;font-size:.875rem;transition:color .2s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,.6)'">Accueil</a></li>
                        <li style="margin-bottom:10px;"><a href="articles.html" style="color:rgba(255,255,255,.6);text-decoration:none;font-size:.875rem;" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,.6)'">Tous les articles</a></li>
                        <li style="margin-bottom:10px;"><a href="auction.html"  style="color:rgba(255,255,255,.6);text-decoration:none;font-size:.875rem;" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,.6)'">Enchères en cours</a></li>
                        <li style="margin-bottom:10px;"><a href="account.html"  style="color:rgba(255,255,255,.6);text-decoration:none;font-size:.875rem;" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,.6)'">Mon compte</a></li>
                    </ul>
                </div>

                <!-- Colonne 3 : Modes d'achat expliqués -->
                <div class="col-6 col-lg-2">
                    <h6 style="font-weight:700;color:white;margin-bottom:16px;font-size:.85rem;text-transform:uppercase;letter-spacing:.5px;">Acheter</h6>
                    <ul style="list-style:none;padding:0;margin:0;">
                        <li style="margin-bottom:10px;"><span style="color:rgba(255,255,255,.6);font-size:.875rem;"> Achat immédiat</span></li>
                        <li style="margin-bottom:10px;"><span style="color:rgba(255,255,255,.6);font-size:.875rem;"> Négociation</span></li>
                        <li style="margin-bottom:10px;"><span style="color:rgba(255,255,255,.6);font-size:.875rem;"> Enchères</span></li>
                    </ul>
                </div>

                <!-- Colonne 4 : Légal et sécurité -->
                <div class="col-lg-3">
                    <h6 style="font-weight:700;color:white;margin-bottom:16px;font-size:.85rem;text-transform:uppercase;letter-spacing:.5px;">Légal & Sécurité</h6>
                    <p style="color:rgba(255,255,255,.5);font-size:.8rem;line-height:1.6;margin-bottom:12px;">
                        Toute offre acceptée constitue un engagement légal d'achat. Paiement 100% sécurisé.
                    </p>
                    <!-- Badges de paiement acceptés -->
                    <div class="d-flex gap-2 flex-wrap">
                        <span style="background:rgba(255,255,255,.1);border-radius:4px;padding:3px 8px;font-size:.7rem;font-weight:700;color:rgba(255,255,255,.7);">VISA</span>
                        <span style="background:rgba(255,255,255,.1);border-radius:4px;padding:3px 8px;font-size:.7rem;font-weight:700;color:rgba(255,255,255,.7);">MC</span>
                        <span style="background:rgba(255,255,255,.1);border-radius:4px;padding:3px 8px;font-size:.7rem;font-weight:700;color:rgba(255,255,255,.7);">PAYPAL</span>
                        <span style="background:rgba(255,255,255,.1);border-radius:4px;padding:3px 8px;font-size:.7rem;font-weight:700;color:rgba(255,255,255,.7);">AMEX</span>
                    </div>
                </div>
            </div>

            <!-- Ligne séparatrice -->
            <hr style="border-color:rgba(255,255,255,.1);margin-bottom:20px;">

            <!-- Copyright et mention légale -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <p style="color:rgba(255,255,255,.4);font-size:.78rem;margin:0;">
                    © 2026 Omnes MarketPlace — Réservé à la communauté Omnes Education
                </p>
                <p style="color:rgba(255,255,255,.4);font-size:.78rem;margin:0;">
                    <i class="bi bi-shield-check me-1"></i>Plateforme sécurisée
                </p>
            </div>
        </div>
    </footer>`;

    /* --- Injection en fin de <body> --- */
    document.body.insertAdjacentHTML('beforeend', footerHTML);
}

/**
 * createProductPlaceholder(productName, category)
 * Génère le HTML d'un placeholder image pour un produit.
 * À remplacer par une vraie balise <img> quand les photos sont disponibles.
 *
 * @param {string} productName - Nom du produit à afficher
 * @param {string} category    - Catégorie (pour la couleur de fond)
 * @param {string} height      - Hauteur CSS du bloc (ex: "200px")
 * @returns {string} HTML du placeholder
 */
function createProductPlaceholder(productName, category = '', height = '200px') {

    /* Palette de couleurs par catégorie */
    const categoryColors = {
        'Bijoux & Montres':    ['#1a2e2e', '#0e8c8c'],
        'Art & Collections':   ['#2d1b69', '#7c3aed'],
        'Vêtements Luxe':      ['#2d1b00', '#8b4513'],
        'Électronique':        ['#1a1a2e', '#16213e'],
        'Livres & Papeterie':  ['#1a2e1a', '#2e7d32'],
        'Mobilier Antique':    ['#2e2e1a', '#7d6e2e'],
        'Accessoires Mode':    ['#2e1a2a', '#8c3d7a'],
        'Matériel Scolaire':   ['#1a1a2e', '#3d3d8c'],
    };

    /* Couleur de fond selon la catégorie, ou couleur primaire par défaut */
    const colors = categoryColors[category] || ['#0a6e6e', '#0e8c8c'];

    return `
        <!-- PLACEHOLDER IMAGE — Remplacer par <img src="..."> quand la photo est disponible -->
        <div style="
            height:${height};
            background:linear-gradient(135deg, ${colors[0]}, ${colors[1]});
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            gap:10px;
            padding:16px;
        ">
            <!-- Icône caméra indiquant qu'une image sera ajoutée -->
            <i class="bi bi-camera" style="font-size:2rem;color:rgba(255,255,255,.4);"></i>
            <!-- Nom du produit centré -->
            <span style="
                color:rgba(255,255,255,.75);
                font-size:.8rem;
                font-weight:600;
                text-align:center;
                line-height:1.3;
                max-width:90%;
                overflow:hidden;
                display:-webkit-box;
                -webkit-line-clamp:2;
                -webkit-box-orient:vertical;
            ">${productName}</span>
            <!-- Label de catégorie -->
            ${category ? `<span style="color:rgba(255,255,255,.4);font-size:.7rem;">${category}</span>` : ''}
        </div>`;
}
