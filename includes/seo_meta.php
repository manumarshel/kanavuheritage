<?php
// includes/seo_meta.php
// Helper to build page meta from `seo_pages` table with safe fallbacks.

if (!function_exists('kh_build_meta')) {
  function kh_build_meta(mysqli $conn, string $page_slug, string $defaultTitle, string $defaultDesc, string $defaultCanonical): array {
    $out = [
      'title' => $defaultTitle,
      'description' => $defaultDesc,
      'canonical' => $defaultCanonical,
      'og_image' => '/kanav/img/og-default.jpg'
    ];

    if (!($conn instanceof mysqli)) return $out;

    // Try several likely variants to match how rows were stored in different installs
    $variants = [
      $page_slug,
      $page_slug . '.php',
      '/' . ltrim($page_slug, '/'),
      '/kanav/' . ltrim($page_slug, '/'),
      'blog/' . $page_slug,
      'blog-' . $page_slug,
    ];
    $stmt = $conn->prepare("SELECT meta_title, meta_description, canonical_url, og_image FROM seo_pages WHERE page_slug = ? LIMIT 1");
    if (!$stmt) return $out;

    foreach ($variants as $v) {
      if (!$v) continue;
      $stmt->bind_param('s', $v);
      $stmt->execute();
      $res = $stmt->get_result();
      $row = $res ? $res->fetch_assoc() : null;
      if ($row) {
        $title = trim((string)($row['meta_title'] ?? ''));
        $desc  = trim((string)($row['meta_description'] ?? ''));
        $canon = trim((string)($row['canonical_url'] ?? ''));
        $og    = trim((string)($row['og_image'] ?? ''));

        if ($title !== '') $out['title'] = $title;
        if ($desc  !== '') $out['description'] = mb_substr(strip_tags($desc), 0, 160);
        if ($canon !== '') $out['canonical'] = $canon;
        if ($og    !== '') $out['og_image'] = $og;

        break;
      }
    }

    $stmt->close();
    return $out;
  }
}
