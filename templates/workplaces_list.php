<?php
ob_start();

$showBreadcrumbs = (int)(
    $workplacesSection['show_breadcrumbs']
    ?? $page['show_breadcrumbs']
    ?? 1
) === 1;

$sectionTitle = (string)(
    $workplacesSection['title']
    ?? $page['title']
    ?? 'Pracoviště'
);

$sectionContent = (string)(
    $workplacesSection['content']
    ?? $page['content']
    ?? ''
);

$breadcrumbs = [
    [
        'label' => 'Domů',
        'href'  => '/',
    ],
    [
        'label' => $sectionTitle,
        'href'  => null,
    ],
];

/*
 * Doplnění kategorií a odborností ke každému pracovišti.
 *
 * Zároveň sestavíme seznamy hodnot pro filtry.
 */
$workplaceItems = [];

$locationOptions = [];
$typeOptions = [];
$categoryOptions = [];
$specializationOptions = [];

foreach ($workplaces as $workplaceItem) {
    $workplaceId = (int)($workplaceItem['id'] ?? 0);

    $categories = workplaces_get_categories(
        $conn,
        $workplaceId
    );

    $specializations = workplaces_get_specializations(
        $conn,
        $workplaceId
    );

    $workplaceItem['categories'] = $categories;
    $workplaceItem['specializations'] = $specializations;

    /*
     * Lokalita.
     */
    $locationName = trim(
        (string)($workplaceItem['location_name'] ?? '')
    );

    if ($locationName !== '') {
        $locationKey = frontend_slugify($locationName);

        $locationOptions[$locationKey] = $locationName;
        $workplaceItem['location_filter'] = $locationKey;
    } else {
        $workplaceItem['location_filter'] = '';
    }

    /*
     * Typ pracoviště.
     */
    $typeName = trim(
        (string)($workplaceItem['type_name'] ?? '')
    );

    if ($typeName !== '') {
        $typeKey = frontend_slugify($typeName);

        $typeOptions[$typeKey] = $typeName;
        $workplaceItem['type_filter'] = $typeKey;
    } else {
        $workplaceItem['type_filter'] = '';
    }

    /*
     * Kategorie.
     */
    $categoryKeys = [];

    foreach ($categories as $category) {
        $categoryName = trim(
            (string)($category['name'] ?? '')
        );

        if ($categoryName === '') {
            continue;
        }

        $categoryKey = frontend_slugify($categoryName);

        $categoryOptions[$categoryKey] = $categoryName;
        $categoryKeys[] = $categoryKey;
    }

    $workplaceItem['category_filters'] = $categoryKeys;

    /*
     * Odbornosti.
     */
    $specializationKeys = [];

    foreach ($specializations as $specialization) {
        $specializationName = trim(
            (string)($specialization['name'] ?? '')
        );

        if ($specializationName === '') {
            continue;
        }

        $specializationKey = frontend_slugify(
            $specializationName
        );

        $specializationOptions[$specializationKey] =
            $specializationName;

        $specializationKeys[] = $specializationKey;
    }

    $workplaceItem['specialization_filters'] =
        $specializationKeys;

    $workplaceItems[] = $workplaceItem;
}

/*
 * Seřazení hodnot filtrů podle názvu.
 */
natcasesort($locationOptions);
natcasesort($typeOptions);
natcasesort($categoryOptions);
natcasesort($specializationOptions);
?>

<div role="main" class="main">

    <?php if ($showBreadcrumbs): ?>

        <section class="page-header mb-0">

            <div class="container">

                <div class="row align-items-center">

                    <div class="col-md-8 text-start">

                        <h1 class="font-weight-bold">
                            <?= e($sectionTitle) ?>
                        </h1>

                    </div>

                    <div class="col-md-4">

                        <?php render_breadcrumbs($breadcrumbs); ?>

                    </div>

                </div>

            </div>

        </section>

    <?php endif; ?>

    <section class="section border-0 m-0">

        <div class="container">

            <?php if ($sectionContent !== ''): ?>

                <div class="row mb-4">

                    <div class="col-lg-9">

                        <div class="lead">

                            <?= $sectionContent ?>

                        </div>

                    </div>

                </div>

            <?php endif; ?>

            <div
                class="card border-0 shadow-sm mb-5"
                id="workplaces-filter">

                <div class="card-body bg-light-5 p-5">

                    <div class="row">

                        <div class="col-lg-3">

                            <label
                                for="workplace-search"
                                class="form-label">

                                Hledat pracoviště

                            </label>

                            <div class="input-group">

                                <span class="input-group-text">

                                    <i class="fa fa-search"></i>

                                </span>

                                <input
                                    type="search"
                                    id="workplace-search"
                                    class="form-control"
                                    placeholder="Název kliniky nebo pracoviště">

                            </div>

                        </div>

                        <?php if ($locationOptions): ?>

                            <div class="col-md-6 col-lg-3">

                                <label
                                    for="workplace-location"
                                    class="form-label">

                                    Nemocnice

                                </label>
                                
                                <select
                                    id="workplace-location"
                                    class="form-select">

                                    <option value="">
                                        Všechny
                                    </option>

                                    <?php foreach (
                                        $locationOptions
                                        as $value => $label
                                    ): ?>

                                        <option value="<?= e($value) ?>">

                                            <?= e($label) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>
                                
                            </div>

                        <?php endif; ?>

                        <?php if ($typeOptions): ?>

                            <div class="col-md-6 col-lg-3">

                                <label
                                    for="workplace-type"
                                    class="form-label">

                                    Typ

                                </label>

                                <select
                                    id="workplace-type"
                                    class="form-select">

                                    <option value="">
                                        Všechny
                                    </option>

                                    <?php foreach (
                                        $typeOptions
                                        as $value => $label
                                    ): ?>

                                        <option value="<?= e($value) ?>">

                                            <?= e($label) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                        <?php endif; ?>

                        <?php if ($categoryOptions): ?>

                            <div class="col-md-6 col-lg-3">

                                <label
                                    for="workplace-category"
                                    class="form-label">

                                    Kategorie

                                </label>

                                <select
                                    id="workplace-category"
                                    class="form-select">

                                    <option value="">
                                        Všechny
                                    </option>

                                    <?php foreach (
                                        $categoryOptions
                                        as $value => $label
                                    ): ?>

                                        <option value="<?= e($value) ?>">

                                            <?= e($label) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                        <?php endif; ?>

                        <?php if ($specializationOptions): ?>

                            <div class="col-md-6 col-lg-2">

                                <label
                                    for="workplace-specialization"
                                    class="form-label">

                                    Odbornost

                                </label>

                                <select
                                    id="workplace-specialization"
                                    class="form-select">

                                    <option value="">
                                        Všechny
                                    </option>

                                    <?php foreach (
                                        $specializationOptions
                                        as $value => $label
                                    ): ?>

                                        <option value="<?= e($value) ?>">

                                            <?= e($label) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                        <?php endif; ?>

                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-3 mt-3">

                        <button
                            type="button"
                            id="workplace-filter-reset"
                            class="btn btn-outline-secondary btn-sm">

                            <i class="bi bi-arrow-counterclockwise me-1"></i>
                            Zrušit filtry

                        </button>

                        <div
                            id="workplace-result-count"
                            class="text-muted small">

                            Zobrazeno pracovišť:
                            <strong><?= count($workplaceItems) ?></strong>

                        </div>

                    </div>

                </div>

            </div>

            <?php if (!$workplaceItems): ?>

                <div class="alert alert-info">

                    V tuto chvíli nejsou zveřejněna žádná pracoviště.

                </div>

            <?php else: ?>

                <div
                    class="row g-4"
                    id="workplaces-list">

                    <?php foreach (
                        $workplaceItems
                        as $workplaceItem
                    ): ?>

                        <?php
                        $workplaceUrl = rtrim(
                            $workplacesBase,
                            '/'
                        )
                            . '/'
                            . rawurlencode(
                                (string)$workplaceItem['slug']
                            );

                        $searchText = implode(
                            ' ',
                            array_filter([
                                $workplaceItem['name'] ?? '',
                                $workplaceItem['short_name'] ?? '',
                                $workplaceItem['perex'] ?? '',
                                $workplaceItem['location_name'] ?? '',
                                $workplaceItem['type_name'] ?? '',
                                implode(
                                    ' ',
                                    array_column(
                                        $workplaceItem['categories'],
                                        'name'
                                    )
                                ),
                                implode(
                                    ' ',
                                    array_column(
                                        $workplaceItem['specializations'],
                                        'name'
                                    )
                                ),
                            ])
                        );

                        $thumbnail = trim(
                            (string)(
                                $workplaceItem['thumbnail']
                                ?? ''
                            )
                        );
                        ?>

                        <div
                            class="col-md-6 col-xl-4 workplace-item"
                            data-name="<?= e(
                                mb_strtolower($searchText)
                            ) ?>"
                            data-location="<?= e(
                                $workplaceItem['location_filter']
                            ) ?>"
                            data-type="<?= e(
                                $workplaceItem['type_filter']
                            ) ?>"
                            data-categories="<?= e(
                                implode(
                                    ' ',
                                    $workplaceItem['category_filters']
                                )
                            ) ?>"
                            data-specializations="<?= e(
                                implode(
                                    ' ',
                                    $workplaceItem[
                                        'specialization_filters'
                                    ]
                                )
                            ) ?>">

                            <article
                                class="card h-100 border-0 shadow-sm workplace-card">

                                <?php if ($thumbnail !== ''): ?>

                                    <a
                                        href="<?= e($workplaceUrl) ?>"
                                        class="d-block">

                                        <img
                                            src="<?= e(
                                                media_url($thumbnail)
                                            ) ?>"
                                            class="card-img-top"
                                            alt="<?= e(
                                                $workplaceItem['name']
                                            ) ?>"
                                            loading="lazy"
                                            style="height: 230px; object-fit: cover;">

                                    </a>

                                <?php endif; ?>

                                <div class="card-body d-flex flex-column bg-light-5 p-4">

                                    <div class="mb-3">

                                        <?php if (
                                            !empty(
                                                $workplaceItem[
                                                    'location_name'
                                                ]
                                            )
                                        ): ?>

                                            <span class="badge bg-primary badge-sm me-1 mb-1">

                                                <?= e(
                                                    $workplaceItem[
                                                        'location_name'
                                                    ]
                                                ) ?>

                                            </span>

                                        <?php endif; ?>

                                        <?php if (
                                            !empty(
                                                $workplaceItem[
                                                    'type_name'
                                                ]
                                            )
                                        ): ?>

                                            <span class="badge bg-light text-dark badge-sm border mb-1">

                                                <?= e(
                                                    $workplaceItem[
                                                        'type_name'
                                                    ]
                                                ) ?>

                                            </span>

                                        <?php endif; ?>

                                    </div>

                                    <h2 class="h4 mb-2">

                                        <a
                                            href="<?= e($workplaceUrl) ?>"
                                            class="text-decoration-none">

                                            <?= e(
                                                $workplaceItem['name']
                                            ) ?>

                                        </a>

                                    </h2>

                                    <?php if (
                                        !empty(
                                            $workplaceItem['short_name']
                                        )
                                    ): ?>

                                        <div class="text-muted small mb-3">

                                            <?= e(
                                                $workplaceItem[
                                                    'short_name'
                                                ]
                                            ) ?>

                                        </div>

                                    <?php endif; ?>

                                    <?php if (
                                        !empty(
                                            $workplaceItem['perex']
                                        )
                                    ): ?>

                                        <p class="text-muted">

                                            <?= e(
                                                text_excerpt(
                                                    $workplaceItem['perex'],
                                                    180
                                                )
                                            ) ?>

                                        </p>

                                    <?php endif; ?>

                                    <?php if (
                                        !empty(
                                            $workplaceItem[
                                                'specializations'
                                            ]
                                        )
                                    ): ?>

                                        <div class="mb-3">

                                            <?php foreach (
                                                array_slice(
                                                    $workplaceItem[
                                                        'specializations'
                                                    ],
                                                    0,
                                                    3
                                                )
                                                as $specialization
                                            ): ?>

                                                <span class="badge bg-light text-dark border me-1 mb-1">

                                                    <?= e(
                                                        $specialization[
                                                            'name'
                                                        ]
                                                    ) ?>

                                                </span>

                                            <?php endforeach; ?>

                                        </div>

                                    <?php endif; ?>

                                    <div class="mt-auto pt-2">

                                        <a
                                            href="<?= e($workplaceUrl) ?>"
                                            class="btn btn-primary">

                                            Detail pracoviště

                                            <i class="bi bi-arrow-right ms-1"></i>

                                        </a>

                                    </div>

                                </div>

                            </article>

                        </div>

                    <?php endforeach; ?>

                </div>

                <div
                    id="workplaces-empty"
                    class="alert alert-warning mt-4"
                    hidden>

                    Zadaným filtrům neodpovídá žádné pracoviště.

                </div>

            <?php endif; ?>

        </div>

    </section>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const list = document.getElementById('workplaces-list');

    if (!list) {
        return;
    }

    const items = Array.from(
        list.querySelectorAll('.workplace-item')
    );

    const searchInput = document.getElementById(
        'workplace-search'
    );

    const locationSelect = document.getElementById(
        'workplace-location'
    );

    const typeSelect = document.getElementById(
        'workplace-type'
    );

    const categorySelect = document.getElementById(
        'workplace-category'
    );

    const specializationSelect = document.getElementById(
        'workplace-specialization'
    );

    const resetButton = document.getElementById(
        'workplace-filter-reset'
    );

    const resultCount = document.getElementById(
        'workplace-result-count'
    );

    const emptyMessage = document.getElementById(
        'workplaces-empty'
    );

    function normalize(value) {
        return String(value || '')
            .toLocaleLowerCase('cs-CZ')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    function applyFilters() {
        const searchValue = normalize(
            searchInput ? searchInput.value : ''
        );

        const locationValue = locationSelect
            ? locationSelect.value
            : '';

        const typeValue = typeSelect
            ? typeSelect.value
            : '';

        const categoryValue = categorySelect
            ? categorySelect.value
            : '';

        const specializationValue = specializationSelect
            ? specializationSelect.value
            : '';

        let visibleCount = 0;

        items.forEach(function (item) {
            const itemName = normalize(
                item.dataset.name
            );

            const itemLocation =
                item.dataset.location || '';

            const itemType =
                item.dataset.type || '';

            const itemCategories = (
                item.dataset.categories || ''
            ).split(' ');

            const itemSpecializations = (
                item.dataset.specializations || ''
            ).split(' ');

            const matchesSearch =
                searchValue === ''
                || itemName.includes(searchValue);

            const matchesLocation =
                locationValue === ''
                || itemLocation === locationValue;

            const matchesType =
                typeValue === ''
                || itemType === typeValue;

            const matchesCategory =
                categoryValue === ''
                || itemCategories.includes(
                    categoryValue
                );

            const matchesSpecialization =
                specializationValue === ''
                || itemSpecializations.includes(
                    specializationValue
                );

            const visible =
                matchesSearch
                && matchesLocation
                && matchesType
                && matchesCategory
                && matchesSpecialization;

            item.hidden = !visible;

            if (visible) {
                visibleCount++;
            }
        });

        if (resultCount) {
            resultCount.innerHTML =
                'Zobrazeno pracovišť: <strong>'
                + visibleCount
                + '</strong>';
        }

        if (emptyMessage) {
            emptyMessage.hidden = visibleCount !== 0;
        }
    }

    [
        searchInput,
        locationSelect,
        typeSelect,
        categorySelect,
        specializationSelect
    ].forEach(function (element) {
        if (!element) {
            return;
        }

        element.addEventListener(
            element.tagName === 'INPUT'
                ? 'input'
                : 'change',
            applyFilters
        );
    });

    if (resetButton) {
        resetButton.addEventListener(
            'click',
            function () {
                if (searchInput) {
                    searchInput.value = '';
                }

                if (locationSelect) {
                    locationSelect.value = '';
                }

                if (typeSelect) {
                    typeSelect.value = '';
                }

                if (categorySelect) {
                    categorySelect.value = '';
                }

                if (specializationSelect) {
                    specializationSelect.value = '';
                }

                applyFilters();
            }
        );
    }

    applyFilters();
});
</script>

<?php
$content = ob_get_clean();

include __DIR__ . '/layout.php';