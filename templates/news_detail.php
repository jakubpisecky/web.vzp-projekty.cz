<?php
// /templates/news_detail.php  (očekává $article)
if (!$article) {
  http_response_code(404);
  $title = 'Článek nenalezen';
  $meta_description = '';
  $content = '<div class="container py-5"><h1>404</h1><p>Článek nebyl nalezen.</p></div>';
  include __DIR__ . '/layout.php';
  return;
}

// Per-článek meta
$meta_description = $meta_description ?? text_excerpt($article['content'] ?? '', 180);
$meta_image       = !empty($article['thumbnail']) ? media_url($article['thumbnail']) : null;

// Base sekce (slug + label) – univerzálně
$seg0     = url_segments()[0] ?? '';
$baseSlug = $section['slug'] ?? $seg0;
$base     = $articlesBase ?? ('/' . $baseSlug);

// label sekce: 1) z $section  2) z DB  3) humanizovaný slug
$baseLabel = $section['title'] ?? null;
if (!$baseLabel && ($conn instanceof mysqli) && $baseSlug) {
  $stmt = $conn->prepare("SELECT title FROM pages WHERE slug = ? LIMIT 1");
  $stmt->bind_param("s", $baseSlug);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  if (!empty($row['title'])) $baseLabel = $row['title'];
}
if (!$baseLabel && $baseSlug) {
  $baseLabel = mb_convert_case(str_replace('-', ' ', $baseSlug), MB_CASE_TITLE, 'UTF-8');
}

// Breadcrumbs
$breadcrumbs = [
  ['label' => 'Domů', 'href' => '/'],
  ['label' => $baseLabel, 'href' => rtrim($base,'/')],
  ['label' => $article['title'] ?? '', 'href' => rtrim($base,'/').'/'.($article['slug'] ?? '')],
];

ob_start(); ?>

<div role="main" class="main">
  <section class="page-header">
					<div class="container">
						<div class="row align-items-center">
							<div class="col-md-8 text-start">
								<h1 class="font-weight-bold"><?= e($article['title']) ?></h1>
                <?php if (!empty($article['publish_date'])): ?>
                <span class="tob-sub-title text-color-primary d-block"><?= e(date('j. n. Y', strtotime($article['publish_date']))) ?></span>
                <?php endif; ?>
							</div>
							<div class="col-md-4">
								  <?php render_breadcrumbs($breadcrumbs); ?>
							</div>
						</div>
					</div>
		</section>

   <div class="container">
        <div class="row">
          <div class="col-md-12">
            <div class="content">

              <article class="mx-auto">

                <?php if (!empty($article['thumbnail'])): ?>
                  <figure class="article-detail-thumb float-md-end ms-md-4 mb-4">
                    <img src="<?= e(media_url($article['thumbnail'])) ?>"
                         alt="<?= e($article['title'] ?? '') ?>"
                         class="img-fluid rounded">
                  </figure>
                <?php endif; ?>

                <div class="content">
                  <?= $article['content'] ?? '' ?>
                </div>

              </article>

            </div>
          </div>
        </div>
        
      </div>
      <?php renderArticleBlocks($conn, $article); ?>


</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
