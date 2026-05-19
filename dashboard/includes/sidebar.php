<?php
// ---------- Active / open helpers ----------
$current = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

function is_active($files) {
  global $current;
  $files = is_array($files) ? $files : [$files];
  return in_array($current, $files) ? 'active' : '';
}
function is_open($files) { return is_active($files) ? 'open' : ''; }

// Safe relative URLs from /dashboard/
$BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
function url_to($file){ global $BASE; return $BASE . $file; }

// ---------- Pre-compute open states ----------
$pages_open = is_open([
  'page-home.php', 'page-about',
  'page-accommodation', 'page-outdoor', 'page-amenities', 'page-packages',
  'page-dining', 'page-experiences', 'page-vicinity',
  'page-photo', 'page-gallery-photo', 'page-gallery-video.php',
  'page-faq', 'page_contact', 'page-contact', 'page-blog-public.php', 'pages.php',
  'bookings.php' // 🔹 ensure Pages group opens on bookings page
]) !== '';

$meta_open   = is_open(['seo-global.php', 'seo-pages.php']) !== '';

// IMPORTANT: point to the page names you’re actually using:
$users_open  = is_open(['page-admin-users.php', 'page-change-password.php', 'page-settings.php']) !== '';
?>

<!-- Mobile sidebar toggle (visible on small / portrait screens) -->
<button id="sidebarToggle" class="sidebar-toggle" aria-label="Open menu" title="Open menu">☰</button>
<div id="sidebarOverlay" class="mobile-sidebar-overlay" style="display:none" aria-hidden="true"></div>
<aside class="admin-sidebar" id="adminSidebar">
  <button id="sidebarClose" class="sidebar-close" aria-label="Close menu">&times;</button>
  <div class="brand">
    <div class="brand-logo">
      <!-- Larger logo image -->
      <img src="./imgs/logo.png" alt="Kanav Heritage Logo" width="100%" height="50%"/>
    </div>
    <span class="brand-text">Kanav Heritage</span>
  </div>

  <nav class="menu">
    <!-- Dashboard -->
    <a class="menu-item <?= is_active('index') ?>" href="<?= url_to('index') ?>">
      <span class="icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
          <path d="M3 11l9-7 9 7v8a2 2 0 0 1-2 2h-4v-6H9v6H5a2 2 0 0 1-2-2v-8z"
                stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
        </svg>
      </span>
      <span>Dashboard</span>
    </a>

    <!-- Blogs -->
    <div class="menu-block">
      <a class="menu-item blog-link <?= is_active(['blog','add_blog','edit_blog']) ?>" href="<?= url_to('blog') ?>">
        <span class="icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
            <path d="M4 5h16M4 12h16M4 19h10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
          </svg>
        </span>
        <span>Blogs</span>
      </a>
    </div>

    <!-- Pages -->
    <div class="menu-group <?= $pages_open ? 'open' : '' ?>">
      <button class="menu-item has-children"
              type="button"
              data-toggle="collapse"
              aria-expanded="<?= $pages_open ? 'true' : 'false' ?>">
        <span class="icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
            <path d="M5 4h14a2 2 0 0 1 2 2v10.5a1.5 1.5 0 0 1-1.5 1.5H8l-5 4V6a2 2 0 0 1 2-2z"
                  stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
          </svg>
        </span>
        <span>Pages</span>
        <span class="chev">▾</span>
      </button>

      <div class="submenu">
        <!-- <a class="submenu-item <?= is_active('pages.php') ?>" href="<?= url_to('pages.php') ?>">All Pages</a> -->
        <a class="submenu-item <?= is_active('page-home.php') ?>" href="<?= url_to('page-home.php') ?>">Home</a>
        <a class="submenu-item <?= is_active('page-about') ?>" href="<?= url_to('page-about') ?>">About</a>

        <!-- Section hubs -->
        <a class="submenu-item <?= is_active('page-stay.php') ?>" href="<?= url_to('page-stay.php') ?>">Stay</a>
        <a class="submenu-item <?= is_active('page-dining') ?>" href="<?= url_to('page-dining') ?>">Dining</a>
        <a class="submenu-item <?= is_active('page-experiences') ?>" href="<?= url_to('page-experiences') ?>">Experiences</a>

        <!-- 🔹 Bookings item (updated) -->
        <a class="submenu-item <?= is_active('bookings.php') ?>" href="<?= url_to('bookings.php') ?>">Bookings</a>

        <!-- Gallery hub & its sub-pages -->
        <a class="submenu-item <?= is_active('page-gallery-photo') ?>" href="<?= url_to('page-gallery-photo') ?>">Gallery</a>
        <a class="submenu-item <?= is_active('page-faq') ?>" href="<?= url_to('page-faq') ?>">FAQ</a>
      </div>
    </div>

    <!-- Meta & SEO -->
    <div class="menu-group <?= $meta_open ? 'open' : '' ?>">
      <button class="menu-item has-children"
              type="button"
              data-toggle="collapse"
              aria-expanded="<?= $meta_open ? 'true' : 'false' ?>">
        <span class="icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/>
            <path d="M12 7v10M7 12h10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
          </svg>
        </span>
        <span>Meta &amp; SEO</span>
        <span class="chev">▾</span>
      </button>
      <div class="submenu">
        <a class="submenu-item <?= is_active('seo-global.php') ?>" href="<?= url_to('seo-global.php') ?>">Site Meta (global)</a>
        <a class="submenu-item <?= is_active('seo-pages.php') ?>" href="<?= url_to('seo-pages.php') ?>">Page Meta</a>
      </div>
    </div>

    <!-- Logout -->
    <a class="menu-item" href="<?= url_to('logout.php') ?>">
      <span class="icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
          <path d="M9 21H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3M16 17l5-5-5-5M21 12H9"
                stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
      <span>Logout</span>
    </a>
  </nav>
</aside>

<script>
  // Collapse / expand groups (no external JS needed)
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.menu-item.has-children[data-toggle="collapse"]');
    if (!btn) return;
    const group = btn.closest('.menu-group');
    if (!group) return;
    const isOpen = group.classList.toggle('open');
    btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  });
</script>

<style>
  /* Sidebar professional styling */
  .admin-sidebar {
    width: 250px;
    background-color: #2C3E50;
    color: #ecf0f1;
    height: 100vh;
    padding-top: 20px;
    font-family: Arial, sans-serif;
    position: fixed;
  }

  .brand {
    text-align: center;
    margin-bottom: 40px;
  }

  .brand-logo {
    margin-bottom: 10px;
  }

  .brand-text {
    font-size: 20px;
    font-weight: 700;
    color: #ecf0f1;
  }

  .menu {
    padding-left: 10px;
  }

  .menu-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 15px;
    color: #ecf0f1;
    text-decoration: none;
    font-weight: 600;
    margin: 10px 0;
    border-radius: 5px;
    transition: background-color 0.3s;
  }

  .menu-item:hover {
    background-color: #34495E;
  }

  .menu-item.active {
    background-color: #2980b9;
  }

  .menu-item .icon {
    margin-right: 4px;
    display: inline-flex;
  }

  .submenu-item {
    display: block;
    padding: 8px 15px 8px 35px;
    color: #ecf0f1;
    text-decoration: none;
    font-weight: normal;
    font-size: 14px;
    border-radius: 4px;
    margin: 2px 0;
  }

  .submenu-item:hover {
    background-color: #34495E;
  }

  .submenu {
    display: none;
  }

  .menu-group.open .submenu {
    display: block;
  }

  .chev {
    margin-left: auto;
    font-size: 12px;
  }
  /* Responsive: collapse sidebar on small / portrait screens */
  .sidebar-toggle { display:none; position:fixed; left:12px; top:12px; z-index:10001; background:#b19777; color:#000; border:none; padding:8px 10px; border-radius:6px; font-size:18px; }
  .mobile-sidebar-overlay { display:none; }
  .sidebar-close { display:none; position:absolute; right:12px; top:10px; z-index:10003; background:transparent; color:#fff; border:none; font-size:22px; cursor:pointer; }
  @media (max-width: 900px), (orientation: portrait) {
    .admin-sidebar { left: -100%; top:0; height:100%; width:80%; max-width:320px; transition: left .24s ease; z-index:10002; }
    .admin-sidebar.open { left: 0; }
    .sidebar-toggle { display:block; }
    .mobile-sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:10000; }
    .mobile-sidebar-overlay.visible { display:block; }
    .sidebar-close { display:block; }
    /* push main content to full width when sidebar hidden */
    .main { margin-left: 0 !important; }
  }
</style>

<script>
  // Mobile sidebar toggle behavior
  (function(){
    var toggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('adminSidebar');
    var overlay = document.getElementById('sidebarOverlay');
    if(!toggle || !sidebar) return;
    function openSidebar(){ sidebar.classList.add('open'); if(overlay) overlay.classList.add('visible'); }
    function closeSidebar(){ sidebar.classList.remove('open'); if(overlay) overlay.classList.remove('visible'); }
    toggle.addEventListener('click', function(){ if(sidebar.classList.contains('open')) closeSidebar(); else openSidebar(); });
    if(overlay){ overlay.addEventListener('click', closeSidebar); }
    var closeBtn = document.getElementById('sidebarClose');
    if(closeBtn){ closeBtn.addEventListener('click', closeSidebar); }
    // Close on Escape
    document.addEventListener('keydown', function(e){ if(e.key === 'Escape') closeSidebar(); });
    // Close on navigation (clicking links inside sidebar)
    sidebar.addEventListener('click', function(e){ var a = e.target.closest('a'); if(a && window.matchMedia('(max-width: 900px), (orientation: portrait)').matches) closeSidebar(); });
  })();
</script>
