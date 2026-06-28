<?php
ob_start();

$showBreadcrumbs = (int)($page['show_breadcrumbs'] ?? 1) === 1;

$breadcrumbs = [
    [
        'label' => 'Domů',
        'href'  => '/',
    ],
    [
        'label' => $page['title'] ?? '',
        'href'  => null,
    ],
];
?>

<div role="main" class="main">

    <?php if ($showBreadcrumbs): ?>
        <section class="page-header mb-0">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-8 text-start">
                        <h1 class="font-weight-bold">
                            <?= e($page['title'] ?? '') ?>
                        </h1>
                    </div>

                    <div class="col-md-4">
                        <?php render_breadcrumbs($breadcrumbs); ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php renderPageBlocks($conn, (int)$page['id']); ?>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';