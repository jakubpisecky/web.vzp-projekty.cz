<?php
$sectionClass = trim($block['section_class'] ?? '');
$imagePosition = $block['image_position'] ?? 'right';
$imageLeft = $imagePosition === 'left';

ob_start();
?>

<div class="col-md-6 mb-4 mb-md-0">

    <?php if (!empty($block['subtitle'])): ?>
        <div class="appear-animation"
             data-appear-animation="fadeInUpShorter"
             data-appear-animation-delay="100">
            <span class="top-sub-title">
                <?= e($block['subtitle']) ?>
            </span>
        </div>
    <?php endif; ?>

    <?php if (!empty($block['title'])): ?>
        <div class="appear-animation mb-3"
             data-appear-animation="fadeInUpShorter"
             data-appear-animation-delay="200">
            <h2 class="word-rotator letters type text-6 mb-3">
                <?= e($block['title']) ?>
            </h2>
        </div>
    <?php endif; ?>

    <?php if (!empty($block['content'])): ?>
        <div class="lead text-4 font-weight-light pe-md-4 mb-3 appear-animation"
             data-appear-animation="fadeInUpShorter"
             data-appear-animation-delay="300">
            <?= $block['content'] ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($block['button_text']) && !empty($block['button_url'])): ?>
        <a href="<?= e($block['button_url']) ?>"
           class="btn btn-outline btn-rounded btn-primary btn-4 font-weight-bold mt-3 appear-animation"
           data-appear-animation="fadeInUpShorter"
           data-appear-animation-delay="700">
            <?= e($block['button_text']) ?>
        </a>
    <?php endif; ?>

</div>

<?php
$textColumn = ob_get_clean();

ob_start();
?>

<?php if (!empty($block['image'])): ?>
    <div class="col-10 col-md-5 mx-auto <?= $imageLeft ? 'me-md-auto' : 'ms-md-auto' ?> appear-animation"
         data-appear-animation="fadeInUpShorter"
         data-appear-animation-delay="500">

        <div class="particles d-flex align-items-center <?= $imageLeft ? 'ps-0 ps-lg-3 ps-xl-5' : 'pe-0 pe-lg-3 pe-xl-5' ?>">
            <div class="particles-rect bg-primary d-none d-md-block"
                 data-plugin-float-element
                 data-plugin-options="{'startPos': 'top', 'speed': 4, 'transition': true}">
            </div>

            <img src="<?= e($block['image']) ?>"
                 class="img-fluid box-shadow-5"
                 alt="<?= e($block['title'] ?? '') ?>"
                 data-plugin-float-element
                 data-plugin-options="{'startPos': 'top', 'speed': 4, 'horizontal': true, 'transition': true}">
        </div>

    </div>
<?php endif; ?>

<?php
$imageColumn = ob_get_clean();
?>

<section class="section <?= e($sectionClass) ?>">
    <div class="container mb-3">
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