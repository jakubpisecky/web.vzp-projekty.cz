<?php

$meta_description = $meta_description
  ?? ($page['meta_description'] ?? '')
  ?? text_excerpt($page['content'] ?? '', 180);
$meta_image = $meta_image ?? null; // pokud někdy přidáš thumbnail pro stránky, tady ho použij
$breadcrumbs = page_breadcrumbs($conn, $page);

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

// dostupné: $page (z routeru), $title, $meta_description
ob_start(); ?>
<div role="main" class="main">
  <section class="page-header">
					<div class="container">
						<div class="row align-items-center">
							<div class="col-md-8 text-start">
								<h1 class="font-weight-bold"><?= e($page['title'] ?? '') ?></h1>

							</div>
							<div class="col-md-4">
								  <?php render_breadcrumbs($breadcrumbs); ?>
							</div>
						</div>
					</div>
				</section>

<div class="container mb-5">

    <div class="row">

        <?php if ($sidebarNavigation): ?>

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

        <?php else: ?>

            <div class="col-12">

        <?php endif; ?>

                <div class="content">
                    <?= $page['content'] ?? '' ?>
                </div>

            </div>

    </div>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
