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