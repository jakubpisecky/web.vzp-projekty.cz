<?php
// /templates/news_list.php

$seg0 = url_segments()[0] ?? '';
$base = $articlesBase ?? ('/' . ($section['slug'] ?? $seg0));
$base = rtrim($base, '/');

$baseSlug  = ltrim($section['slug'] ?? $seg0, '/');
$baseLabel = $section['title'] ?? null;

if (!$baseLabel && ($conn instanceof mysqli) && $baseSlug) {
    $stmt = $conn->prepare("SELECT title FROM pages WHERE slug = ? LIMIT 1");
    $stmt->bind_param("s", $baseSlug);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!empty($row['title'])) {
        $baseLabel = $row['title'];
    }
}

if (!$baseLabel && $baseSlug) {
    $baseLabel = mb_convert_case(str_replace('-', ' ', $baseSlug), MB_CASE_TITLE, 'UTF-8');
}

$breadcrumbs = [
    ['label' => 'Domů', 'href' => '/'],
    ['label' => $baseLabel ?: 'Aktuality', 'href' => $base],
];

$view = strtolower(trim((string)($_GET['view'] ?? '')));

if ($view !== 'grid' && $view !== 'list') {
    $tpl = (string)($section['template'] ?? '');
    $parts = array_values(array_filter(explode('|', $tpl)));

    foreach ($parts as $p) {
        if (stripos($p, 'view=') === 0) {
            $v = strtolower(trim(substr($p, 5)));

            if ($v === 'grid' || $v === 'list') {
                $view = $v;
            }
        }
    }
}

if ($view !== 'grid' && $view !== 'list') {
    $view = 'grid';
}

if (!function_exists('article_teaser')) {
    function article_teaser(?string $html, int $maxLen = 220): string
    {
        $text = strip_tags((string)$html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        if (function_exists('mb_strlen') && mb_strlen($text) > $maxLen) {
            return mb_substr($text, 0, $maxLen) . '…';
        }

        if (!function_exists('mb_strlen') && strlen($text) > $maxLen) {
            return substr($text, 0, $maxLen) . '…';
        }

        return $text;
    }
}

$perPage = (int)setting('pagination_per_page', 12);
$pageNum = get_page_param();

$totalRow = $conn->query("SELECT COUNT(*) AS c FROM articles WHERE status='published'")->fetch_assoc();
$total = (int)($totalRow['c'] ?? 0);

$totalPages = max(1, (int)ceil($total / max(1, $perPage)));

if ($pageNum > $totalPages) {
    $pageNum = $totalPages;
}

$offset = ($pageNum - 1) * $perPage;

$articles = [];

$stmt = $conn->prepare("
    SELECT id, title, slug, thumbnail, publish_date, content
    FROM articles
    WHERE status = 'published'
    ORDER BY publish_date DESC, id DESC
    LIMIT ?, ?
");
$stmt->bind_param("ii", $offset, $perPage);
$stmt->execute();

$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $articles[] = $row;
}

$stmt->close();

function build_view_url(string $base, string $view): string
{
    $qs = $_GET;
    $qs['view'] = $view;

    $q = http_build_query($qs);

    return $base . ($q ? ('?' . $q) : '');
}

ob_start();
?>

<div role="main" class="main">

    <section class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8 text-start">
                    <h1 class="font-weight-bold">
                        <?= e($title ?? ($section['title'] ?? $baseLabel ?? 'Aktuality')) ?>
                    </h1>
                </div>

                <div class="col-md-4">
                    <?php render_breadcrumbs($breadcrumbs); ?>
                </div>
            </div>
        </div>
    </section>

    <div class="container mb-5">
        <div class="row">
            <div class="col-md-12">

                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="text-muted small">
                        <?php if ($total): ?>
                            Celkem: <strong><?= (int)$total ?></strong>
                        <?php endif; ?>
                    </div>

                    <div class="btn-group btn-group-sm" role="group" aria-label="Zobrazení">
                        <a class="btn btn-outline-secondary<?= $view === 'grid' ? ' active' : '' ?>"
                           href="<?= e(build_view_url($base, 'grid')) ?>">
                            <i class="fas fa-th-large me-1"></i> Grid
                        </a>

                        <a class="btn btn-outline-secondary<?= $view === 'list' ? ' active' : '' ?>"
                           href="<?= e(build_view_url($base, 'list')) ?>">
                            <i class="fas fa-bars me-1"></i> List
                        </a>
                    </div>
                </div>

                <div class="content">

                    <?php if (empty($articles)): ?>

                        <p class="text-muted">Zatím tu nic není.</p>

                    <?php else: ?>

                        <?php if ($view === 'grid'): ?>
                            <div class="row g-4">
                                <?php foreach ($articles as $a): ?>
                                    <?php
                                    $url = $base . '/' . trim($a['slug'] ?? '', '/');
                                    $titleA = (string)($a['title'] ?? '');
                                    $thumb = !empty($a['thumbnail']) ? media_url($a['thumbnail']) : null;
                                    $teaser = article_teaser($a['content'] ?? '', 160);
                                    ?>

                                    <div class="col-md-6 col-lg-4">
                                        <article class="card h-100 border-0 shadow-sm bg-white">

                                            <?php if ($thumb): ?>
                                                <a href="<?= e($url) ?>">
                                                    <img src="<?= e($thumb) ?>"
                                                        class="card-img-top"
                                                        alt="<?= e($titleA) ?>"
                                                        style="height:260px !important;width:100%;object-fit:cover;">
                                                </a>
                                            <?php endif; ?>
                                            <div class="card-body d-flex flex-column">

                                                <h2 class="h5 card-title mb-2">
                                                    <a class="text-decoration-none text-dark"
                                                       href="<?= e($url) ?>">
                                                        <?= e($titleA) ?>
                                                    </a>
                                                </h2>

                                                <?php if (!empty($a['publish_date'])): ?>
                                                    <div class="text-muted small mb-3">
                                                        <?= e(date('j. n. Y', strtotime($a['publish_date']))) ?>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($teaser): ?>
                                                    <p class="mb-4" style="min-height:90px;">
                                                        <?= e($teaser) ?>
                                                    </p>
                                                <?php endif; ?>

                                                <div class="mt-auto">
                                                    <a href="<?= e($url) ?>"
                                                       class="btn btn-link font-weight-bold text-decoration-none ps-0">
                                                        Pokračování
                                                        <i class="fas fa-angle-right text-3 ms-2"></i>
                                                    </a>
                                                </div>

                                            </div>

                                        </article>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                        <?php else: ?>

                            <?php foreach ($articles as $idx => $a): ?>
                                <?php
                                $url = $base . '/' . trim($a['slug'] ?? '', '/');
                                $titleA = (string)($a['title'] ?? '');
                                $thumb = !empty($a['thumbnail']) ? media_url($a['thumbnail']) : null;
                                $teaser = article_teaser($a['content'] ?? '');
                                ?>

                                <div class="row pt-4 pb-1 align-items-start">
                                    <?php if ($thumb): ?>
                                        <div class="col-md-3 mb-3 mb-md-0">
                                            <a href="<?= e($url) ?>">
                                                <img src="<?= e($thumb) ?>"
                                                     class="img-fluid"
                                                     alt="<?= e($titleA) ?>">
                                            </a>
                                        </div>

                                        <div class="col-md-9">
                                    <?php else: ?>

                                        <div class="col-12">

                                    <?php endif; ?>

                                            <h2 class="font-weight-semibold h4 mb-2">
                                                <a href="<?= e($url) ?>" class="text-decoration-none">
                                                    <?= e($titleA) ?>
                                                </a>
                                            </h2>

                                            <?php if (!empty($a['publish_date'])): ?>
                                                <div class="text-muted small mb-1">
                                                    <?= e(date('j. n. Y', strtotime($a['publish_date']))) ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($teaser): ?>
                                                <p class="lead mb-2">
                                                    <?= e($teaser) ?>
                                                </p>
                                            <?php endif; ?>

                                            <p class="mb-0">
                                                <a href="<?= e($url) ?>"
                                                   class="btn btn-link font-weight-bold text-decoration-none ps-0">
                                                    Pokračování
                                                    <i class="fas fa-angle-right text-3 ms-3"></i>
                                                </a>
                                            </p>

                                        </div>
                                </div>

                                <?php if ($idx < count($articles) - 1): ?>
                                    <hr class="my-4">
                                <?php endif; ?>
                            <?php endforeach; ?>

                        <?php endif; ?>

                        <?php render_pagination($total, $perPage, $pageNum); ?>

                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';