<?php

$meta_description = $meta_description
    ?? ($page['meta_description'] ?? '');

$meta_image = $meta_image ?? null;

$showBreadcrumbs = (int)($page['show_breadcrumbs'] ?? 1) === 1;

$breadcrumbs = page_breadcrumbs(
    $conn,
    $page
);

/*
 * Levá navigace.
 */
$sidebarNavigation = null;

$sidebarNavigationId = (int)(
    $page['sidebar_navigation_id'] ?? 0
);

if ($sidebarNavigationId > 0) {
    $sidebarNavigation = frontend_navigation_get(
        $conn,
        $sidebarNavigationId
    );
}

$currentPath = parse_url(
    $_SERVER['REQUEST_URI'],
    PHP_URL_PATH
) ?: '/';

ob_start();
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

                        <?php
                        render_breadcrumbs(
                            $breadcrumbs
                        );
                        ?>

                    </div>

                </div>

            </div>

        </section>

    <?php endif; ?>


    <?php if ($sidebarNavigation): ?>

        <div class="container">

            <div class="row">

                <aside class="col-lg-3 mb-4 mb-lg-0">

                    <div class="sidebar-navigation-wrap">

                        <div class="sidebar-navigation-title">
                            <?= e($sidebarNavigation['name']) ?>
                        </div>

                        <?php
                        render_sidebar_navigation(
                            $conn,
                            $sidebarNavigation,
                            $currentPath
                        );
                        ?>

                    </div>

                </aside>

                <div class="col-lg-9">

                    <?php
                    renderPageBlocks(
                        $conn,
                        (int)$page['id']
                    );
                    ?>

                </div>

            </div>

        </div>

    <?php else: ?>

        <?php
        renderPageBlocks(
            $conn,
            (int)$page['id']
        );
        ?>

    <?php endif; ?>

</div>

<?php

$content = ob_get_clean();

include __DIR__ . '/layout.php';