<?php

/*
 * Dostupné proměnné z routeru:
 *
 * $page   = stránka se šablonou team
 * $member = konkrétní člen týmu
 */

$title = $member['name'] ?? '';

$meta_description = text_excerpt(
    $member['description'] ?? '',
    180
);

$meta_image = !empty($member['photo'])
    ? $member['photo']
    : null;

$breadcrumbs = page_breadcrumbs($conn, $page);

$breadcrumbs[] = [
    'label' => $member['name'] ?? '',
    'url'   => null,
];

$teamBaseUrl = '/'
    . trim($page['slug'] ?? 'tym', '/');

$phoneLink = !empty($member['phone'])
    ? preg_replace('/[^0-9+]/', '', $member['phone'])
    : '';

ob_start();
?>

<div role="main" class="main">

    <section class="page-header">
        <div class="container">
            <div class="row align-items-center">

                <div class="col-md-8 text-start">

                    <h1 class="font-weight-bold">
                        <?= e($member['name'] ?? '') ?>
                    </h1>

                </div>

                <div class="col-md-4">
                    <?php render_breadcrumbs($breadcrumbs); ?>
                </div>

            </div>
        </div>
    </section>

    <section class="section pt-4">
        <div class="container">

            <div class="row align-items-start">

                <?php if (!empty($member['photo'])): ?>

                    <div class="col-md-4 col-lg-3 mb-4 mb-md-0 text-center">

                        <img
                            src="<?= e($member['photo']) ?>"
                            alt="<?= e($member['name'] ?? '') ?>"
                            class="img-fluid rounded-circle border"
                            style="width:240px;height:240px;object-fit:cover;"
                        >

                    </div>

                    <div class="col-md-8 col-lg-9">

                <?php else: ?>

                    <div class="col-12">

                <?php endif; ?>

                        <h2 class="font-weight-bold mb-1">
                            <?= e($member['name'] ?? '') ?>
                        </h2>

                        <?php if (!empty($member['position'])): ?>

                            <div class="text-muted h5 mb-4">
                                <?= e($member['position']) ?>
                            </div>

                        <?php endif; ?>

                        <?php if (
                            !empty($member['email'])
                            || !empty($member['phone'])
                        ): ?>

                            <div class="mb-4">

                                <?php if (!empty($member['email'])): ?>

                                    <div class="mb-2">

                                        <a href="mailto:<?= e($member['email']) ?>">

                                            <i class="fas fa-envelope me-2"></i>

                                            <?= e($member['email']) ?>

                                        </a>

                                    </div>

                                <?php endif; ?>

                                <?php if (!empty($member['phone'])): ?>

                                    <div>

                                        <a href="tel:<?= e($phoneLink) ?>">

                                            <i class="fas fa-phone me-2"></i>

                                            <?= e($member['phone']) ?>

                                        </a>

                                    </div>

                                <?php endif; ?>

                            </div>

                        <?php endif; ?>

                        <?php if (!empty($member['description'])): ?>

                            <div class="content">
                                <?= $member['description'] ?>
                            </div>

                        <?php endif; ?>

                    </div>

            </div>

            <div class="mt-5 text-center">

                <a
                    href="<?= e($teamBaseUrl) ?>"
                    class="btn btn-primary btn-3"
                >
                    Zpět na tým
                </a>

            </div>

        </div>
    </section>

</div>

<?php

$content = ob_get_clean();

include __DIR__ . '/layout.php';