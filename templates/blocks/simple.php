<?php

$type = trim((string)($block['type'] ?? ''));

$blockData = [];

if (!empty($block['block_data'])) {

    $decoded = json_decode(
        (string)$block['block_data'],
        true
    );

    if (is_array($decoded)) {
        $blockData = $decoded;
    }
}

$sectionClass = trim(
    (string)($block['section_class'] ?? '')
);

/*
 * ==========================================================
 * ALERT
 * ==========================================================
 */
if ($type === 'alert') {

    $text = trim(
        (string)($blockData['text'] ?? '')
    );

    if ($text === '') {
        return;
    }

    $variant = trim(
        (string)($blockData['variant'] ?? 'warning')
    );

    $allowedVariants = [
        'primary',
        'secondary',
        'success',
        'danger',
        'warning',
        'info',
        'light',
        'dark',
    ];

    if (!in_array($variant, $allowedVariants, true)) {
        $variant = 'warning';
    }

    $dismissible =
        (int)($blockData['dismissible'] ?? 0) === 1;

    ?>

    <section class="section <?= e($sectionClass) ?>">
        <div class="container">

            <div
                class="alert alert-<?= e($variant) ?><?= $dismissible ? ' alert-dismissible fade show' : '' ?>"
                role="alert"
            >

                <?= nl2br(e($text)) ?>

                <?php if ($dismissible): ?>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                        aria-label="Zavřít"
                    ></button>
                <?php endif; ?>

            </div>

        </div>
    </section>

    <?php

    return;
}

/*
 * ==========================================================
 * DIVIDER
 * ==========================================================
 */
if ($type === 'divider') {
    ?>
    <section class="section <?= e($sectionClass) ?>">
        <div class="container">
            <hr>
        </div>
    </section>
    <?php
    return;
}

/*
 * ==========================================================
 * BUTTON
 * ==========================================================
 */
if ($type === 'button') {

    $text = trim(
        (string)($blockData['text'] ?? '')
    );

    $url = trim(
        (string)($blockData['url'] ?? '')
    );

    if ($text === '' || $url === '') {
        return;
    }

    $style = trim(
        (string)($blockData['style'] ?? 'primary')
    );

    $allowedStyles = [
        'primary',
        'secondary',
        'outline-primary',
        'outline-secondary',
    ];

    if (!in_array($style, $allowedStyles, true)) {
        $style = 'primary';
    }

    $alignment = trim(
        (string)($blockData['alignment'] ?? 'left')
    );

    $alignmentClasses = [
        'left'   => 'text-start',
        'center' => 'text-center',
        'right'  => 'text-end',
    ];

    $alignmentClass =
        $alignmentClasses[$alignment]
        ?? 'text-start';

    $targetBlank =
        (int)($blockData['target_blank'] ?? 0) === 1;

    ?>

    <section class="section <?= e($sectionClass) ?>">
        <div class="container">

            <div class="<?= e($alignmentClass) ?>">

                <a
                    href="<?= e($url) ?>"
                    class="btn btn-<?= e($style) ?> btn-3"
                    <?php if ($targetBlank): ?>
                        target="_blank"
                        rel="noopener noreferrer"
                    <?php endif; ?>
                >
                    <?= e($text) ?>
                </a>

            </div>

        </div>
    </section>

    <?php

    return;
}


/*
 * ==========================================================
 * BOXES
 * ==========================================================
 */
if ($type === 'boxes') {

    $title = trim(
        (string)($blockData['title'] ?? '')
    );

    $text = trim(
        (string)($blockData['text'] ?? '')
    );

    $columns = (int)($blockData['columns'] ?? 2);

    if (!in_array($columns, [2, 3, 4], true)) {
        $columns = 2;
    }

    $items = $blockData['items'] ?? [];

    if (!is_array($items)) {
        $items = [];
    }

    /*
     * Odstraníme případné neplatné/prázdné položky.
     * Nadpis boxu je povinný v administraci, ale frontend
     * se chová bezpečně i při starších nebo ručně upravených datech.
     */
    $items = array_values(
        array_filter(
            $items,
            static function ($item): bool {
                return is_array($item)
                    && trim((string)($item['title'] ?? '')) !== '';
            }
        )
    );

    if (
        $title === ''
        && $text === ''
        && !$items
    ) {
        return;
    }

    $columnClasses = [
        2 => 'col-12 col-md-6',
        3 => 'col-12 col-md-6 col-lg-4',
        4 => 'col-12 col-md-6 col-lg-3',
    ];

    $columnClass =
        $columnClasses[$columns]
        ?? $columnClasses[2];

    /*
     * Barevná varianta boxu.
     *
     * U světlého boxu používáme tmavý text a primární tlačítko.
     * U barevných boxů bílý text a světlé tlačítko,
     * aby byl vždy zachovaný dostatečný kontrast.
     */
    $colorMap = [
        'light' => [
            'box' => 'bg-light text-dark',
            'button' => 'btn-primary',
        ],
        'primary' => [
            'box' => 'bg-primary text-white',
            'button' => 'btn-light',
        ],
        'secondary' => [
            'box' => 'bg-secondary text-white',
            'button' => 'btn-light',
        ],
        'blue' => [
            'box' => 'bg-info text-white',
            'button' => 'btn-light',
        ],
        'green' => [
            'box' => 'bg-success text-white',
            'button' => 'btn-light',
        ],
    ];

    ?>

    <section class="section <?= e($sectionClass) ?>">
        <div class="container">

            <?php if ($title !== '' || $text !== ''): ?>

                <div class="mb-4">

                    <?php if ($title !== ''): ?>
                        <h2>
                            <?= e($title) ?>
                        </h2>
                    <?php endif; ?>

                    <?php if ($text !== ''): ?>
                        <div class="mt-3">
                            <?= nl2br(e($text)) ?>
                        </div>
                    <?php endif; ?>

                </div>

            <?php endif; ?>

            <?php if ($items): ?>

                <div class="row g-4">

                    <?php foreach ($items as $item): ?>

                        <?php
                        $itemTitle = trim(
                            (string)($item['title'] ?? '')
                        );

                        $itemText = trim(
                            (string)($item['text'] ?? '')
                        );

                        $buttonText = trim(
                            (string)($item['button_text'] ?? '')
                        );

                        $buttonUrl = trim(
                            (string)($item['button_url'] ?? '')
                        );

                        $targetBlank =
                            (int)($item['target_blank'] ?? 0) === 1;

                        $color = trim(
                            (string)($item['color'] ?? 'light')
                        );

                        if (!isset($colorMap[$color])) {
                            $color = 'light';
                        }

                        $boxClass =
                            $colorMap[$color]['box'];

                        $buttonClass =
                            $colorMap[$color]['button'];
                        ?>

                        <div class="<?= e($columnClass) ?> d-flex">

                            <div
                                class="card border-0 h-100 w-100 <?= e($boxClass) ?>"
                            >

                                <div class="card-body p-4 p-lg-5">

                                    <h3 class="card-title mb-3">
                                        <?= e($itemTitle) ?>
                                    </h3>

                                    <?php if ($itemText !== ''): ?>

                                        <div class="card-text mb-4">
                                            <?= nl2br(e($itemText)) ?>
                                        </div>

                                    <?php endif; ?>

                                    <?php if (
                                        $buttonText !== ''
                                        && $buttonUrl !== ''
                                    ): ?>

                                        <a
                                            href="<?= e($buttonUrl) ?>"
                                            class="btn <?= e($buttonClass) ?> btn-3"
                                            <?php if ($targetBlank): ?>
                                                target="_blank"
                                                rel="noopener noreferrer"
                                            <?php endif; ?>
                                        >
                                            <?= e($buttonText) ?>
                                        </a>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>
    </section>

    <?php

    return;
}


/*
 * ==========================================================
 * YOUTUBE
 * ==========================================================
 */
if ($type === 'youtube') {

    $url = trim(
        (string)($blockData['url'] ?? '')
    );

    if ($url === '') {
        return;
    }

    $videoId = '';

    /*
     * youtube.com/watch?v=...
     */
    $query = parse_url($url, PHP_URL_QUERY);

    if ($query) {

        parse_str($query, $queryParams);

        if (!empty($queryParams['v'])) {
            $videoId = trim(
                (string)$queryParams['v']
            );
        }
    }

    /*
     * youtu.be/...
     */
    if ($videoId === '') {

        $host = strtolower(
            (string)parse_url($url, PHP_URL_HOST)
        );

        $path = trim(
            (string)parse_url($url, PHP_URL_PATH),
            '/'
        );

        if (
            $host === 'youtu.be'
            || $host === 'www.youtu.be'
        ) {
            $videoId = explode('/', $path)[0] ?? '';
        }
    }

    /*
     * youtube.com/embed/...
     * youtube.com/shorts/...
     */
    if ($videoId === '') {

        $path = trim(
            (string)parse_url($url, PHP_URL_PATH),
            '/'
        );

        $parts = explode('/', $path);

        if (
            isset($parts[0], $parts[1])
            && in_array(
                $parts[0],
                ['embed', 'shorts'],
                true
            )
        ) {
            $videoId = $parts[1];
        }
    }

    $videoId = preg_replace(
        '/[^a-zA-Z0-9_-]/',
        '',
        $videoId
    );

    if ($videoId === '') {
        return;
    }

    $ratio = trim(
        (string)($blockData['ratio'] ?? '16x9')
    );

    $allowedRatios = [
        '16x9',
        '21x9',
        '4x3',
        '1x1',
    ];

    if (!in_array($ratio, $allowedRatios, true)) {
        $ratio = '16x9';
    }

    ?>

    <section class="section <?= e($sectionClass) ?>">
        <div class="container">

            <div class="ratio ratio-<?= e($ratio) ?>">

                <iframe
                    src="https://www.youtube.com/embed/<?= e($videoId) ?>"
                    title="YouTube video"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen
                ></iframe>

            </div>

        </div>
    </section>

    <?php

    return;
}


/*
 * ==========================================================
 * MAP
 * ==========================================================
 */
if ($type === 'map') {

    $address = trim(
        (string)($blockData['address'] ?? '')
    );

    if ($address === '') {
        return;
    }

    $height = (int)(
        $blockData['height'] ?? 450
    );

    if ($height < 200) {
        $height = 200;
    }

    if ($height > 900) {
        $height = 900;
    }

    $mapUrl =
        'https://www.google.com/maps?q='
        . rawurlencode($address)
        . '&output=embed';

    ?>

    <section class="section <?= e($sectionClass) ?>">
        <div class="container">

            <iframe
                src="<?= e($mapUrl) ?>"
                width="100%"
                height="<?= $height ?>"
                frameborder="0"
                allowfullscreen
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                title="<?= e($address) ?>"
            ></iframe>

        </div>
    </section>

    <?php

    return;
}
