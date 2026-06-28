<?php
// /templates/blocks/image_slider_text.php

$images = array_filter(array_map('trim', explode(';', $block['images'] ?? '')));

$kicker   = trim($block['subtitle'] ?? '');
$headline = trim($block['title'] ?? '');
$content  = trim($block['content'] ?? '');

$buttonText = trim($block['button_text'] ?? '');
$buttonUrl  = trim($block['button_url'] ?? '');
?>

<section class="section <?= e($block['section_class'] ?? '') ?>">
    <div class="container">

        <div class="row align-items-center justify-content-center text-center text-md-start">

            <div class="col-md-7 pe-md-5 mb-5 mb-md-0">

                <?php if ($kicker !== ''): ?>
                    <div class="overflow-hidden">
                        <span class="d-block top-sub-title text-color-primary"
                              data-appear-animation="maskUp">
                            <?= e($kicker) ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if ($headline !== ''): ?>
                    <div class="overflow-hidden mb-2">
                        <h2 class="font-weight-bold mb-0"
                            data-appear-animation="maskUp"
                            data-appear-animation-delay="200">
                            <?= e($headline) ?>
                        </h2>
                    </div>
                <?php endif; ?>

                <?php if ($content !== ''): ?>
                    <div class="lead mb-4"
                         data-appear-animation="fadeInUpShorter"
                         data-appear-animation-delay="400">
                        <?= $content ?>
                    </div>
                <?php endif; ?>

                <?php if ($buttonText !== '' && $buttonUrl !== ''): ?>
                    <a href="<?= e($buttonUrl) ?>"
                       class="btn btn-outline btn-rounded btn-primary btn-4 font-weight-bold mt-3 appear-animation"
                       data-appear-animation="fadeInUpShorter"
                       data-appear-animation-delay="600">
                        <?= e($buttonText) ?>
                    </a>
                <?php endif; ?>

            </div>

            <?php if (!empty($images)): ?>
                <div class="col-9 col-md-5 px-lg-5"
                    data-appear-animation="fadeInRightShorter"
                    data-appear-animation-delay="800">

                    <div class="owl-carousel owl-theme nav-style-3 rounded-style-1"
                        data-plugin-options="{'items': 1, 'dots': false, 'nav': true, 'navtext': []}">

                        <?php foreach ($images as $img): ?>
                            <div>
                                <img src="<?= e($img) ?>"
                                    class="img-fluid"
                                    alt="<?= e($headline) ?>">
                            </div>
                        <?php endforeach; ?>

                    </div>

                </div>
            <?php endif; ?>

        </div>

    </div>
</section>