<?php
// ============================================================
// helpers.php - Fonctions utilitaires globales
// Omnes MarketPlace
// ============================================================

/**
 * Sécuriser l'affichage d'une chaîne (XSS)
 */
function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Nettoyer les entrées utilisateur
 */
function clean(string $str): string {
    return trim(strip_tags($str));
}

/**
 * Redirection
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Réponse JSON (pour les endpoints AJAX)
 */
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Formater un prix en euros
 */
function formatPrix(float $prix): string {
    return number_format($prix, 2, ',', ' ') . ' €';
}

/**
 * Formater une date
 */
function formatDate(string $date, string $format = 'd/m/Y H:i'): string {
    return date($format, strtotime($date));
}

/**
 * Vérifier si une chaîne est un email valide
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Hasher un mot de passe
 */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Vérifier un mot de passe
 */
function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Valider la force du mot de passe
 * Exige : 8 chars min, 1 majuscule, 1 chiffre, 1 spécial
 */
function isStrongPassword(string $password): bool {
    return strlen($password) >= 8
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[0-9]/', $password)
        && preg_match('/[^a-zA-Z0-9]/', $password);
}

/**
 * Upload d'une image
 * Retourne le chemin relatif ou false en cas d'échec
 */
function uploadImage(array $file, string $destDir): string|false {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > UPLOAD_MAX_SIZE) return false;

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, UPLOAD_ALLOWED_TYPES)) return false;

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_', true) . '.' . strtolower($ext);
    $destPath = $destDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) return false;

    return $filename;
}

/**
 * Masquer un numéro de carte bancaire
 * Ex: 4111111111111111 -> **** **** **** 1111
 */
function masquerCarte(string $numero): string {
    $numero = preg_replace('/\s+/', '', $numero);
    return '**** **** **** ' . substr($numero, -4);
}

/**
 * Générer un token CSRF
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifier le token CSRF
 */
function verifyCsrf(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Paginer un tableau de résultats
 */
function paginate(int $total, int $perPage, int $currentPage): array {
    $totalPages = (int)ceil($total / $perPage);
    $offset     = ($currentPage - 1) * $perPage;
    return [
        'total'        => $total,
        'per_page'     => $perPage,
        'current_page' => $currentPage,
        'total_pages'  => $totalPages,
        'offset'       => $offset,
        'has_prev'     => $currentPage > 1,
        'has_next'     => $currentPage < $totalPages,
    ];
}


/**
 * Retourne l'URL correcte d'une image article.
 * On essaie d'abord le chemin venant de la base.
 * Si le fichier n'existe pas, on utilise une image du front en secours.
 */
function articleImageUrl(?string $dbPhotoPath, string $articleName = ''): string
{
    // 1) Si la base contient un chemin valide existant physiquement
    if (!empty($dbPhotoPath)) {
        $cleanPath = ltrim($dbPhotoPath, '/');

        // Chemin réel sur le disque
        $fullPath = __DIR__ . '/../' . $cleanPath;

        if (file_exists($fullPath)) {
            return BASE_URL . '/' . $cleanPath;
        }
    }

    // 2) Images de secours depuis le dossier assets/images
    $name = mb_strtolower($articleName);

    $fallbacks = [
        'bague'     => 'bage_or.jpg',
        'rolex'     => 'montre_tissot.jpg',
        'montre'    => 'montre_tissot.jpg',
        'hermès'    => 'vestecuir.jpg',
        'hermes'    => 'vestecuir.jpg',
        'veste'     => 'vestecuir.jpg',
        'macbook'   => 'macbook.jpg',
        'livre'     => 'colection_haryypotter.jpg',
        'livres'    => 'colection_haryypotter.jpg',
        'commode'   => 'chaise.jpg',
        'sac'       => 'sac.jpg',
        'parfum'    => 'parfum.jpg',
        'guitare'   => 'guitare.jpg',
        'nikon'     => 'appreil_photo_nikon.jpg',
        'appareil'  => 'appreilphoto.jpg',
        'tableau'   => 'tableau_aquarelle.jpg',
        'vélo'      => 'velo.jpg',
        'velo'      => 'velo.jpg',
    ];

    foreach ($fallbacks as $keyword => $fileName) {
        if (str_contains($name, $keyword)) {
            $candidate = __DIR__ . '/../assets/images/' . $fileName;
            if (file_exists($candidate)) {
                return BASE_URL . '/assets/images/' . $fileName;
            }
        }
    }

    // 3) Image par défaut si rien n'est trouvé
    return '';
}