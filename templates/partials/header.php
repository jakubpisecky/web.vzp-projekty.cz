<?php
// /templates/partials/header.php

global $conn;

$inMaintenance = !empty($GLOBALS['MAINTENANCE_MODE']);
$currentPath   = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$homeSlug      = isset($homeSlug) ? $homeSlug : ($GLOBALS['homeSlug'] ?? null);

/**
 * Menu z tabulky pages – 1. + 2. úroveň
 */
$menu = [];
if ($conn instanceof mysqli) {
    // kořeny
    $top = $conn->query("
        SELECT id, title, slug
        FROM pages
        WHERE status='published' AND show_in_menu=1 AND parent_id=0
        ORDER BY menu_order ASC, id ASC
    ");
    while ($r = $top->fetch_assoc()) {
        $r['children'] = [];
        $menu[(int)$r['id']] = $r;
    }

    // děti kořenů
    if ($menu) {
        $ids = implode(',', array_map('intval', array_keys($menu)));
        $child = $conn->query("
            SELECT id, title, slug, parent_id
            FROM pages
            WHERE status='published' AND show_in_menu=1 AND parent_id IN ($ids)
            ORDER BY menu_order ASC, id ASC
        ");
        while ($c = $child->fetch_assoc()) {
            $pid = (int)$c['parent_id'];
            if (isset($menu[$pid])) {
                $menu[$pid]['children'][] = $c;
            }
        }
        $menu = array_values($menu); // reindex
    }
}

/**
 * Aktivní položka – root/child podle aktuální URL
 */
$isActiveItem = function(array $item) use ($currentPath, $homeSlug): bool {
    $slug = trim($item['slug'] ?? '', '/');
    $path = trim($currentPath ?? '', '/');

    if ($slug === '') return false;

    // homepage
    if ($path === '' && $homeSlug && $slug === $homeSlug) return true;

    // přesně /slug
    if ($path === $slug) return true;

    // /slug/něco
    if (strpos($path, $slug . '/') === 0) return true;

    // /slug/dite
    if (!empty($item['children'])) {
        foreach ($item['children'] as $child) {
            $childSlug = trim($child['slug'] ?? '', '/');
            if ($childSlug === '') continue;
            $childPath = trim($slug . '/' . $childSlug, '/');
            if ($path === $childPath) return true;
        }
    }

    return false;
};

// Nastavení z tabulky settings
$phone   = trim((string) setting('contact_phone', ''));
$email   = trim((string) setting('contact_email', ''));
$address = setting('contact_address', '');

// logo z settings (site_logo_url) → přes media_url()
$logoSrc = setting('site_logo_url', '');
$logoSrc = $logoSrc ? media_url($logoSrc) : '/img/logo-simple.png';

$socials = function_exists('settings_social_links') ? settings_social_links() : [];
?>
<header id="header" class="header-effect-shrink" data-plugin-options="{'stickyEnabled': true, 'stickyEnableOnBoxed': true, 'stickyEnableOnMobile': true, 'stickyStartAt': 120}">
    <div class="header-body">
        <div class="header-top">
            <div class="header-top-container container">
                <div class="header-row">
                    <div class="header-column justify-content-start">
                        <ul class="list-infos">
                            <li class="list-info-item-increase-size d-none d-lg-flex">
                                <i class="lnr lnr-phone-handset text-color-primary font-weight-semibold me-1"></i>
                                <a href="<?= $phone ? 'tel:' . preg_replace('/\s+/', '', $phone) : 'tel:+1234567890' ?>" class="text-color-primary">
                                    <strong><?= htmlspecialchars($phone ?: '800 123 456') ?></strong>
                                </a>
                            </li>
                            <li class="list-info-item-increase-icon-size d-none d-lg-flex">
                                <i class="lnr lnr-envelope me-1"></i>
                                <a href="<?= $email ? 'mailto:' . htmlspecialchars($email) : 'mailto:email@domain.com' ?>">
                                    <?= htmlspecialchars($email ?: 'email@domain.com') ?>
                                </a>
                            </li>
                            <li class="list-info-item-increase-icon-size">
                                <i class="icon icon-location-pin text-2 position-relative top-1 me-1"></i>
                                <?= htmlspecialchars($address ?: '1234 Street Name, City, State, USA') ?>
                            </li>
                        </ul>
                    </div>
                    <div class="header-column justify-content-end">
                        <ul class="header-top-social-icons social-icons social-icons-transparent social-icons-icon-dark social-icons-2 d-none d-md-block">
                            <?php if (!empty($socials)): ?>
                                <?php foreach ($socials as $soc): ?>
                                    <?php
                                        $type = htmlspecialchars($soc['type']);
                                        $url  = htmlspecialchars($soc['url']);
                                    ?>
                                    <li class="social-icons-<?= $type ?>">
                                        <a href="<?= $url ?>" target="_blank" rel="noopener" title="<?= ucfirst($type) ?>">
                                            <i class="fab fa-<?= $type ?>"></i>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- fallback, když ještě nic není v nastavení -->
                                <li class="social-icons-instagram">
                                    <a href="http://www.instagram.com/" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a>
                                </li>
                                <li class="social-icons-twitter">
                                    <a href="http://www.twitter.com/" target="_blank" title="Twitter"><i class="fab fa-twitter"></i></a>
                                </li>
                                <li class="social-icons-facebook">
                                    <a href="http://www.facebook.com/" target="_blank" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                                </li>
                            <?php endif; ?>
                        </ul>
                        <a href="/kontakt" class="btn btn-primary btn-3 font-weight-bold text-1 rounded-0 ms-3">Kontakt</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="header-container container">
            <div class="header-row">
                <div class="header-column justify-content-start">
                    <div class="header-logo">
                        <a href="/">
                            <img alt="<?= htmlspecialchars($siteTitle ?? 'Logo') ?>" width="92" height="35" src="<?= htmlspecialchars($logoSrc) ?>">
                        </a>
                    </div>
                </div>
                <div class="header-column justify-content-end">
                    <div class="header-search-expanded">
                        <form method="GET" action="/hledat">
                            <div class="input-group bg-light border">
                                <input type="text"
                                    class="form-control text-4"
                                    name="s"
                                    placeholder="Hledám…"
                                    aria-label="Hledám…"
                                    value="<?= htmlspecialchars($_GET['s'] ?? '') ?>">
                                <span class="input-group-btn">
                                    <button class="btn text-4" type="submit">
                                        <i class="lnr lnr-magnifier text-color-dark font-weight-bold"></i>
                                    </button>
                                </span>
                            </div>
                        </form>
                    </div>

                    <div class="header-nav">
                        <div class="header-nav-main header-nav-main-effect-1 header-nav-main-sub-effect-1 order-1">
                            <nav class="collapse">
                                <ul class="nav flex-column flex-lg-row" id="mainNav">
                                    <?php $order = 1; ?>
                                    <?php foreach ($menu as $item): ?>
                                        <?php
                                            $parentSlug  = trim($item['slug'] ?? '', '/');
                                            $hasChildren = !empty($item['children']);

                                            if ($homeSlug && $parentSlug === $homeSlug) {
                                                $itemUrl = '/';
                                            } else {
                                                $itemUrl = '/' . $parentSlug;
                                            }

                                            $active = $isActiveItem($item) ? ' active' : '';
                                        ?>
                                        <li class="order-<?= $order ?><?= $hasChildren ? ' dropdown' : '' ?><?= $active ?>">
                                            <a class="dropdown-item<?= $hasChildren ? ' dropdown-toggle' : '' ?>"
                                               href="<?= htmlspecialchars($itemUrl) ?>">
                                                <?= htmlspecialchars($item['title']) ?>
                                            </a>

                                            <?php if ($hasChildren): ?>
                                                <ul class="dropdown-menu">
                                                    <?php foreach ($item['children'] as $child): ?>
                                                        <?php
                                                            $childSlug = trim($child['slug'] ?? '', '/');
                                                            $childPath = trim($parentSlug . '/' . $childSlug, '/');
                                                            $childUrl  = '/' . $childPath;
                                                            $childActive = (trim($currentPath, '/') === $childPath) ? ' active' : '';
                                                        ?>
                                                        <li>
                                                            <a class="dropdown-item<?= $childActive ?>"
                                                               href="<?= htmlspecialchars($childUrl) ?>">
                                                                <?= htmlspecialchars($child['title']) ?>
                                                            </a>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </li>
                                        <?php $order++; ?>
                                    <?php endforeach; ?>
                                </ul>
                            </nav>
                        </div>
                        <a href="#" class="header-search-button order-1 text-4 order-2 mt-1 me-xl-2 ms-3">
                            <i class="icon icon-magnifier font-weight-bold"></i>
                        </a>
                        <button class="header-btn-collapse-nav order-3 ms-3" data-bs-toggle="collapse" data-bs-target=".header-nav-main nav">
                            <span class="hamburguer">
                                <span></span>
                                <span></span>
                                <span></span>
                            </span>
                            <span class="close">
                                <span></span>
                                <span></span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
