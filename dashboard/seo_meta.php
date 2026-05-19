<?php
// /kanav/dashboard/seo_meta.php
// Safe meta helper for admin/frontend usage.
// Exposes: kh_build_meta(mysqli $conn, string $current_filename, string $fallback_title='', string $fallback_desc='', string $fallback_canonical='')

/* ---------- URL helpers ---------- */
if (!function_exists('kh_base_url')) {
  function kh_base_url(): string {
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return rtrim($proto.$host, '/');
  }
}

if (!function_exists('kh_abs_url')) {
  function kh_abs_url(string $rel): string {
    if ($rel === '') return kh_base_url() . '/';
    if (preg_match('~^https?://~i', $rel)) return $rel;
    return kh_base_url() . '/' . ltrim($rel, '/');
  }
}

/* ---------- Column detection (works with old/new schemas) ---------- */
if (!function_exists('kh_table_has_column')) {
  function kh_table_has_column(mysqli $conn, string $table, string $col): bool {
    try {
      $col = $conn->real_escape_string($col);
      $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
      return $res && $res->num_rows > 0;
    } catch (Throwable $e) { return false; }
  }
}

/* ---------- DB fetch (safe) ---------- */
if (!function_exists('kh_fetch_site_meta')) {
  function kh_fetch_site_meta(mysqli $conn): array {
    $defaults = [
      'site_title'       => 'Kanavu Heritage | Best Heritage Home In Kerala',
      'site_description' => 'Experience a serene heritage homestay in Kerala.',
      'og_image'         => '',
    ];
    try {
      $res = $conn->query("SELECT site_title, site_description, og_image FROM seo_global WHERE id=1");
      if ($res && ($row = $res->fetch_assoc())) {
        return [
          'site_title'       => $row['site_title']       ?? $defaults['site_title'],
          'site_description' => $row['site_description'] ?? $defaults['site_description'],
          'og_image'         => $row['og_image']         ?? $defaults['og_image'],
        ];
      }
    } catch (Throwable $e) { /* fall back to defaults */ }
    return $defaults;
  }
}

if (!function_exists('kh_slug_from_filename')) {
  function kh_slug_from_filename(string $filename): string {
    $map = [
      'index'            => 'home',
      'about'            => 'about',
      'accommodation'    => 'accommodation',
      'outdoor'          => 'outdoor',
      'amenities'        => 'amenities',
      'packages'         => 'packages',
      'dining'           => 'dining',
      'experiences'      => 'experiences',
      'vicinity'         => 'vicinity',
      'photo'          => 'gallery',
      'video.php'            => 'video',
      'faq'              => 'faq',
      'contact'          => 'contact',
      'terms&conditions.php' => 'terms',
    ];
    $filename = strtolower($filename);
    return $map[$filename] ?? preg_replace('~[^a-z0-9\-]+~', '-', $filename);
  }
}

if (!function_exists('kh_fetch_page_meta_by_slug')) {
  function kh_fetch_page_meta_by_slug(mysqli $conn, string $slug): ?array {
    // Detect column set once and cache
    static $cols = null;
    if ($cols === null) {
      $has_meta_title       = kh_table_has_column($conn, 'seo_pages', 'meta_title');
      $has_meta_description = kh_table_has_column($conn, 'seo_pages', 'meta_description');
      $has_canonical_url    = kh_table_has_column($conn, 'seo_pages', 'canonical_url');
      $has_title            = kh_table_has_column($conn, 'seo_pages', 'title');
      $has_description      = kh_table_has_column($conn, 'seo_pages', 'description');
      $has_canonical        = kh_table_has_column($conn, 'seo_pages', 'canonical');

      // Build SELECT fields with aliases to unified keys
      $f_title = $has_meta_title       ? 'meta_title'       : ($has_title       ? 'title'       : "''");
      $f_desc  = $has_meta_description ? 'meta_description' : ($has_description ? 'description' : "''");
      $f_canon = $has_canonical_url    ? 'canonical_url'    : ($has_canonical   ? 'canonical'   : "''");

      $cols = [
        'f_title' => $f_title,
        'f_desc'  => $f_desc,
        'f_canon' => $f_canon,
        // og_image has been consistent in your code; still guard it
        'has_og'  => kh_table_has_column($conn, 'seo_pages', 'og_image'),
      ];
    }

    try {
      $sql = "SELECT
                {$cols['f_title']}  AS meta_title,
                {$cols['f_desc']}   AS meta_description,
                {$cols['f_canon']}  AS canonical_url" .
              ($cols['has_og'] ? ", og_image" : ", '' AS og_image") .
             " FROM seo_pages WHERE page_slug=? LIMIT 1";
      $stmt = $conn->prepare($sql);
      if (!$stmt) return null;
      $stmt->bind_param('s',$slug);
      $stmt->execute();
      $res = $stmt->get_result();
      $row = $res ? $res->fetch_assoc() : null;
      $stmt->close();
      return $row ?: null;
    } catch (Throwable $e) {
      return null;
    }
  }
}

/* ---------- META builder (fully guarded) ---------- */
if (!function_exists('kh_build_meta')) {
  function kh_build_meta(mysqli $conn, string $current_filename, string $fallback_title = '', string $fallback_desc = '', string $fallback_canonical = ''): array {
    // Site defaults
    $site = kh_fetch_site_meta($conn);

    // Page data (may be null)
    $slug = kh_slug_from_filename($current_filename);
    $page = kh_fetch_page_meta_by_slug($conn, $slug);
    if (!is_array($page)) $page = [];

    // Title
    $title = $page['meta_title']
          ?? ($site['site_title']
          ?: ($fallback_title !== '' ? $fallback_title : 'Kanavu Heritage | Best Heritage Home In Kerala'));

    // Description
    $description = $page['meta_description']
                ?? ($site['site_description']
                ?: ($fallback_desc !== '' ? $fallback_desc : 'Experience a serene heritage homestay in Kerala.'));

    // Canonical
    $canonical_rel = $page['canonical_url']
                  ?? ($fallback_canonical !== '' ? $fallback_canonical : '/'.$current_filename);
    $canonical_abs = kh_abs_url($canonical_rel);

    // OG image
    $og_abs = '';
    if (!empty($page['og_image'])) {
      $og = trim((string)$page['og_image']);
      $og_abs = preg_match('~^https?://~i', $og) ? $og : kh_abs_url(ltrim($og,'/'));
    } elseif (!empty($site['og_image'])) {
      $og_abs = kh_abs_url('uploads/seo/'.$site['og_image']);
    } else {
      $og_abs = kh_abs_url('img/og-default.jpg'); // ensure this exists or change path
    }

    return [
      'title'       => $title,
      'description' => $description,
      'canonical'   => $canonical_abs,
      'og_image'    => $og_abs,
    ];
  }
}
