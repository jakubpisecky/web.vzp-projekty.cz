<?php
// Base URL sekce (pro odkaz "Zpět na alba") – vezme 1. segment z URL, pokud nepřišel z routeru
if (empty($galleryBase)) {
  $parts       = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
  $galleryBase = '/' . ($parts[0] ?? '');
}

// k dispozici: $gallery (z index.php), $galleryBase, $conn, e()
if (!$gallery) {
  http_response_code(404);
  $title = 'Album nenalezeno';
  $meta_description = '';
  $content = '<div class="container py-5"><h1>404</h1><p>Album nebylo nalezeno.</p></div>';
  include __DIR__ . '/layout.php';
  return;
}


// fotky alba
$photos = [];
$stmt = $conn->prepare("
  SELECT id, filename, title, sort_order
  FROM gallery_photos
  WHERE gallery_id = ?
  ORDER BY sort_order ASC, id ASC
");
$stmt->bind_param("i", $gallery['id']);
$stmt->execute();
$res = $stmt->get_result();
while ($p = $res->fetch_assoc()) $photos[] = $p;
$stmt->close();

// Drobečkovka pro detail galerie

$breadcrumbs = [];

// 1) Homepage
$breadcrumbs[] = [
    'label' => 'Domů',
    'href'  => '/',          // může být i null, pokud ji nechceš klikací
];

// 2) Sekce / stránka, pod kterou galerie běží
if (!empty($section)) {
    $breadcrumbs[] = [
        'label' => $section['title'] ?? 'Fotogalerie',
        'href'  => '/' . ($section['slug'] ?? ''),
    ];
} elseif (!empty($page)) { // fallback, kdybys pracoval s $page
    $breadcrumbs[] = [
        'label' => $page['title'] ?? 'Fotogalerie',
        'href'  => '/' . ($page['slug'] ?? ''),
    ];
}

// 3) Aktuální galerie – poslední, neklikací
if (!empty($gallery)) {
    $breadcrumbs[] = [
        'label' => $gallery['title'] ?? 'Detail galerie',
        'href'  => null,      // aktuální stránka → bez odkazu
    ];
}

ob_start(); ?>


<div role="main" class="main">
  <section class="page-header">
					<div class="container">
						<div class="row align-items-center">
							<div class="col-md-8 text-start">
								<h1 class="font-weight-bold"><?= e($gallery['title']) ?></h1>
							</div>
							<div class="col-md-4">
								  <?php render_breadcrumbs($breadcrumbs ?? []); ?>
							</div>
						</div>
					</div>
		</section>
  <div class="container">
					<div class="row">
						<div class="col-md-12">
               <div class="content">
                <?php if (empty($photos)): ?>
                  <p class="text-muted">V tomto albu zatím nejsou fotografie.</p>
                <?php else: ?>
                  <div class="row g-3">
                    <?php foreach ($photos as $ph): ?>
                      <?php
                        $url   = gallery_photo_url((string)$ph['filename'], (int)$gallery['id']);
                        $title = $ph['title'] ?? $gallery['title'] ?? '';
                      ?>
                      <div class="col-6 col-md-4 col-lg-3">
                        <a
                          href="<?= e($url) ?>"
                          class="glightbox d-block"
                          data-gallery="alb-<?= (int)$gallery['id'] ?>"
                          data-type="image"
                          data-title="<?= e($title) ?>"
                          >
                          <img src="<?= e($url) ?>" alt="<?= e($title) ?>" class="img-fluid rounded border" loading="lazy">
                        </a>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
						</div>
					</div>
				</div>

</div>

<div class="container py-4">
  <nav class="mb-3">
    <a href="<?= e($galleryBase) ?>" class="btn btn-outline-secondary">&larr; Zpět na alba</a>
  </nav>
</div>

<!-- Lightbox: CSS + JS z CDN a inicializace -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js" defer></script>
<script defer>
document.addEventListener('DOMContentLoaded', function () {
  GLightbox({
    selector: '.glightbox',
    touchNavigation: true,
    loop: true,
    closeOnOutsideClick: true
  });
});
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
