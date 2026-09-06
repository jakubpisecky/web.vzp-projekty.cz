<?php
ob_start();

/*
 * Základní hodnoty
 */
$workplaceId = (int)($workplace['id'] ?? 0);
$workplacePageId = (int)($workplacePage['id'] ?? 0);
$mainPageId = (int)($workplace['main_page_id'] ?? 0);

$isMainPage = $workplacePageId === $mainPageId;

$workplaceName = trim(
    (string)($workplace['name'] ?? '')
);

$workplaceShortName = trim(
    (string)($workplace['short_name'] ?? '')
);

$currentPageTitle = trim(
    (string)($workplacePage['title'] ?? '')
);

$baseUrl = rtrim(
    (string)$workplacesBase,
    '/'
);

$workplaceUrl = $baseUrl
    . '/'
    . rawurlencode(
        (string)($workplace['slug'] ?? '')
    );

$showBreadcrumbs = (int)(
    $workplacePage['show_breadcrumbs']
    ?? 1
) === 1;

/*
 * Breadcrumbs
 */
$breadcrumbs = [
    [
        'label' => 'Domů',
        'href'  => '/',
    ],
    [
        'label' => $workplacesSection['title']
            ?? 'Pracoviště',
        'href'  => $baseUrl,
    ],
];

if ($isMainPage) {
    $breadcrumbs[] = [
        'label' => $workplaceName,
        'href'  => null,
    ];
} else {
    $breadcrumbs[] = [
        'label' => $workplaceName,
        'href'  => $workplaceUrl,
    ];

    $breadcrumbs[] = [
        'label' => $currentPageTitle,
        'href'  => null,
    ];
}

/*
 * Kontaktní údaje
 */
$email = trim(
    (string)($workplace['email'] ?? '')
);

$phone = trim(
    (string)($workplace['phone'] ?? '')
);

$building = trim(
    (string)($workplace['building'] ?? '')
);

$floor = trim(
    (string)($workplace['floor'] ?? '')
);

$address = trim(
    (string)($workplace['address'] ?? '')
);

$locationName = trim(
    (string)($workplace['location_name'] ?? '')
);

$typeName = trim(
    (string)($workplace['type_name'] ?? '')
);

$perex = trim(
    (string)($workplace['perex'] ?? '')
);

$logo = trim(
    (string)($workplace['logo'] ?? '')
);

$thumbnail = trim(
    (string)($workplace['thumbnail'] ?? '')
);

/*
 * Pomocná funkce pro URL položky navigace.
 */
$buildNavigationUrl = static function (
    array $item
) use (
    $workplaceUrl
): string {
    $routeSlug = trim(
        (string)($item['route_slug'] ?? '')
    );

    if (
        !empty($item['is_main'])
        || $routeSlug === ''
    ) {
        return $workplaceUrl;
    }

    return $workplaceUrl
        . '/'
        . rawurlencode($routeSlug);
};

/*
 * Pomocná funkce pro aktivní položku.
 */
$isNavigationItemActive = static function (
    array $item
) use (
    $workplacePageId,
    $workplacePageSlug
): bool {
    $itemPageId = (int)($item['page_id'] ?? 0);

    if (
        $itemPageId > 0
        && $itemPageId === $workplacePageId
    ) {
        return true;
    }

    $itemSlug = trim(
        (string)($item['route_slug'] ?? '')
    );

    return $itemSlug !== ''
        && $itemSlug === $workplacePageSlug;
};
?>

<div role="main" class="main">

    <?php if ($showBreadcrumbs): ?>

        <section class="page-header mb-0">

            <div class="container">

                <div class="row align-items-center">

                    <div class="col-md-7 text-start">

                        <div class="d-flex align-items-center gap-3">

                            <?php if ($logo !== ''): ?>

                                <div class="flex-shrink-0">

                                    <img
                                        src="<?= e(media_url($logo)) ?>"
                                        alt="<?= e($workplaceName) ?>"
                                        class="img-fluid"
                                        style="max-width: 90px; max-height: 70px; object-fit: contain;">

                                </div>

                            <?php endif; ?>

                            <div>

                                <?php if (
                                    !$isMainPage
                                    && $currentPageTitle !== ''
                                ): ?>

                                    <div class="text-muted mb-1">
                                        <?= e($workplaceName) ?>
                                    </div>

                                    <h1 class="font-weight-bold mb-0">
                                        <?= e($currentPageTitle) ?>
                                    </h1>

                                <?php else: ?>

                                    <h1 class="font-weight-bold mb-0">
                                        <?= e($workplaceName) ?>
                                    </h1>

                                    <?php if (
                                        $workplaceShortName !== ''
                                    ): ?>

                                        <div class="mt-1">
                                            <?= e($workplaceShortName) ?>
                                        </div>

                                    <?php endif; ?>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                    <div class="col-md-5 mt-3 mt-md-0">

                        <?php render_breadcrumbs($breadcrumbs); ?>

                    </div>

                </div>

            </div>

        </section>

    <?php endif; ?>

    <?php if ($workplaceNavigation): ?>

        <nav
            class="workplace-navigation border-bottom bg-white"
            aria-label="Navigace pracoviště">

            <div class="container">

                <ul class="nav flex-column flex-lg-row">

                    <?php foreach (
                        $workplaceNavigation
                        as $navigationItem
                    ): ?>

                        <?php
                        $navigationUrl =
                            $buildNavigationUrl(
                                $navigationItem
                            );

                        $isActive =
                            $isNavigationItemActive(
                                $navigationItem
                            );
                        ?>

                        <li class="nav-item">

                            <a
                                href="<?= e($navigationUrl) ?>"
                                class="nav-link px-3 py-3<?= $isActive ? ' active fw-semibold' : '' ?>"
                                <?= $isActive
                                    ? 'aria-current="page"'
                                    : '' ?>>

                                <?= e(
                                    $navigationItem['title']
                                    ?? ''
                                ) ?>

                            </a>

                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        </nav>

    <?php endif; ?>

    <?php if ($isMainPage): ?>

        <section class="section border-0 m-0 workplace-intro">

            <div class="container">

                <div class="row g-4 align-items-start">

                    <div class="col-lg-8">

                        <?php if ($perex !== ''): ?>

                            <div class="lead mb-4">
                                <?= $perex ?>
                            </div>

                        <?php endif; ?>

                    </div>

                    <?php if (
                        $email !== ''
                        || $phone !== ''
                        || $building !== ''
                        || $floor !== ''
                        || $address !== ''
                    ): ?>

                        <div class="col-lg-4">

                                <div class="card-body bg-light-5 p-5">

                                    <h2 class="h4 mb-4">
                                        Kontakt a umístění
                                    </h2>

                                    <div class="d-flex flex-wrap gap-2 mb-5">

                            <?php if ($locationName !== ''): ?>

                                <span class="badge bg-primary badge-md">

                                    <?= e($locationName) ?>

                                </span>

                            <?php endif; ?>

                            <?php if ($typeName !== ''): ?>

                                <span class="badge bg-light text-dark border badge-md">

                                    <?= e($typeName) ?>

                                </span>

                            <?php endif; ?>

                        </div>

                                    <div class="workplace-contact-list">

                                        <?php if ($phone !== ''): ?>
                                            <div class="workplace-contact-row">
                                                <i class="fa fa-phone" aria-hidden="true"></i>

                                                <div>
                                                    <strong>Telefon:</strong>

                                                    <a href="<?= e(tel_href($phone)) ?>">
                                                        <?= e($phone) ?>
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($email !== ''): ?>
                                            <div class="workplace-contact-row">
                                                <i class="fa fa-envelope" aria-hidden="true"></i>

                                                <div>
                                                    <strong>E-mail:</strong>

                                                    <a href="mailto:<?= e($email) ?>">
                                                        <?= e($email) ?>
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($address !== ''): ?>
                                            <div class="workplace-contact-row">
                                                <i class="fa fa-map-marker" aria-hidden="true"></i>

                                                <div>
                                                    <strong>Adresa:</strong>
                                                    <?= e($address) ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($building !== '' || $floor !== ''): ?>
                                            <div class="workplace-contact-row">
                                                <i class="fa fa-building" aria-hidden="true"></i>

                                                <div>
                                                    <?php if ($building !== ''): ?>
                                                        <strong>Budova:</strong>
                                                        <?= e($building) ?>
                                                    <?php endif; ?>

                                                    <?php if ($building !== '' && $floor !== ''): ?>
                                                        <span class="mx-2">|</span>
                                                    <?php endif; ?>

                                                    <?php if ($floor !== ''): ?>
                                                        <strong>Patro:</strong>
                                                        <?= e($floor) ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                    </div>

                                    <?php if (
                            $workplaceCategories
                            || $workplaceSpecializations
                        ): ?>

                            <div class="row mt-5">

                                <?php if ($workplaceCategories): ?>

                                    <div class="col-md-12">

                                        <h2 class="h5 mb-3">
                                            Kategorie
                                        </h2>

                                        <div class="d-flex flex-wrap gap-2">

                                            <?php foreach (
                                                $workplaceCategories
                                                as $category
                                            ): ?>

                                                <span class="badge bg-light text-dark border badge-md">

                                                    <?= e(
                                                        $category['name']
                                                        ?? ''
                                                    ) ?>

                                                </span>

                                            <?php endforeach; ?>

                                        </div>

                                    </div>

                                <?php endif; ?>

                                <?php if (
                                    $workplaceSpecializations
                                ): ?>

                                    <div class="col-md-12 mt-4">

                                        <h2 class="h5 mb-3">
                                            Odbornosti
                                        </h2>

                                        <div class="d-flex flex-wrap gap-2">

                                            <?php foreach (
                                                $workplaceSpecializations
                                                as $specialization
                                            ): ?>

                                                <span class="badge bg-light text-dark border badge-md">

                                                    <?= e(
                                                        $specialization['name']
                                                        ?? ''
                                                    ) ?>

                                                </span>

                                            <?php endforeach; ?>

                                        </div>

                                    </div>

                                <?php endif; ?>

                            </div>

                        <?php endif; ?>

                                </div>

                            </aside>

                        </div>

                    <?php elseif ($thumbnail !== ''): ?>

                        <div class="col-lg-4">

                            <img
                                src="<?= e(media_url($thumbnail)) ?>"
                                alt="<?= e($workplaceName) ?>"
                                class="img-fluid rounded shadow-sm"
                                loading="lazy">

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </section>

    <?php endif; ?>

    <?php
    /*
     * Obsah bloků hlavní stránky nebo podstránky.
     */
    renderPageBlocks(
        $conn,
        $workplacePageId
    );
    ?>

</div>

<style>
.workplace-navigation .nav-link {
    color: inherit;
    border-bottom: 3px solid transparent;
}

.workplace-navigation .nav-link:hover,
.workplace-navigation .nav-link:focus {
    color: var(--bs-primary);
}

.workplace-navigation .nav-link.active {
    color: var(--bs-primary);
    border-bottom-color: var(--bs-primary);
}

@media (max-width: 991.98px) {
    .workplace-navigation .nav-link {
        border-bottom-width: 1px;
        border-bottom-color: rgba(0, 0, 0, .08);
    }

    .workplace-navigation .nav-link.active {
        border-left: 3px solid var(--bs-primary);
        border-bottom-color: rgba(0, 0, 0, .08);
    }
}
</style>

<?php
$content = ob_get_clean();

include __DIR__ . '/layout.php';