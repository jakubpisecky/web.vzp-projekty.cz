<?php
// /templates/blocks/latest_articles.php

$limit = (int)($block['article_limit'] ?? 3);
if ($limit <= 0) {
    $limit = 3;
}

$layout = $block['layout'] ?: 'cards';

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

$stmt = $conn->prepare("
    SELECT title, slug, thumbnail, content, publish_date
    FROM articles
    WHERE status = 'published'
    ORDER BY publish_date DESC, id DESC
    LIMIT ?
");
$stmt->bind_param("i", $limit);
$stmt->execute();
$articles = $stmt->get_result();
$stmt->close();
?>

<section class="section <?= e($block['section_class'] ?? '') ?>">
    <div class="container">

        <?php if (!empty($block['title'])): ?>
            <h2 class="font-weight-bold mb-4">
                <?= e($block['title']) ?>
            </h2>
        <?php endif; ?>

        <?php if ($articles->num_rows === 0): ?>

            <p class="text-muted">Zatím nejsou žádné články.</p>

        <?php else: ?>

            <?php if ($layout === 'list'): ?>

                <?php
                $articlesArray = [];

                while ($row = $articles->fetch_assoc()) {
                    $articlesArray[] = $row;
                }

                $totalArticles = count($articlesArray);
                ?>

                <?php foreach ($articlesArray as $index => $a): ?>

                    <?php
                    $url    = '/' . ltrim($articlesBase, '/') . '/' . $a['slug'];
                    $thumb  = trim($a['thumbnail'] ?? '');
                    $title  = $a['title'] ?? '';
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

    <?php while ($a = $articles->fetch_assoc()): ?>

        <?php
        $url    = '/' . ltrim($articlesBase, '/') . '/' . $a['slug'];
        $thumb  = trim($a['thumbnail'] ?? '');
        $title  = $a['title'] ?? '';
                    $teaser = trim(strip_tags($a['content'] ?? ''));

                    if (function_exists('mb_strlen') && mb_strlen($teaser) > 220) {
                        $teaser = mb_substr($teaser, 0, 220) . '…';
                    } elseif (strlen($teaser) > 220) {
                        $teaser = substr($teaser, 0, 220) . '…';
                    }

        if (function_exists('mb_strlen')) {
            if (mb_strlen($teaser) > 140) {
                $teaser = mb_substr($teaser, 0, 140) . '…';
            }
        } else {
            if (strlen($teaser) > 140) {
                $teaser = substr($teaser, 0, 140) . '…';
            }
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

    <?php endwhile; ?>

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