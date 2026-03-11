<?php
// ============================================================
// config.php - Configuration générale du projet
// ============================================================

define('ENV', 'development');

// Nom du dossier du projet dans htdocs
define('BASE_URL', '/OMNES');

// Base de données
define('DB_HOST', 'localhost');
define('DB_PORT', '8889');
define('DB_NAME', 'omnes_marketplace');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_CHARSET', 'utf8mb4');

// Session
define('SESSION_NAME', 'omnes_session');
define('SESSION_LIFETIME', 0);

// Limite du nombre de tours de négociation
define('NEGOCIATION_MAX_TOURS', 5);