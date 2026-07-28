<?php
// /index.php – router frontendu

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

/*
 * Frontend modulu Pracoviště
 */
require_once __DIR__ . '/includes/frontend/workplaces.php';
require_once __DIR__ . '/includes/frontend/workplaces_router.php';

/*
 * Nastavení, maintenance a přesměrování
 */
settings_load_all($conn);
maintenance_guard();
handleRedirects($conn);

/*
 * Aktuální URL
 */
$path = parse_url(
    $_SERVER['REQUEST_URI'],
    PHP_URL_PATH
);

$path = $path ?: '/';

$segs = array_values(
    array_filter(
        explode('/', trim($path, '/')),
        static function ($segment) {
            return $segment !== '';
        }
    )
);

/*
 * ==========================================================
 * HLEDÁNÍ
 * /hledat?s=...
 * ==========================================================
 */
$searchQuery = trim($_GET['s'] ?? '');

if ($path === '/hledat') {
    $title = $searchQuery !== ''
        ? 'Výsledky hledání: ' . $searchQuery
        : 'Hledání';

    $meta_description = '';

    include_template('search');
    exit;
}

/*
 * ==========================================================
 * HOMEPAGE
 * ==========================================================
 *
 * Načítáme pouze běžné stránky.
 * Interní stránky pracovišť mají owner_type = workplace.
 */
$homePage = null;
$homeSlug = null;

$res = $conn->query("
    SELECT
        id,
        title,
        slug,
        content,
        meta_title,
        meta_description,
        template

    FROM pages

    WHERE status = 'published'
      AND owner_type = 'page'

    ORDER BY id ASC
");

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $tpl = tpl_key(
            $row['template'] ?? ''
        );

        if (in_array(
            $tpl,
            ['home', 'homepage'],
            true
        )) {
            $homePage = $row;
            $homeSlug = (string)$row['slug'];
            break;
        }
    }
}

/*
 * Root URL → homepage
 */
if ($path === '/' && $homePage) {
    $page = $homePage;

    $title = $page['meta_title']
        ?: $page['title'];

    $meta_description =
        $page['meta_description'] ?? '';

    include_template('homepage');
    exit;
}

/*
 * Canonical redirect z /slug-homepage na /
 */
if (
    $homeSlug !== null
    && $homeSlug !== ''
    && $path === '/' . $homeSlug
) {
    header('Location: /', true, 301);
    exit;
}

/*
 * Pokud homepage není vytvořená, root skončí jako 404.
 */
if ($path === '/' && !$homePage) {
    http_response_code(404);

    $title = 'Stránka nenalezena';
    $meta_description = '';

    include_template('404');
    exit;
}

/*
 * ==========================================================
 * PRVNÍ SEGMENT = HLAVNÍ STRÁNKA NEBO SEKCE
 * ==========================================================
 */
$slug = trim(
    (string)($segs[0] ?? '')
);

if ($slug === '') {
    http_response_code(404);

    $title = 'Stránka nenalezena';
    $meta_description = '';

    include_template('404');
    exit;
}

/*
 * Načítáme pouze běžnou stránku.
 *
 * Stránky pracovišť mají owner_type = workplace
 * a nemohou se tímto dotazem zobrazit samostatně.
 */
$stmt = $conn->prepare("
    SELECT
        id,
        title,
        slug,
        content,
        meta_title,
        meta_description,
        template

    FROM pages

    WHERE slug = ?
      AND status = 'published'
      AND owner_type = 'page'

    LIMIT 1
");

$stmt->bind_param("s", $slug);
$stmt->execute();

$page = $stmt
    ->get_result()
    ->fetch_assoc();

$stmt->close();

if (!$page) {
    http_response_code(404);

    $title = 'Stránka nenalezena';
    $meta_description = '';

    include_template('404');
    exit;
}

$tplName = tpl_key(
    $page['template'] ?? 'page'
);

$title = $page['meta_title']
    ?: $page['title'];

$meta_description =
    $page['meta_description'] ?? '';

/*
 * ==========================================================
 * MODUL PRACOVIŠTĚ
 * ==========================================================
 *
 * Router se aktivuje podle šablony stránky:
 *
 * template = workplaces
 *
 * Slug této stránky automaticky určuje základní URL.
 */
if (
    workplaces_route(
        $conn,
        $page,
        $segs
    )
) {
    exit;
}

/*
 * Kontaktní formulář hlavní stránky.
 */
if (
    $tplName === 'contact'
    && $_SERVER['REQUEST_METHOD'] === 'POST'
) {
    handle_contact_form($page);
}

/*
 * ==========================================================
 * SPECIÁLNÍ PŘÍPAD TŘÍ ÚROVNÍ
 * ==========================================================
 *
 * Například:
 *
 * /rodic/aktuality/clanek
 * /rodic/fotogalerie/album
 *
 * Druhý segment představuje podstránku typu
 * články nebo galerie a třetí segment její detail.
 */
if (count($segs) === 3) {
    $childSlug = (string)$segs[1];
    $parentId = (int)$page['id'];

    $stmt = $conn->prepare("
        SELECT
            id,
            title,
            slug,
            content,
            meta_title,
            meta_description,
            template

        FROM pages

        WHERE slug = ?
          AND parent_id = ?
          AND status = 'published'
          AND owner_type = 'page'

        LIMIT 1
    ");

    $stmt->bind_param(
        "si",
        $childSlug,
        $parentId
    );

    $stmt->execute();

    $child = $stmt
        ->get_result()
        ->fetch_assoc();

    $stmt->close();

    if ($child) {
        $childTpl = tpl_key(
            $child['template'] ?? 'page'
        );

        if (in_array(
            $childTpl,
            ['gallery', 'galleries', 'articles'],
            true
        )) {
            $page = $child;
            $tplName = $childTpl;

            $title = $page['meta_title']
                ?: $page['title'];

            $meta_description =
                $page['meta_description'] ?? '';

            /*
             * Z /rodic/sekce/detail vytvoříme pro další
             * zpracování /sekce/detail.
             */
            $segs = array_slice($segs, 1);
        }
    }
}

/*
 * ==========================================================
 * JEDNOSEGMENTOVÉ URL
 * /sekce
 * ==========================================================
 */
if (count($segs) === 1) {

    if ($tplName === 'articles') {
        $section = $page;
        $articlesBase = '/' . $page['slug'];

        include_template('news_list');

    } elseif (in_array(
        $tplName,
        ['gallery', 'galleries'],
        true
    )) {
        $section = $page;
        $galleryBase = '/' . $page['slug'];

        include_template('gallery');

    } elseif ($tplName === 'contact') {
        include_template('contact');

    } elseif (in_array(
        $tplName,
        ['homepage', 'home'],
        true
    )) {
        include_template('homepage');

    } elseif ($tplName === 'universal') {
        include_template('universal');

    } else {
        include_template('page');
    }

    exit;
}

/*
 * ==========================================================
 * DVOUSEGMENTOVÉ URL
 * /sekce/neco
 * ==========================================================
 */

/*
 * 1. Detail galerie
 * /sekce/album
 */
if (
    count($segs) === 2
    && in_array(
        $tplName,
        ['gallery', 'galleries'],
        true
    )
) {
    $albumSlug = (string)$segs[1];

    $stmt = $conn->prepare("
        SELECT
            id,
            title,
            slug

        FROM galleries

        WHERE slug = ?

        LIMIT 1
    ");

    $stmt->bind_param(
        "s",
        $albumSlug
    );

    $stmt->execute();

    $gallery = $stmt
        ->get_result()
        ->fetch_assoc();

    $stmt->close();

    if ($gallery) {
        $title = $gallery['title'];
        $meta_description = '';

        $galleryBase = '/'
            . $page['slug'];

        include_template('gallery_detail');
        exit;
    }
}

/*
 * 2. Detail článku
 * /sekce/clanek
 */
if (
    count($segs) === 2
    && $tplName === 'articles'
) {
    $articleSlug = (string)$segs[1];

    $stmt = $conn->prepare("
        SELECT
            id,
            title,
            slug,
            content,
            thumbnail,
            publish_date

        FROM articles

        WHERE slug = ?
          AND status = 'published'

        LIMIT 1
    ");

    $stmt->bind_param(
        "s",
        $articleSlug
    );

    $stmt->execute();

    $article = $stmt
        ->get_result()
        ->fetch_assoc();

    $stmt->close();

    if ($article) {
        $title = $article['title'];
        $meta_description = '';

        $articlesBase = '/'
            . $page['slug'];

        include_template('news_detail');
        exit;
    }
}

/*
 * 3. Obecná podstránka
 * /rodic/podstranka
 */
if (count($segs) === 2) {
    $childSlug = (string)$segs[1];
    $parentId = (int)$page['id'];

    $stmt = $conn->prepare("
        SELECT
            id,
            title,
            slug,
            content,
            meta_title,
            meta_description,
            template

        FROM pages

        WHERE slug = ?
          AND parent_id = ?
          AND status = 'published'
          AND owner_type = 'page'

        LIMIT 1
    ");

    $stmt->bind_param(
        "si",
        $childSlug,
        $parentId
    );

    $stmt->execute();

    $child = $stmt
        ->get_result()
        ->fetch_assoc();

    $stmt->close();

    if ($child) {
        $page = $child;

        $tplName = tpl_key(
            $page['template'] ?? 'page'
        );

        $title = $page['meta_title']
            ?: $page['title'];

        $meta_description =
            $page['meta_description'] ?? '';

        if (
            $tplName === 'contact'
            && $_SERVER['REQUEST_METHOD'] === 'POST'
        ) {
            handle_contact_form($page);
        }

        if ($tplName === 'contact') {
            include_template('contact');

        } elseif (in_array(
            $tplName,
            ['homepage', 'home'],
            true
        )) {
            include_template('homepage');

        } elseif ($tplName === 'universal') {
            include_template('universal');

        } elseif (in_array(
            $tplName,
            ['gallery', 'galleries'],
            true
        )) {
            $section = $page;

            $galleryBase = '/'
                . implode('/', $segs);

            include_template('gallery');

        } elseif ($tplName === 'articles') {
            $section = $page;

            $articlesBase = '/'
                . implode('/', $segs);

            include_template('news_list');

        } else {
            include_template('page');
        }

        exit;
    }
}

/*
 * ==========================================================
 * 404
 * ==========================================================
 */
http_response_code(404);

$title = 'Stránka nenalezena';
$meta_description = '';

include_template('404');
exit;