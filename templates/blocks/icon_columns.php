<?php
$sectionClass = trim($block['section_class'] ?? 'bg-light-5');

$columns = [
    [
        'icon'   => $block['col1_icon'] ?? '',
        'kicker' => $block['col1_kicker'] ?? '',
        'title'  => $block['col1_title'] ?? '',
        'text'   => $block['col1_text'] ?? '',
        'animation' => 'fadeInLeftShorter',
        'delay' => 600,
    ],
    [
        'icon'   => $block['col2_icon'] ?? '',
        'kicker' => $block['col2_kicker'] ?? '',
        'title'  => $block['col2_title'] ?? '',
        'text'   => $block['col2_text'] ?? '',
        'animation' => 'fadeInUpShorter',
        'delay' => 900,
    ],
    [
        'icon'   => $block['col3_icon'] ?? '',
        'kicker' => $block['col3_kicker'] ?? '',
        'title'  => $block['col3_title'] ?? '',
        'text'   => $block['col3_text'] ?? '',
        'animation' => 'fadeInRightShorter',
        'delay' => 1200,
    ],
];
?>

<section id="start" class="section <?= e($sectionClass) ?> curved-border">
    <div class="container container-lg-custom">
        <div class="row align-items-baseline px-lg-4 mt-2">

            <?php foreach ($columns as $col): ?>
                <div class="col-lg-4">
                    <div class="icon-box icon-box-style-1 appear-animation"
                         data-appear-animation="<?= e($col['animation']) ?>"
                         data-appear-animation-delay="300">

                        <?php if (!empty($col['icon'])): ?>
                            <div class="icon-box-icon pe-3">
                                <img width="42"
                                     src="<?= e($col['icon']) ?>"
                                     alt=""
                                     data-icon
                                     data-plugin-options="{'color': '#2388ED', 'animated': true, 'delay': <?= (int)$col['delay'] ?>}" />
                            </div>
                        <?php endif; ?>

                        <div class="icon-box-info">

                            <?php if (!empty($col['kicker'])): ?>
                                <span class="top-sub-title">
                                    <?= e($col['kicker']) ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($col['title'])): ?>
                                <h2 class="line-height-1 text-5 mb-3">
                                    <?= e($col['title']) ?>
                                </h2>
                            <?php endif; ?>

                            <?php if (!empty($col['text'])): ?>
                                <p class="text-alternative-style pe-lg-4">
                                    <?= e($col['text']) ?>
                                </p>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </div>
</section>