<?php
// /templates/blocks/related_articles.php

$layout = $block['layout'] ?: 'vertical';

if (!in_array($layout, ['vertical', 'horizontal'], true)) {
    $layout = 'vertical';
}

$sourceType = $block['source_type'] ?? 'manual';

if (!in_array($sourceType, ['manual', 'category'], true)) {
    $sourceType = 'manual';
}

$articleLimit = (int)($block['article_limit'] ?? 3);

if ($articleLimit <= 0) {
    $articleLimit = 3;
}

$articleOrder = $block['article_order'] ?? 'newest';

if (!in_array($articleOrder, ['newest', 'oldest', 'random'], true)) {
    $articleOrder = 'newest';
}

$currentArticleId = (int)($article['id'] ?? 0);

// najdeme stránku s výpisem článků
$articlesBase = 'aktuality';

$resBase = $conn->query("
    SELECT slug
    FROM pages
    WHERE status = 'published'
      AND template = 'articles'
    ORDER BY id ASC
    LIMIT 1
");

if ($resBase && $rowBase = $resBase->fetch_assoc()) {
    $articlesBase = $rowBase['slug'];
}

$articlesArray = [];

if ($sourceType === 'category') {

    $categoryIds = [];

    if (!empty($block['category_ids'])) {
        $categoryIds = array_filter(array_map('intval', explode(';', $block['category_ids'])));
    }

    if (!empty($categoryIds)) {
        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));

        if ($articleOrder === 'oldest') {
            $orderSql = "a.publish_date ASC, a.id ASC";
        } elseif ($articleOrder === 'random') {
            $orderSql = "RAND()";
        } else {
            $orderSql = "a.publish_date DESC, a.id DESC";
        }

        $sql = "
            SELECT DISTINCT
                a.id,
                a.title,
                a.slug,
                a.thumbnail,
                a.content,
                a.publish_date
            FROM articles a
            INNER JOIN article_category ac ON ac.article_id = a.id
            WHERE a.status = 'published'
              AND ac.category_id IN ($placeholders)
        ";

        $types = str_repeat('i', count($categoryIds));
        $params = $categoryIds;

        if ($currentArticleId > 0) {
            $sql .= " AND a.id <> ?";
            $types .= "i";
            $params[] = $currentArticleId;
        }

        $sql .= "
            ORDER BY {$orderSql}
            LIMIT ?
        ";

        $types .= "i";
        $params[] = $articleLimit;

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $articlesArray[] = $row;
        }

        $stmt->close();
    }

} else {

    $sql = "
        SELECT
            a.id,
            a.title,
            a.slug,
            a.thumbnail,
            a.content,
            a.publish_date
        FROM article_block_related r
        INNER JOIN articles a ON a.id = r.article_id
        WHERE r.block_id = ?
          AND a.status = 'published'
    ";

    $types = "i";
    $params = [(int)$block['id']];

    if ($currentArticleId > 0) {
        $sql .= " AND a.id <> ?";
        $types .= "i";
        $params[] = $currentArticleId;
    }

    $sql .= "
        ORDER BY r.id ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $articlesArray[] = $row;
    }

    $stmt->close();
}

$totalArticles = count($articlesArray);
?>

<section class="section <?= e($block['section_class'] ?? '') ?>">
    <div class="container">

        <?php if (!empty($block['title'])): ?>
            <h2 class="font-weight-bold mb-4">
                <?= e($block['title']) ?>
            </h2>
        <?php endif; ?>

        <?php if ($totalArticles === 0): ?>

            <p class="text-muted">Nejsou vybrané žádné související články.</p>

        <?php else: ?>

            <?php if ($layout === 'horizontal'): ?>

                <?php foreach ($articlesArray as $index => $a): ?>

                    <?php
                    $url = '/' . ltrim($articlesBase, '/') . '/' . $a['slug'];
                    $thumb = trim($a['thumbnail'] ?? '');
                    $title = $a['title'] ?? '';
                    $teaser = trim(strip_tags($a['content'] ?? ''));

                    if (function_exists('mb_strlen') && mb_strlen($teaser) > 220) {
                        $teaser = mb_substr($teaser, 0, 220) . '…';
                    } elseif (strlen($teaser) > 220) {
                        $teaser = substr($teaser, 0, 220) . '…';
                    }
                    ?>

                    <div class="row pt-4 pb-1 align-items-start">

                        <?php if ($thumb): ?>
                            <div class="col-md-3 mb-3 mb-md-0">
                                <a href="<?= e($url) ?>">
                                    <img src="<?= e($thumb) ?>"
                                         class="img-fluid"
                                         alt="<?= e($title) ?>">
                                </a>
                            </div>

                            <div class="col-md-9">
                        <?php else: ?>

                            <div class="col-12">

                        <?php endif; ?>

                                <h2 class="font-weight-semibold h4 mb-2">
                                    <a href="<?= e($url) ?>" class="text-decoration-none">
                                        <?= e($title) ?>
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

                    <?php if (($index + 1) < $totalArticles): ?>
                        <hr class="my-4">
                    <?php endif; ?>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="row g-4">

                    <?php foreach ($articlesArray as $a): ?>

                        <?php
                        $url = '/' . ltrim($articlesBase, '/') . '/' . $a['slug'];
                        $thumb = trim($a['thumbnail'] ?? '');
                        $title = $a['title'] ?? '';
                        $teaser = trim(strip_tags($a['content'] ?? ''));

                        if (function_exists('mb_strlen') && mb_strlen($teaser) > 140) {
                            $teaser = mb_substr($teaser, 0, 140) . '…';
                        } elseif (strlen($teaser) > 140) {
                            $teaser = substr($teaser, 0, 140) . '…';
                        }
                        ?>

                        <div class="col-md-4">

                            <article class="card h-100 border-0 shadow-sm bg-white">

                                <?php if ($thumb): ?>
                                    <a href="<?= e($url) ?>">
                                        <img src="<?= e($thumb) ?>"
                                             class="card-img-top"
                                             alt="<?= e($title) ?>"
                                             style="height:260px;object-fit:cover;">
                                    </a>
                                <?php endif; ?>

                                <div class="card-body d-flex flex-column">

                                    <h3 class="h5 mb-2">
                                        <a href="<?= e($url) ?>"
                                           class="text-decoration-none text-dark">
                                            <?= e($title) ?>
                                        </a>
                                    </h3>

                                    <?php if (!empty($a['publish_date'])): ?>
                                        <div class="text-muted small mb-3">
                                            <?= e(date('j. n. Y', strtotime($a['publish_date']))) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($teaser): ?>
                                        <p class="mb-4">
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

            <?php endif; ?>

        <?php endif; ?>

        <?php if (!empty($block['button_text']) && !empty($block['button_url'])): ?>
            <div class="text-center mt-4">
                <a href="<?= e($block['button_url']) ?>"
                   class="btn btn-outline btn-rounded btn-primary btn-4 font-weight-bold mt-3 appear-animation animated appear-animation-visible">
                    <?= e($block['button_text']) ?>
                </a>
            </div>
        <?php endif; ?>

    </div>
</section>