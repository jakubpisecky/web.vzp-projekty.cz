<?php

/**
 * Router modulu Pracoviště.
 *
 * Vrací true, pokud požadavek zpracoval.
 * Vrací false, pokud předaná stránka není sekce pracovišť.
 */
function workplaces_route(
    mysqli $conn,
    array $sectionPage,
    array $segments
): bool {
    /*
     * Nepoužíváme zde pouze tpl_key(), protože šablona
     * "workplaces" nemusí mít stejnojmenný soubor
     * /templates/workplaces.php.
     */
    $rawTemplate = strtolower(
        trim((string)($sectionPage['template'] ?? ''))
    );

    if (!in_array(
        $rawTemplate,
        ['workplaces', 'workplace_list'],
        true
    )) {
        return false;
    }

    $segmentCount = count($segments);

    $workplacesSection = $sectionPage;

    $workplacesBase = '/'
        . trim(
            (string)$sectionPage['slug'],
            '/'
        );

    /*
     * =====================================================
     * Výpis všech pracovišť
     * /{slug-sekce}
     * =====================================================
     */
    if ($segmentCount === 1) {
        $workplaces = workplaces_get_all($conn);

        $title = $sectionPage['meta_title']
            ?: $sectionPage['title'];

        $metaDescription =
            $sectionPage['meta_description'] ?? '';

        workplaces_include_template(
            'workplaces_list',
            [
                'workplacesSection' => $workplacesSection,
                'workplacesBase' => $workplacesBase,
                'workplaces' => $workplaces,
            ],
            $sectionPage,
            $title,
            $metaDescription
        );

        return true;
    }

    /*
     * Další úroveň musí obsahovat slug pracoviště.
     */
    $workplaceSlug = trim(
        (string)($segments[1] ?? '')
    );

    if ($workplaceSlug === '') {
        workplaces_render_404(
            'Pracoviště nenalezeno'
        );

        return true;
    }

    $workplace = workplaces_get_by_slug(
        $conn,
        $workplaceSlug
    );

    if (!$workplace) {
        workplaces_render_404(
            'Pracoviště nenalezeno'
        );

        return true;
    }

    $workplaceId = (int)$workplace['id'];

    $mainPageId = (int)(
        $workplace['main_page_id'] ?? 0
    );

    /*
     * Společná data detailu pracoviště.
     */
    $workplaceNavigation =
        workplaces_get_navigation(
            $conn,
            $workplaceId,
            (string)$workplace['slug'],
            $mainPageId
        );

    $workplaceCategories =
        workplaces_get_categories(
            $conn,
            $workplaceId
        );

    $workplaceSpecializations =
        workplaces_get_specializations(
            $conn,
            $workplaceId
        );

    /*
     * =====================================================
     * Hlavní stránka pracoviště
     * /{sekce}/{pracoviste}
     * =====================================================
     */
    if ($segmentCount === 2) {
        $workplacePage =
            workplaces_get_main_page(
                $conn,
                $workplaceId,
                $mainPageId
            );

        if (!$workplacePage) {
            workplaces_render_404(
                'Stránka pracoviště nenalezena'
            );

            return true;
        }

        $workplacePageSlug = '';

        $workplacePageTemplate = tpl_key(
            $workplacePage['template']
                ?? 'universal'
        );

        $title = $workplace['meta_title']
            ?: $workplacePage['meta_title']
            ?: $workplace['name'];

        $metaDescription =
            $workplace['meta_description']
            ?: $workplacePage['meta_description']
            ?: '';

        if (
            $workplacePageTemplate === 'contact'
            && $_SERVER['REQUEST_METHOD'] === 'POST'
        ) {
            handle_contact_form(
                $workplacePage
            );
        }

        workplaces_include_template(
            'workplace',
            [
                'workplacesSection' => $workplacesSection,
                'workplacesBase' => $workplacesBase,
                'workplace' => $workplace,
                'workplacePage' => $workplacePage,
                'workplacePageSlug' => $workplacePageSlug,
                'workplacePageTemplate' => $workplacePageTemplate,
                'workplaceNavigation' => $workplaceNavigation,
                'workplaceCategories' => $workplaceCategories,
                'workplaceSpecializations' => $workplaceSpecializations,
            ],
            $workplacePage,
            $title,
            $metaDescription
        );

        return true;
    }

    /*
     * =====================================================
     * Podstránka pracoviště
     * /{sekce}/{pracoviste}/{podstranka}
     * =====================================================
     */
    if ($segmentCount === 3) {
        $workplacePageSlug = trim(
            (string)($segments[2] ?? '')
        );

        if ($workplacePageSlug === '') {
            workplaces_render_404(
                'Stránka pracoviště nenalezena'
            );

            return true;
        }

        $workplacePage =
            workplaces_get_page_by_slug(
                $conn,
                $workplaceId,
                (string)$workplace['slug'],
                $workplacePageSlug
            );

        if (!$workplacePage) {
            workplaces_render_404(
                'Stránka pracoviště nenalezena'
            );

            return true;
        }

        $workplacePageTemplate = tpl_key(
            $workplacePage['template']
                ?? 'universal'
        );

        $title = $workplacePage['meta_title']
            ?: $workplacePage['title'];

        $metaDescription =
            $workplacePage['meta_description']
            ?? '';

        if (
            $workplacePageTemplate === 'contact'
            && $_SERVER['REQUEST_METHOD'] === 'POST'
        ) {
            handle_contact_form(
                $workplacePage
            );
        }

        workplaces_include_template(
            'workplace',
            [
                'workplacesSection' => $workplacesSection,
                'workplacesBase' => $workplacesBase,
                'workplace' => $workplace,
                'workplacePage' => $workplacePage,
                'workplacePageSlug' => $workplacePageSlug,
                'workplacePageTemplate' => $workplacePageTemplate,
                'workplaceNavigation' => $workplaceNavigation,
                'workplaceCategories' => $workplaceCategories,
                'workplaceSpecializations' => $workplaceSpecializations,
            ],
            $workplacePage,
            $title,
            $metaDescription
        );

        return true;
    }

    /*
     * Zatím podporujeme maximálně tři segmenty.
     */
    workplaces_render_404(
        'Stránka nenalezena'
    );

    return true;
}


/**
 * Načte šablonu pracovišť a současně nastaví globální
 * proměnné očekávané současnou funkcí include_template().
 *
 * Modulová data se předávají standardně druhým parametrem.
 */
function workplaces_include_template(
    string $template,
    array $data,
    array $currentPage,
    string $pageTitle,
    string $metaDescription = ''
): void {
    /*
     * include_template() používá tyto proměnné jako global.
     * Proto je musíme nastavit do globálního rozsahu.
     */
    $GLOBALS['page'] = $currentPage;
    $GLOBALS['title'] = $pageTitle;
    $GLOBALS['meta_description'] = $metaDescription;

    include_template(
        $template,
        $data
    );
}


/**
 * Jednotné vykreslení 404 uvnitř routeru pracovišť.
 */
function workplaces_render_404(
    string $pageTitle = 'Stránka nenalezena'
): void {
    http_response_code(404);

    $GLOBALS['title'] = $pageTitle;
    $GLOBALS['meta_description'] = '';

    include_template('404');
}