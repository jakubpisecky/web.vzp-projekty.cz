<?php
// /index.php – router frontendu
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// 1) vždy nejdřív načtení nastavení + maintenance
settings_load_all($conn);
maintenance_guard();
handleRedirects($conn);

/* ==== Hledání – /hledat?s=... ==== */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$searchQuery = trim($_GET['s'] ?? '');

if ($path === '/hledat') {
    $title = $searchQuery ? 'Výsledky hledání: ' . $searchQuery : 'Hledání';
    $meta_description = '';
    include_template('search');
    exit;
}

/* ==== Zjištění homepage ==== */
$homePage = null;
$homeSlug = null;

$res = $conn->query("
    SELECT id, title, slug, content, meta_title, meta_description, template
    FROM pages
    WHERE status = 'published'
    ORDER BY id ASC
");

while ($row = $res->fetch_assoc()) {
    $tpl = tpl_key($row['template'] ?? '');

    if ($tpl === 'home' || $tpl === 'homepage') {
        $homePage = $row;
        $homeSlug = $row['slug'];
        break;
    }
}

/* ==== Parsování URL ==== */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segs = array_values(array_filter(explode('/', trim($path, '/'))));

/* ==== Root (/) → homepage ==== */
if ($path === '/' && $homePage) {
    $page = $homePage;
    $title = $page['meta_title'] ?: $page['title'];
    $meta_description = $page['meta_description'] ?? '';
    include_template('homepage');
    exit;
}

/* Canonical redirect z /{homeSlug} na / */
if ($homeSlug && $path === '/' . $homeSlug) {
    header('Location: /', true, 301);
    exit;
}

/* ==== 1. segment = slug stránky/sekce ==== */
$slug = $segs[0] ?? ($homeSlug ?: 'home');

$stmt = $conn->prepare("
    SELECT id, title, slug, content, meta_title, meta_description, template
    FROM pages
    WHERE slug = ?
      AND status = 'published'
    LIMIT 1
");
$stmt->bind_param("s", $slug);
$stmt->execute();
$page = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($page) {
    $tplName = tpl_key($page['template'] ?? 'page');
    $title = $page['meta_title'] ?: $page['title'];
    $meta_description = $page['meta_description'] ?? '';

    if ($tplName === 'contact' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        handle_contact_form($page);
    }

    // --- Speciální případ: 3 úrovně
    if (count($segs) === 3) {
        $childSlug = $segs[1];
        $parentId = (int)$page['id'];

        $stmt = $conn->prepare("
            SELECT id, title, slug, content, meta_title, meta_description, template
            FROM pages
            WHERE slug = ?
              AND parent_id = ?
              AND status = 'published'
            LIMIT 1
        ");
        $stmt->bind_param("si", $childSlug, $parentId);
        $stmt->execute();
        $child = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($child) {
            $childTpl = tpl_key($child['template'] ?? 'page');

            if (in_array($childTpl, ['gallery', 'galleries', 'articles'], true)) {
                $page = $child;
                $tplName = $childTpl;
                $title = $page['meta_title'] ?: $page['title'];
                $meta_description = $page['meta_description'] ?? '';

                $segs = array_slice($segs, 1);
            }
        }
    }

    // ---- Jednosegmentové URL (/sekce) ----
    if (count($segs) === 1) {

        if ($tplName === 'articles') {
            $section = $page;
            $articlesBase = '/' . $page['slug'];
            include_template('news_list');

        } elseif ($tplName === 'gallery' || $tplName === 'galleries') {
            $section = $page;
            $galleryBase = '/' . $page['slug'];
            include_template('gallery');

        } elseif ($tplName === 'contact') {
            include_template('contact');

        } elseif ($tplName === 'homepage' || $tplName === 'home') {
            include_template('homepage');

        } elseif ($tplName === 'universal') {
            include_template('universal');

        } else {
            include_template('page');
        }

        exit;
    }

    // ---- Dvousegmentové URL (/sekce/něco) ----

    // 1) Detail galerie (/sekce/album)
    if (count($segs) === 2 && ($tplName === 'gallery' || $tplName === 'galleries')) {
        [$sectionSlug, $albumSlug] = $segs;

        $stmt = $conn->prepare("
            SELECT id, title, slug
            FROM galleries
            WHERE slug = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $albumSlug);
        $stmt->execute();
        $gallery = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($gallery) {
            $title = $gallery['title'];
            $galleryBase = '/' . $page['slug'];
            include_template('gallery_detail');
            exit;
        }
    }

    // 2) Detail článku (/sekce/clanek)
    if (count($segs) === 2 && $tplName === 'articles') {
        $articleSlug = $segs[1];

        $stmt = $conn->prepare("
            SELECT id, title, slug, content, thumbnail, publish_date
            FROM articles
            WHERE slug = ?
              AND status = 'published'
            LIMIT 1
        ");
        $stmt->bind_param("s", $articleSlug);
        $stmt->execute();
        $article = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($article) {
            $title = $article['title'];
            $meta_description = '';
            $articlesBase = '/' . $page['slug'];
            include_template('news_detail');
            exit;
        }
    }

    // 3) Obecná podstránka (/rodic/podstranka)
    if (count($segs) === 2) {
        $childSlug = $segs[1];
        $parentId = (int)$page['id'];

        $stmt = $conn->prepare("
            SELECT id, title, slug, content, meta_title, meta_description, template
            FROM pages
            WHERE slug = ?
              AND parent_id = ?
              AND status = 'published'
            LIMIT 1
        ");
        $stmt->bind_param("si", $childSlug, $parentId);
        $stmt->execute();
        $child = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($child) {
            $page = $child;
            $tplName = tpl_key($page['template'] ?? 'page');
            $title = $page['meta_title'] ?: $page['title'];
            $meta_description = $page['meta_description'] ?? '';

            if ($tplName === 'contact') {
                include_template('contact');

            } elseif ($tplName === 'homepage' || $tplName === 'home') {
                include_template('homepage');

            } elseif ($tplName === 'universal') {
                include_template('universal');

            } elseif ($tplName === 'gallery' || $tplName === 'galleries') {
                $section = $page;
                $galleryBase = '/' . implode('/', $segs);
                include_template('gallery');

            } elseif ($tplName === 'articles') {
                $section = $page;
                $articlesBase = '/' . implode('/', $segs);
                include_template('news_list');

            } else {
                include_template('page');
            }

            exit;
        }
    }
}

/* ==== 404 ==== */
http_response_code(404);
$title = 'Stránka nenalezena';
$meta_description = '';
include_template('404');
exit;