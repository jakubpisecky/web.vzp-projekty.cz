<?php

// Univerzální base URL sekce (bez pevného /fotogalerie)
if (empty($galleryBase)) {
  $firstSeg    = strtok(trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'), '/');
  $galleryBase = '/' . ($section['slug'] ?? $firstSeg ?? '');
}

// ===== breadcrumbs (Domů → název rubriky) =====
$seg0      = url_segments()[0] ?? '';
$baseSlug  = ltrim($section['slug'] ?? $seg0, '/');
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
$breadcrumbs = [
  ['label' => 'Domů', 'href' => '/'],
  ['label' => $baseLabel ?: 'Fotogalerie',   'href' => rtrim($galleryBase,'/')],
];

// ===== alba + cover z první fotky =====
$albums = [];
$sql = "
  SELECT
    g.id,
    g.title,
    g.slug,
    g.created_at,
    (
      SELECT gp.filename
      FROM gallery_photos gp
      WHERE gp.gallery_id = g.id
      ORDER BY gp.sort_order ASC, gp.id ASC
      LIMIT 1
    ) AS cover_filename
  FROM galleries g
  ORDER BY g.created_at DESC, g.id DESC
";
$res = $conn->query($sql);
while ($row = $res->fetch_assoc()) $albums[] = $row;

ob_start(); ?>


<div role="main" class="main">
  <section class="page-header">
					<div class="container">
						<div class="row align-items-center">
							<div class="col-md-8 text-start">
								<h1 class="font-weight-bold"><?= e($section['title'] ?? $baseLabel ?? 'Fotogalerie') ?></h1>

							</div>
							<div class="col-md-4">
								  <?php render_breadcrumbs($breadcrumbs); ?>
							</div>
						</div>
					</div>
				</section>

  <div class="container mb-5">
					<div class="row">
						 <?php foreach ($albums as $g): ?>
              <div class="col-12 col-sm-6 col-lg-4">
                <article class="card h-100 shadow-sm">
                  <?php if (!empty($g['cover_filename'])): ?>
                    <img src="<?= e(gallery_photo_url((string)$g['cover_filename'], (int)$g['id'])) ?>" class="card-img-top" alt="<?= e($g['title']) ?>">
                  <?php else: ?>
                    <div class="ratio ratio-16x9 bg-light"></div>
                  <?php endif; ?>
                  <div class="card-body">
                    <h2 class="h5 card-title mb-2">
                      <a class="stretched-link text-decoration-none" href="<?= e($galleryBase) ?>/<?= e($g['slug']) ?>">
                        <?= e($g['title']) ?>
                      </a>
                    </h2>
                    <?php if (!empty($g['created_at'])): ?>
                      <div class="text-muted small"><?= e(date('j. n. Y', strtotime($g['created_at']))) ?></div>
                    <?php endif; ?>
                  </div>
                </article>
              </div>
            <?php endforeach; ?>
					</div>
    </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
