<?php
// /templates/blocks/gallery.php

$galleryId = (int)($block['gallery_id'] ?? 0);

if ($galleryId <= 0) {
    return;
}

$stmt = $conn->prepare("
    SELECT title
    FROM galleries
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $galleryId);
$stmt->execute();
$blockGallery = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$blockGallery) {
    return;
}

$photos = [];

$stmt = $conn->prepare("
    SELECT id, filename, title, sort_order
    FROM gallery_photos
    WHERE gallery_id = ?
    ORDER BY sort_order ASC, id ASC
");
$stmt->bind_param("i", $galleryId);
$stmt->execute();
$res = $stmt->get_result();

while ($p = $res->fetch_assoc()) {
    $photos[] = $p;
}

$stmt->close();

$blockGalleryDomId = 'block-gallery-' . (int)$block['id'];
?>

<section class="section <?= e($block['section_class'] ?? '') ?>">
    <div class="container">

        <h2 class="font-weight-bold mb-4">
            <?= e($block['title'] ?: ($blockGallery['title'] ?? 'Fotogalerie')) ?>
        </h2>

        <?php if (empty($photos)): ?>

            <p class="text-muted">Fotogalerie zatím neobsahuje žádné fotografie.</p>

        <?php else: ?>

            <div class="row g-3">
                <?php foreach ($photos as $ph): ?>
                    <?php
                    $url = function_exists('gallery_photo_url')
                        ? gallery_photo_url((string)$ph['filename'], $galleryId)
                        : '/uploads/galleries/' . $galleryId . '/' . $ph['filename'];

                    $photoTitle = $ph['title'] ?? $blockGallery['title'] ?? '';
                    ?>

                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="<?= e($url) ?>"
                           class="glightbox d-block"
                           data-gallery="<?= e($blockGalleryDomId) ?>"
                           data-type="image"
                           data-title="<?= e($photoTitle) ?>">
                            <img src="<?= e($url) ?>"
                                 alt="<?= e($photoTitle) ?>"
                                 class="img-fluid rounded border"
                                 loading="lazy">
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </div>
</section>

<?php if (!defined('GLIGHTBOX_BLOCK_LOADED')): ?>
    <?php define('GLIGHTBOX_BLOCK_LOADED', true); ?>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
    <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js" defer></script>

    <script defer>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof GLightbox !== 'undefined') {
            GLightbox({
                selector: '.glightbox',
                touchNavigation: true,
                loop: true,
                closeOnOutsideClick: true
            });
        }
    });
    </script>
<?php endif; ?>