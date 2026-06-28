<?php
$sectionClass = trim($block['section_class'] ?? '');
$imagePosition = $block['image_position'] ?? 'right';
$imageLeft = $imagePosition === 'left';

ob_start();
?>

<div class="col-lg-8 mb-4 mb-lg-0">
    <?php if (!empty($block['subtitle'])): ?>
        <span class="top-sub-title text-primary">
            <?= e($block['subtitle']) ?>
        </span>
    <?php endif; ?>

    <?php if (!empty($block['title'])): ?>
        <h1 class="font-weight-bold mb-3">
            <?= e($block['title']) ?>
        </h1>
    <?php endif; ?>

    <?php if (!empty($block['content'])): ?>
        <div class="mb-4">
            <?= $block['content'] ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($block['button_text']) && !empty($block['button_url'])): ?>
        <a href="<?= e($block['button_url']) ?>"
           class="btn btn-outline btn-rounded btn-primary btn-4 font-weight-bold mt-3 appear-animation animated appear-animation-visible">
            <?= e($block['button_text']) ?>
        </a>
    <?php endif; ?>
</div>

<?php
$textColumn = ob_get_clean();

ob_start();
?>

<?php if (!empty($block['image'])): ?>
    <div class="col-lg-4">
        <img src="<?= e($block['image']) ?>"
             alt="<?= e($block['title'] ?? '') ?>"
             class="img-fluid rounded">
    </div>
<?php endif; ?>

<?php
$imageColumn = ob_get_clean();
?>

<section class="section <?= e($sectionClass) ?>">
    <div class="container">
        <div class="row align-items-center">

            <?php if ($imageLeft): ?>
                <?= $imageColumn ?>
                <?= $textColumn ?>
            <?php else: ?>
                <?= $textColumn ?>
                <?= $imageColumn ?>
            <?php endif; ?>

        </div>
    </div>
</section>