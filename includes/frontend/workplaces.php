<?php

/**
 * Vrátí všechna publikovaná a aktivní pracoviště.
 */
function workplaces_get_all(mysqli $conn): array
{
    $items = [];

    $sql = "
        SELECT
            w.id,
            w.location_id,
            w.type_id,
            w.main_page_id,
            w.name,
            w.short_name,
            w.slug,
            w.perex,
            w.thumbnail,
            w.logo,
            w.email,
            w.phone,
            w.building,
            w.floor,
            w.address,
            w.latitude,
            w.longitude,
            w.meta_title,
            w.meta_description,
            w.sort_order,

            wl.name AS location_name,
            wt.name AS type_name

        FROM workplaces w

        INNER JOIN workplace_locations wl
            ON wl.id = w.location_id

        LEFT JOIN workplace_types wt
            ON wt.id = w.type_id

        WHERE w.status = 'published'
          AND w.is_active = 1

        ORDER BY
            w.sort_order ASC,
            w.name ASC,
            w.id ASC
    ";

    $result = $conn->query($sql);

    if (!$result) {
        error_log(
            'workplaces_get_all SQL error: '
            . $conn->error
        );

        return [];
    }

    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    return $items;
}


/**
 * Vrátí jedno publikované pracoviště podle slugu.
 */
function workplaces_get_by_slug(
    mysqli $conn,
    string $slug
): ?array {
    $slug = trim($slug);

    if ($slug === '') {
        return null;
    }

    $stmt = $conn->prepare("
        SELECT
            w.id,
            w.location_id,
            w.type_id,
            w.main_page_id,
            w.name,
            w.short_name,
            w.slug,
            w.perex,
            w.thumbnail,
            w.logo,
            w.email,
            w.phone,
            w.building,
            w.floor,
            w.address,
            w.latitude,
            w.longitude,
            w.meta_title,
            w.meta_description,
            w.sort_order,

            wl.name AS location_name,
            wt.name AS type_name

        FROM workplaces w

        INNER JOIN workplace_locations wl
            ON wl.id = w.location_id

        LEFT JOIN workplace_types wt
            ON wt.id = w.type_id

        WHERE w.slug = ?
          AND w.status = 'published'
          AND w.is_active = 1

        LIMIT 1
    ");

    if (!$stmt) {
        error_log(
            'workplaces_get_by_slug prepare error: '
            . $conn->error
        );

        return null;
    }

    $stmt->bind_param("s", $slug);
    $stmt->execute();

    $workplace = $stmt
        ->get_result()
        ->fetch_assoc();

    $stmt->close();

    return $workplace ?: null;
}


/**
 * Vrátí hlavní stránku pracoviště.
 */
function workplaces_get_main_page(
    mysqli $conn,
    int $workplaceId,
    int $mainPageId
): ?array {
    if ($workplaceId <= 0 || $mainPageId <= 0) {
        return null;
    }

    $stmt = $conn->prepare("
        SELECT
            p.id,
            p.owner_type,
            p.owner_id,
            p.title,
            p.slug,
            p.content,
            p.meta_title,
            p.meta_description,
            p.template,
            p.show_breadcrumbs,
            p.status,

            wp.id AS workplace_page_id,
            wp.parent_id,
            wp.show_in_menu,
            wp.sort_order,
            wp.is_active

        FROM pages p

        INNER JOIN workplace_pages wp
            ON wp.page_id = p.id

        WHERE p.id = ?
          AND p.owner_type = 'workplace'
          AND p.owner_id = ?
          AND p.status = 'published'

          AND wp.workplace_id = ?
          AND wp.is_active = 1

        LIMIT 1
    ");

    if (!$stmt) {
        error_log(
            'workplaces_get_main_page prepare error: '
            . $conn->error
        );

        return null;
    }

    $stmt->bind_param(
        "iii",
        $mainPageId,
        $workplaceId,
        $workplaceId
    );

    $stmt->execute();

    $page = $stmt
        ->get_result()
        ->fetch_assoc();

    $stmt->close();

    return $page ?: null;
}


/**
 * Vrátí podstránku pracoviště podle veřejného slugu.
 *
 * V databázi je slug uložen například:
 * pracoviste-detska-klinika-kontakty
 *
 * Ve veřejné URL se používá pouze:
 * kontakty
 */
function workplaces_get_page_by_slug(
    mysqli $conn,
    int $workplaceId,
    string $workplaceSlug,
    string $pageSlug
): ?array {
    $workplaceSlug = frontend_slugify($workplaceSlug);
    $pageSlug = frontend_slugify($pageSlug);

    if (
        $workplaceId <= 0
        || $workplaceSlug === ''
        || $pageSlug === ''
    ) {
        return null;
    }

    $internalSlug = 'pracoviste-'
        . $workplaceSlug
        . '-'
        . $pageSlug;

    $stmt = $conn->prepare("
        SELECT
            p.id,
            p.owner_type,
            p.owner_id,
            p.title,
            p.slug,
            p.content,
            p.meta_title,
            p.meta_description,
            p.template,
            p.show_breadcrumbs,
            p.status,

            wp.id AS workplace_page_id,
            wp.parent_id,
            wp.show_in_menu,
            wp.sort_order,
            wp.is_active

        FROM pages p

        INNER JOIN workplace_pages wp
            ON wp.page_id = p.id

        WHERE p.slug = ?
          AND p.owner_type = 'workplace'
          AND p.owner_id = ?
          AND p.status = 'published'

          AND wp.workplace_id = ?
          AND wp.is_active = 1

        LIMIT 1
    ");

    if (!$stmt) {
        error_log(
            'workplaces_get_page_by_slug prepare error: '
            . $conn->error
        );

        return null;
    }

    $stmt->bind_param(
        "sii",
        $internalSlug,
        $workplaceId,
        $workplaceId
    );

    $stmt->execute();

    $page = $stmt
        ->get_result()
        ->fetch_assoc();

    $stmt->close();

    return $page ?: null;
}


/**
 * Vrátí navigaci konkrétního pracoviště.
 *
 * Každá položka obsahuje také:
 * route_slug – slug použitelný ve veřejné URL
 * is_main – zda jde o hlavní stránku
 */
function workplaces_get_navigation(
    mysqli $conn,
    int $workplaceId,
    string $workplaceSlug,
    int $mainPageId
): array {
    if ($workplaceId <= 0) {
        return [];
    }

    $items = [];

    $stmt = $conn->prepare("
        SELECT
            wp.id AS workplace_page_id,
            wp.parent_id,
            wp.sort_order,

            p.id AS page_id,
            p.title,
            p.slug,
            p.template

        FROM workplace_pages wp

        INNER JOIN pages p
            ON p.id = wp.page_id

        WHERE wp.workplace_id = ?
          AND wp.show_in_menu = 1
          AND wp.is_active = 1

          AND p.owner_type = 'workplace'
          AND p.owner_id = ?
          AND p.status = 'published'

        ORDER BY
            wp.sort_order ASC,
            wp.id ASC
    ");

    if (!$stmt) {
        error_log(
            'workplaces_get_navigation prepare error: '
            . $conn->error
        );

        return [];
    }

    $stmt->bind_param(
        "ii",
        $workplaceId,
        $workplaceId
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $workplaceSlug = frontend_slugify($workplaceSlug);
    $prefix = 'pracoviste-' . $workplaceSlug . '-';

    while ($row = $result->fetch_assoc()) {
        $pageId = (int)$row['page_id'];
        $isMain = $pageId === $mainPageId;

        if ($isMain) {
            $routeSlug = '';
        } elseif (
            str_starts_with(
                (string)$row['slug'],
                $prefix
            )
        ) {
            $routeSlug = substr(
                (string)$row['slug'],
                strlen($prefix)
            );
        } else {
            $routeSlug = (string)$row['slug'];
        }

        $row['route_slug'] = $routeSlug;
        $row['is_main'] = $isMain;

        $items[] = $row;
    }

    $stmt->close();

    return $items;
}


/**
 * Vrátí kategorie konkrétního pracoviště.
 */
function workplaces_get_categories(
    mysqli $conn,
    int $workplaceId
): array {
    if ($workplaceId <= 0) {
        return [];
    }

    $items = [];

    $stmt = $conn->prepare("
        SELECT
            c.id,
            c.name

        FROM workplace_category wc

        INNER JOIN workplace_categories c
            ON c.id = wc.category_id

        WHERE wc.workplace_id = ?
          AND c.is_active = 1

        ORDER BY
            c.sort_order ASC,
            c.name ASC
    ");

    if (!$stmt) {
        error_log(
            'workplaces_get_categories prepare error: '
            . $conn->error
        );

        return [];
    }

    $stmt->bind_param("i", $workplaceId);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    $stmt->close();

    return $items;
}


/**
 * Vrátí odbornosti konkrétního pracoviště.
 */
function workplaces_get_specializations(
    mysqli $conn,
    int $workplaceId
): array {
    if ($workplaceId <= 0) {
        return [];
    }

    $items = [];

    $stmt = $conn->prepare("
        SELECT
            s.id,
            s.name

        FROM workplace_specialization ws

        INNER JOIN workplace_specializations s
            ON s.id = ws.specialization_id

        WHERE ws.workplace_id = ?
          AND s.is_active = 1

        ORDER BY
            s.sort_order ASC,
            s.name ASC
    ");

    if (!$stmt) {
        error_log(
            'workplaces_get_specializations prepare error: '
            . $conn->error
        );

        return [];
    }

    $stmt->bind_param("i", $workplaceId);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    $stmt->close();

    return $items;
}