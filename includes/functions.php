<?php

declare(strict_types=1);

/**
 * Front office helper functions for AREACH.
 */

/**
 * Translation helper — loads the language file for the current language
 * and returns the translated string. Falls back to French, then to the key.
 *
 * Usage:  t('shop_title')
 *         t('shop_results', 5)   — passes args to sprintf
 */
function t(string $key, mixed ...$args): string
{
    static $translations = null;
    static $loadedCode   = null;

    // Determine the current language code from session / globals
    $code = 'fr';
    if (isset($GLOBALS['langCode']) && is_string($GLOBALS['langCode']) && $GLOBALS['langCode'] !== '') {
        $code = $GLOBALS['langCode'];
    } elseif (isset($_SESSION['lang']) && is_string($_SESSION['lang']) && $_SESSION['lang'] !== '') {
        $code = $_SESSION['lang'];
    }

    // (Re)load when the language changes between calls
    if ($translations === null || $loadedCode !== $code) {
        $langFile = __DIR__ . '/../languages/' . $code . '.php';
        if (file_exists($langFile)) {
            $translations = require $langFile;
        } else {
            $fallback = __DIR__ . '/../languages/fr.php';
            $translations = file_exists($fallback) ? require $fallback : [];
        }
        $loadedCode = $code;
    }

    $string = $translations[$key] ?? $key;

    if (!empty($args)) {
        return sprintf($string, ...$args);
    }

    return $string;
}

/**
 * Translate a size code (XS, S, M, L, XL, XXL) to the current language label.
 * Sizes not in the map are returned as-is.
 */
function translateSize(string $size): string
{
    $key = 'size_' . strtoupper(trim($size));
    $translated = t($key);
    // If the key wasn't found, t() returns the key itself — return original size
    return ($translated === $key) ? $size : $translated;
}

function baseUrl(): string
{
    return '';
}

function asset(string $path): string
{
    $path = str_replace('\\', '/', ltrim($path, '/'));
    $path = preg_replace('#/+#', '/', $path) ?? $path;
    $path = str_replace(' ', '%20', $path);

    return 'assets/' . $path;
}

function pageUrl(string $path = ''): string
{
    $path = str_replace('\\', '/', trim((string) $path));

    if ($path === '') {
        return '';
    }

    if (preg_match('#^(https?:)?//#i', $path) === 1) {
        return $path;
    }

    if (str_contains($path, '://')) {
        return $path;
    }

    $path = ltrim($path, '/');

    return str_replace(' ', '%20', preg_replace('#/+#', '/', $path));
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatCurrency(float|int|string|null $amount): string
{
    $value = (float) ($amount ?? 0);

    return number_format($value, 2, ',', ' ') . ' €';
}

function getSettings(PDO $pdo): array
{
    static $settings = null;

    if ($settings !== null) {
        return $settings;
    }

    $stmt = $pdo->query('SELECT * FROM settings LIMIT 1');
    $row = $stmt->fetch();

    $settings = $row ?: [
        'store_name' => '',
        'logo' => '',
        'email' => '',
        'telephone' => '',
        'whatsapp' => '',
        'facebook' => '',
        'instagram' => '',
        'tiktok' => '',
        'address' => '',
        'delivery_price' => 0,
        'free_delivery' => 0,
    ];

    return $settings;
}

function getActiveLanguages(PDO $pdo): array
{
    static $languages = null;

    if ($languages !== null) {
        return $languages;
    }

    $stmt = $pdo->query(
        'SELECT id_language, code, name, direction
         FROM languages
         WHERE status = 1
         ORDER BY id_language ASC'
    );

    $languages = $stmt->fetchAll();

    return $languages;
}

function getCurrentLanguage(PDO $pdo): array
{
    $languages = getActiveLanguages($pdo);
    $codes = array_column($languages, 'code');

    if (isset($_GET['lang'])) {
        $requested = strtolower(trim((string) $_GET['lang']));

        if (in_array($requested, $codes, true)) {
            $_SESSION['lang'] = $requested;
        }
    }

    $currentCode = $_SESSION['lang'] ?? 'fr';

    if (!in_array($currentCode, $codes, true)) {
        $currentCode = $codes[0] ?? 'fr';
    }

    foreach ($languages as $language) {
        if ($language['code'] === $currentCode) {
            return $language;
        }
    }

    return [
        'id_language' => 1,
        'code' => 'fr',
        'name' => 'Français',
        'direction' => 'LTR',
    ];
}

function languageSwitchUrl(string $code): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $parts = parse_url($uri);
    $path = $parts['path'] ?? '/';
    $query = [];

    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
    }

    $query['lang'] = $code;
    $queryString = http_build_query($query);

    return $path . ($queryString !== '' ? '?' . $queryString : '');
}

function imagePath(string $folder, ?string $filename): string
{
    static $productImagePaths = null;

    if ($filename === null || trim($filename) === '') {
        return '';
    }

    $folder = trim(str_replace('\\', '/', $folder), '/');
    $filename = ltrim(str_replace('\\', '/', $filename), '/');

    $candidatePaths = [
        'assets/images/' . $folder . '/' . $filename,
        'assets/uploads/' . $folder . '/' . $filename,
    ];

    foreach ($candidatePaths as $candidatePath) {
        if (file_exists(__DIR__ . '/../' . $candidatePath)) {
            return $candidatePath;
        }
    }

    // Product creation stores images in assets/images/products/<category-id>/,
    // while product_images.image intentionally contains only the filename.
    if ($folder === 'products' && !str_contains($filename, '/')) {
        if ($productImagePaths === null) {
            $productImagePaths = [];
            $productRoot = __DIR__ . '/../assets/images/products';

            if (is_dir($productRoot)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($productRoot, FilesystemIterator::SKIP_DOTS)
                );

                foreach ($iterator as $file) {
                    if (!$file->isFile()) {
                        continue;
                    }

                    $relativePath = str_replace(
                        '\\',
                        '/',
                        substr($file->getPathname(), strlen(__DIR__ . '/../'))
                    );
                    $productImagePaths[$file->getFilename()] = $relativePath;
                }
            }
        }

        if (isset($productImagePaths[$filename])) {
            return $productImagePaths[$filename];
        }
    }

    return asset('uploads/' . $folder . '/' . $filename);
}

function isPromotionActive(array $variant): bool
{
    if (empty($variant['promotion_price']) || (float) $variant['promotion_price'] <= 0) {
        return false;
    }

    $today = date('Y-m-d');

    if (!empty($variant['promotion_start']) && $today < $variant['promotion_start']) {
        return false;
    }

    if (!empty($variant['promotion_end']) && $today > $variant['promotion_end']) {
        return false;
    }

    return true;
}

function socialUrl(string $platform, ?string $value): string
{
    $value = trim((string) $value);

    if ($value === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $value)) {
        return $value;
    }

    switch ($platform) {
        case 'whatsapp':
            return 'https://wa.me/' . preg_replace('/\D+/', '', $value);
        case 'facebook':
            return 'https://www.facebook.com/' . ltrim($value, '@/');
        case 'instagram':
            return 'https://www.instagram.com/' . ltrim($value, '@/');
        case 'tiktok':
            return 'https://www.tiktok.com/@' . ltrim($value, '@/');
        default:
            return $value;
    }
}

function phoneUrl(?string $phone): string
{
    $digits = preg_replace('/\D+/', '', (string) $phone);

    return $digits !== '' ? 'tel:+' . $digits : '';
}

function freeShippingText(array $settings): string
{
    $threshold = (float) ($settings['free_delivery'] ?? 0);

    if ($threshold <= 0) {
        return '';
    }

    return 'Livraison gratuite à partir de ' . formatCurrency($threshold);
}

function initFrontOffice(PDO $pdo): array
{
    return [
        'settings' => getSettings($pdo),
        'languages' => getActiveLanguages($pdo),
        'currentLang' => getCurrentLanguage($pdo),
    ];
}

function redirect(string $url): never
{
    $redirectUrl = $url;

    if ($redirectUrl === '' || !preg_match('#^https?://#i', $redirectUrl)) {
        $redirectUrl = pageUrl($redirectUrl);
    }

    header('Location: ' . $redirectUrl);
    exit;
}

function getActiveBanners(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT id_banner, title, subtitle, desktop_image, mobile_image,
                button_text, button_link, display_order
         FROM banners
         WHERE status = 1
         ORDER BY display_order ASC, id_banner ASC'
    );

    return $stmt->fetchAll();
}

function getHomeCategories(PDO $pdo, string $langCode): array
{
    $stmt = $pdo->prepare(
        'SELECT c.id_category, c.image, COALESCE(ct.name, ct_fr.name) AS name
         FROM categories c
         LEFT JOIN languages l ON l.code = ?
         LEFT JOIN category_translations ct ON c.id_category = ct.id_category AND ct.id_language = l.id_language
         LEFT JOIN languages l_fr ON l_fr.code = "fr"
         LEFT JOIN category_translations ct_fr ON c.id_category = ct_fr.id_category AND ct_fr.id_language = l_fr.id_language
         WHERE c.status = 1
         ORDER BY c.id_category ASC'
    );
    $stmt->execute([$langCode]);

    return $stmt->fetchAll();
}
function getProductImagesForVariants(PDO $pdo, array $variantIds): array
{
    if (empty($variantIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($variantIds), '?'));
    $stmt = $pdo->prepare(
        "SELECT id_variant, image
         FROM product_images
         WHERE id_variant IN ($placeholders)
         ORDER BY id_variant, is_primary DESC, id_image ASC"
    );
    $stmt->execute(array_values($variantIds));
    $rows = $stmt->fetchAll();

    $result = [];
    foreach ($rows as $row) {
        $result[(int) $row['id_variant']][] = $row['image'];
    }

    return $result;
}

function getNewArrivalProducts(PDO $pdo, string $langCode, int $limit = 5): array
{
    $stmt = $pdo->prepare(
        'SELECT
            p.id_product,
            COALESCE(pt.name, pt_fr.name) AS product_name,
            pv.id_variant,
            pv.price,
            pv.promotion_price,
            pv.promotion_start,
            pv.promotion_end,
            pv.discount_percentage,
            pi.image AS product_image
        FROM products p

        LEFT JOIN languages l
            ON l.code = ?

        LEFT JOIN product_translations pt
            ON pt.id_product = p.id_product
            AND pt.id_language = l.id_language

        LEFT JOIN languages l_fr
            ON l_fr.code = "fr"

        LEFT JOIN product_translations pt_fr
            ON pt_fr.id_product = p.id_product
            AND pt_fr.id_language = l_fr.id_language

        INNER JOIN (
            SELECT id_product, MIN(id_variant) AS id_variant
            FROM product_variants
            WHERE status = 1
            GROUP BY id_product
        ) first_variant
            ON first_variant.id_product = p.id_product

        INNER JOIN product_variants pv
            ON pv.id_variant = first_variant.id_variant

        ' . productImageJoinSql() . '

        WHERE p.status = 1

        ORDER BY p.created_at DESC, p.id_product DESC

        LIMIT ' . (int) $limit
    );

    $stmt->execute([$langCode]);

    return $stmt->fetchAll();
}

function getPromotionProducts(PDO $pdo, string $langCode, int $limit = 8): array
{
    $stmt = $pdo->prepare(
        'SELECT
            p.id_product,
            COALESCE(pt.name, pt_fr.name) AS product_name,
            pv.id_variant,
            pv.price,
            pv.promotion_price,
            pv.promotion_start,
            pv.promotion_end,
            pv.discount_percentage,
            pi.image AS product_image
         FROM products p
         LEFT JOIN languages l ON l.code = ?
         LEFT JOIN product_translations pt ON pt.id_product = p.id_product AND pt.id_language = l.id_language
         LEFT JOIN languages l_fr ON l_fr.code = "fr"
         LEFT JOIN product_translations pt_fr ON pt_fr.id_product = p.id_product AND pt_fr.id_language = l_fr.id_language
         INNER JOIN product_variants pv ON pv.id_variant = (
              SELECT pv2.id_variant
              FROM product_variants pv2
              WHERE pv2.id_product = p.id_product
                AND pv2.status = 1
                AND pv2.promotion_price IS NOT NULL
                AND pv2.promotion_price > 0
                AND (pv2.promotion_start IS NULL OR pv2.promotion_start <= CURDATE())
                AND (pv2.promotion_end IS NULL OR pv2.promotion_end >= CURDATE())
              ORDER BY pv2.discount_percentage DESC, pv2.id_variant ASC
              LIMIT 1
         )
         ' . productImageJoinSql() . '
         WHERE p.status = 1
         ORDER BY pv.discount_percentage DESC, p.created_at DESC
         LIMIT ' . (int) $limit
    );
    $stmt->execute([$langCode]);

    return $stmt->fetchAll();
}

function getPromotionBanner(PDO $pdo): ?array
{
    $stmt = $pdo->query(
        'SELECT id_campaign, subject, content, image, button_text, button_link
         FROM campaigns
         WHERE status = 1
         ORDER BY created_at DESC
         LIMIT 1'
    );

    $campaign = $stmt->fetch();

    return $campaign ?: null;
}

function getProductDisplayPrice(array $product): array
{
    if (isPromotionActive($product)) {
        return [
            'current' => (float) $product['promotion_price'],
            'original' => (float) $product['price'],
            'on_sale' => true,
        ];
    }

    return [
        'current' => (float) $product['price'],
        'original' => null,
        'on_sale' => false,
    ];
}

function getProductDiscountLabel(array $product): string
{
    if (!isPromotionActive($product)) {
        return '';
    }

    if (!empty($product['discount_percentage'])) {
        return '-' . (int) $product['discount_percentage'] . '%';
    }

    if ((float) $product['price'] > 0) {
        $discount = round(
            (((float) $product['price'] - (float) $product['promotion_price'])
                / (float) $product['price']) * 100
        );

        return '-' . $discount . '%';
    }

    return '';
}

function handleNewsletterSubscription(PDO $pdo, string $email): bool
{
    $email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);

    if ($email === false) {
        return false;
    }

    $stmt = $pdo->prepare('SELECT id_customer FROM customers WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $customer = $stmt->fetch();

    if ($customer) {
        $update = $pdo->prepare(
            'UPDATE customers SET newsletter = 1 WHERE id_customer = ?'
        );
        $update->execute([(int) $customer['id_customer']]);

        return true;
    }

    $insert = $pdo->prepare(
        'INSERT INTO customers (nom, prenom, email, password, newsletter, status)
         VALUES (?, ?, ?, ?, 1, 1)'
    );

    return $insert->execute([
        'Newsletter',
        'Abonné',
        $email,
        password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
    ]);
}

function productUrl(int $productId): string
{
    return pageUrl('product.php?id=' . $productId);
}

/**
 * Join the primary image for a selected variant, falling back to its first image.
 */
function productImageJoinSql(string $variantAlias = 'pv', string $imageAlias = 'pi'): string
{
    return 'LEFT JOIN product_images ' . $imageAlias . '
            ON ' . $imageAlias . '.id_image = (
                SELECT pi_choice.id_image
                FROM product_images pi_choice
                WHERE pi_choice.id_variant = ' . $variantAlias . '.id_variant
                ORDER BY CASE WHEN pi_choice.is_primary = 1 THEN 0 ELSE 1 END,
                         pi_choice.id_image ASC
                LIMIT 1
            )';
}

function getShopProducts(
    PDO $pdo,
    string $langCode,
    ?int $categoryId = null,
    ?string $search = null,
    ?int $limit = null
): array {

    $sql = 'SELECT
            p.id_product,
            COALESCE(pt.name, pt_fr.name) AS product_name,
            pv.id_variant,
            pv.price,
            pv.promotion_price,
            pv.promotion_start,
            pv.promotion_end,
            pv.discount_percentage,
            pi.image AS product_image
        FROM products p

        LEFT JOIN languages l
            ON l.code = ?

        LEFT JOIN product_translations pt
            ON pt.id_product = p.id_product
            AND pt.id_language = l.id_language

        LEFT JOIN languages l_fr
            ON l_fr.code = "fr"

        LEFT JOIN product_translations pt_fr
            ON pt_fr.id_product = p.id_product
            AND pt_fr.id_language = l_fr.id_language

        INNER JOIN (
            SELECT id_product, MIN(id_variant) AS id_variant
            FROM product_variants
            WHERE status = 1
            GROUP BY id_product
        ) first_variant
            ON first_variant.id_product = p.id_product

        INNER JOIN product_variants pv
            ON pv.id_variant = first_variant.id_variant

        ' . productImageJoinSql() . '

        WHERE p.status = 1';

    $params = [$langCode];

    if ($categoryId !== null && $categoryId > 0) {
        $sql .= ' AND p.id_category = ?';
        $params[] = $categoryId;
    }

    if ($search !== null && trim($search) !== '') {
        $sql .= ' AND COALESCE(pt.name, pt_fr.name) LIKE ?';
        $params[] = '%' . trim($search) . '%';
    }

    $sql .= ' ORDER BY p.created_at DESC, p.id_product DESC';

    if ($limit !== null && $limit > 0) {
        $sql .= ' LIMIT ' . (int) $limit;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function getProductById(PDO $pdo, int $productId, string $langCode): ?array
{
    $stmt = $pdo->prepare(
        'SELECT
            p.id_product,
            p.id_category,
            p.reference,
            COALESCE(pt.name, pt_fr.name) AS product_name,
            COALESCE(pt.description, pt_fr.description) AS description,
            COALESCE(ct.name, ct_fr.name) AS category_name,
            pi.image AS product_image
         FROM products p
         LEFT JOIN languages l ON l.code = ?
         LEFT JOIN product_translations pt ON pt.id_product = p.id_product AND pt.id_language = l.id_language
         LEFT JOIN languages l_fr ON l_fr.code = "fr"
         LEFT JOIN product_translations pt_fr ON pt_fr.id_product = p.id_product AND pt_fr.id_language = l_fr.id_language
         LEFT JOIN category_translations ct ON p.id_category = ct.id_category
            AND ct.id_language = l.id_language
         LEFT JOIN category_translations ct_fr ON p.id_category = ct_fr.id_category
            AND ct_fr.id_language = l_fr.id_language
         LEFT JOIN product_variants pv ON pv.id_variant = (
             SELECT pv_image.id_variant
             FROM product_variants pv_image
             WHERE pv_image.id_product = p.id_product AND pv_image.status = 1
             ORDER BY pv_image.id_variant ASC
             LIMIT 1
         )
         ' . productImageJoinSql() . '
         WHERE p.id_product = ? AND p.status = 1
         LIMIT 1'
    );
    $stmt->execute([$langCode, $productId]);
    $product = $stmt->fetch();

    return $product ?: null;
}

function getRelatedProducts(
    PDO $pdo,
    int $productId,
    int $categoryId,
    string $langCode,
    int $limit = 4
): array {
    $stmt = $pdo->prepare(
        'SELECT
            p.id_product,
            COALESCE(pt.name, pt_fr.name) AS product_name,
            pv.id_variant,
            pv.price,
            pv.promotion_price,
            pv.promotion_start,
            pv.promotion_end,
            pv.discount_percentage,
            pi.image AS product_image
         FROM products p
         LEFT JOIN languages l ON l.code = ?
         LEFT JOIN product_translations pt ON pt.id_product = p.id_product AND pt.id_language = l.id_language
         LEFT JOIN languages l_fr ON l_fr.code = "fr"
         LEFT JOIN product_translations pt_fr ON pt_fr.id_product = p.id_product AND pt_fr.id_language = l_fr.id_language
         INNER JOIN product_variants pv ON pv.id_variant = (
             SELECT pv_related.id_variant
             FROM product_variants pv_related
             WHERE pv_related.id_product = p.id_product AND pv_related.status = 1
             ORDER BY pv_related.id_variant ASC
             LIMIT 1
         )
         ' . productImageJoinSql() . '
         WHERE p.status = 1
           AND p.id_category = ?
           AND p.id_product <> ?
         ORDER BY p.created_at DESC
         LIMIT ' . (int) $limit
    );
    $stmt->execute([$langCode, $categoryId, $productId]);

    return $stmt->fetchAll();
}

function getProductVariants(PDO $pdo, int $productId): array
{
    $colorStmt = $pdo->prepare(
        'SELECT DISTINCT
            pv.color_name
         FROM product_variants pv
         WHERE pv.id_product = ? AND pv.status = 1
         ORDER BY pv.color_name ASC'
    );
    $colorStmt->execute([$productId]);
    $colors = $colorStmt->fetchAll();

    $variantStmt = $pdo->prepare(
        'SELECT DISTINCT
            pv.id_variant,
            pv.color_name,
            pv.color_code,
            pv.price,
            pv.promotion_price,
            pv.promotion_start,
            pv.promotion_end,
            pv.discount_percentage,
            pv.stock,
            pv.status,
            pvs.size
         FROM product_variants pv
         INNER JOIN product_variant_sizes pvs ON pvs.id_variant = pv.id_variant
         WHERE pv.id_product = ?
           AND pv.status = 1
           AND pv.color_name <=> ?
         ORDER BY pv.id_variant ASC, pvs.size ASC'
    );

    $imageStmt = $pdo->prepare(
        'SELECT
            pi.image,
            MAX(pi.is_primary) AS is_primary,
            MIN(pi.id_image) AS image_order
         FROM product_images pi
         INNER JOIN product_variants pv ON pv.id_variant = pi.id_variant
         WHERE pv.id_product = ?
           AND pv.status = 1
           AND pv.color_name <=> ?
         GROUP BY pi.image
         ORDER BY is_primary DESC, image_order ASC'
    );

    $variants = [];

    foreach ($colors as $color) {
        $colorName = $color['color_name'];
        $variantStmt->execute([$productId, $colorName]);
        $sizeRows = $variantStmt->fetchAll();

        if (!$sizeRows) {
            continue;
        }

        $sizeOptions = [];
        $seenSizes = [];
        $seenVariants = [];
        $totalStock = 0;

        foreach ($sizeRows as $sizeRow) {
            $size = trim((string) $sizeRow['size']);
            $sizeKey = strtoupper($size);
            $variantId = (int) $sizeRow['id_variant'];

            if (!isset($seenVariants[$variantId])) {
                $totalStock += max(0, (int) $sizeRow['stock']);
                $seenVariants[$variantId] = true;
            }

            if ($size === '' || isset($seenSizes[$sizeKey])) {
                continue;
            }

            $seenSizes[$sizeKey] = true;
            $sizeOptions[] = [
                'id_variant' => $variantId,
                'size' => $size,
                'stock' => max(0, (int) $sizeRow['stock']),
                'price' => $sizeRow['price'],
                'promotion_price' => $sizeRow['promotion_price'],
                'promotion_start' => $sizeRow['promotion_start'],
                'promotion_end' => $sizeRow['promotion_end'],
                'discount_percentage' => $sizeRow['discount_percentage'],
            ];
        }

        if (!$sizeOptions) {
            continue;
        }

        $defaultSize = null;
        foreach ($sizeOptions as $sizeOption) {
            if ($sizeOption['stock'] > 0) {
                $defaultSize = $sizeOption;
                break;
            }
        }
        $defaultSize ??= $sizeOptions[0];

        $imageStmt->execute([$productId, $colorName]);
        $images = array_map(
            static fn (array $image): array => [
                'image' => $image['image'],
                'is_primary' => (int) $image['is_primary'],
            ],
            $imageStmt->fetchAll()
        );

        $variants[] = [
            'id_variant' => $defaultSize['id_variant'],
            'color_name' => $colorName,
            'color_code' => $sizeRows[0]['color_code'],
            'price' => $defaultSize['price'],
            'promotion_price' => $defaultSize['promotion_price'],
            'promotion_start' => $defaultSize['promotion_start'],
            'promotion_end' => $defaultSize['promotion_end'],
            'discount_percentage' => $defaultSize['discount_percentage'],
            'stock' => $totalStock,
            'status' => 1,
            'sizes' => array_column($sizeOptions, 'size'),
            'size_options' => $sizeOptions,
            'images' => $images,
        ];
    }

    return $variants;
}

function getCartItems(): array
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return [];
    }

    return $_SESSION['cart'];
}

function getCartCount(): int
{
    $items = getCartItems();

    return array_sum(array_column($items, 'quantity'));
}

function getCartTotal(): float
{
    $total = 0.0;

    foreach (getCartItems() as $item) {
        $total += ((float) ($item['price'] ?? 0)) * ((int) ($item['quantity'] ?? 0));
    }

    return $total;
}

function addToCart(array $item): void
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $key = ($item['id_variant'] ?? 0) . '-' . ($item['size'] ?? '');

    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['quantity'] += (int) ($item['quantity'] ?? 1);
    } else {
        $_SESSION['cart'][$key] = $item;
    }
}

function removeFromCart(string $key): void
{
    if (isset($_SESSION['cart'][$key])) {
        unset($_SESSION['cart'][$key]);
    }
}

function clearCart(): void
{
    $_SESSION['cart'] = [];
}

function getShopEffectivePriceSql(string $alias = 'pv'): string
{
    return 'CASE
        WHEN ' . $alias . '.promotion_price IS NOT NULL
            AND ' . $alias . '.promotion_price > 0
            AND (' . $alias . '.promotion_start IS NULL OR CURDATE() >= ' . $alias . '.promotion_start)
            AND (' . $alias . '.promotion_end IS NULL OR CURDATE() <= ' . $alias . '.promotion_end)
        THEN ' . $alias . '.promotion_price
        ELSE ' . $alias . '.price
    END';
}

function getShopVariantSubquery(): string
{
    return '(
        SELECT pv2.id_variant
        FROM product_variants pv2
        WHERE pv2.id_product = p.id_product AND pv2.status = 1
        ORDER BY ' . getShopEffectivePriceSql('pv2') . ' ASC, pv2.id_variant ASC
        LIMIT 1
    )';
}

function getShopProductsListing(PDO $pdo, string $langCode, array $options = []): array
{
    $categoryId = isset($options['category']) ? (int) $options['category'] : 0;
    $search = trim((string) ($options['search'] ?? ''));
    $promoOnly = !empty($options['promo']);
    $sort = (string) ($options['sort'] ?? 'newest');
    $page = max(1, (int) ($options['page'] ?? 1));
    $perPage = max(1, (int) ($options['per_page'] ?? 12));

    $allowedSorts = ['newest', 'price_asc', 'price_desc'];

    if (!in_array($sort, $allowedSorts, true)) {
        $sort = 'newest';
    }

    $effectivePrice = getShopEffectivePriceSql();
    $variantSubquery = getShopVariantSubquery();
    $translationJoins =
        'LEFT JOIN languages l ON l.code = ?
         LEFT JOIN product_translations pt ON pt.id_product = p.id_product AND pt.id_language = l.id_language
         LEFT JOIN languages l_fr ON l_fr.code = "fr"
         LEFT JOIN product_translations pt_fr ON pt_fr.id_product = p.id_product AND pt_fr.id_language = l_fr.id_language';

    $where = ['p.status = 1'];
    $params = [$langCode];

    if ($categoryId > 0) {
        $where[] = 'p.id_category = ?';
        $params[] = $categoryId;
    }

    if ($search !== '') {
        $where[] = 'COALESCE(pt.name, pt_fr.name) LIKE ?';
        $params[] = '%' . $search . '%';
    }

    if ($promoOnly) {
        $where[] = 'EXISTS (
            SELECT 1
            FROM product_variants pv_promo
            WHERE pv_promo.id_product = p.id_product
              AND pv_promo.status = 1
              AND pv_promo.promotion_price IS NOT NULL
              AND pv_promo.promotion_price > 0
              AND (pv_promo.promotion_start IS NULL OR CURDATE() >= pv_promo.promotion_start)
              AND (pv_promo.promotion_end IS NULL OR CURDATE() <= pv_promo.promotion_end)
        )';
    }

    $whereSql = implode(' AND ', $where);

    $countSql = 'SELECT COUNT(DISTINCT p.id_product)
        FROM products p
        ' . $translationJoins . '
        INNER JOIN product_variants pv ON pv.id_variant = ' . $variantSubquery . '
        WHERE ' . $whereSql;

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    $totalPages = max(1, (int) ceil($total / $perPage));

    if ($page > $totalPages) {
        $page = $totalPages;
    }

    $offset = ($page - 1) * $perPage;

    $orderBy = match ($sort) {
        'price_asc' => $effectivePrice . ' ASC, p.created_at DESC',
        'price_desc' => $effectivePrice . ' DESC, p.created_at DESC',
        default => 'p.created_at DESC, p.id_product DESC',
    };

    $sql = 'SELECT
            p.id_product,
            p.created_at,
            COALESCE(pt.name, pt_fr.name) AS product_name,
            pv.id_variant,
            pv.price,
            pv.promotion_price,
            pv.promotion_start,
            pv.promotion_end,
            pv.discount_percentage,
            pv.stock AS variant_stock,
            pi.image AS product_image,
            (
                SELECT COALESCE(SUM(pv_stock.stock), 0)
                FROM product_variants pv_stock
                WHERE pv_stock.id_product = p.id_product AND pv_stock.status = 1
            ) AS total_stock
        FROM products p
        ' . $translationJoins . '
        INNER JOIN product_variants pv ON pv.id_variant = ' . $variantSubquery . '
        ' . productImageJoinSql() . '
        WHERE ' . $whereSql . '
        ORDER BY ' . $orderBy . '
        LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return [
        'products' => $stmt->fetchAll(),
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
    ];
}

function shopUrl(array $params = []): string
{
    $query = [];

    foreach (['q', 'category', 'promo', 'sort', 'page'] as $key) {
        if (!array_key_exists($key, $params)) {
            continue;
        }

        $value = $params[$key];

        if ($value === null || $value === '' || $value === false) {
            continue;
        }

        $query[$key] = $value;
    }

    $url = pageUrl('shop.php');

    if ($query === []) {
        return $url;
    }

    return $url . '?' . http_build_query($query);
}

function getProductStockStatus(array $product): string
{
    $totalStock = (int) ($product['total_stock'] ?? 0);

    return $totalStock > 0 ? t('product_in_stock') : t('product_out_stock');
}

function isProductInStock(array $product): bool
{
    return (int) ($product['total_stock'] ?? 0) > 0;
}
