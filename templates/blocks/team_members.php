<?php
// /templates/blocks/team_members.php

/*
 * =====================================================
 * Stránka s kompletním výpisem týmu
 * =====================================================
 */

$teamPageUrl = '';

$stmt = $conn->prepare("
    SELECT id
    FROM pages
    WHERE template = 'team'
      AND status = 'published'
      AND owner_type = 'page'
    ORDER BY id ASC
    LIMIT 1
");

$stmt->execute();

$teamPage = $stmt
    ->get_result()
    ->fetch_assoc();

$stmt->close();

if ($teamPage) {
    $teamPageUrl = frontend_page_url(
        $conn,
        (int)$teamPage['id']
    );
}


/*
 * =====================================================
 * Nastavení bloku
 * =====================================================
 */

$teamCategoryIds = array_values(
    array_filter(
        array_map(
            'intval',
            explode(
                ';',
                $block['team_category_ids'] ?? ''
            )
        )
    )
);

$manualMemberIds = array_values(
    array_filter(
        array_map(
            'intval',
            explode(
                ';',
                $block['team_member_ids'] ?? ''
            )
        )
    )
);

$showTeamButton =
    (int)($block['show_team_button'] ?? 1) === 1;


/*
 * =====================================================
 * Celkový počet aktivních členů
 * =====================================================
 */

$totalMembers = 0;

$result = $conn->query("
    SELECT COUNT(*) AS cnt
    FROM team_members
    WHERE is_active = 1
");

if ($result) {

    $row = $result->fetch_assoc();

    $totalMembers = (int)(
        $row['cnt'] ?? 0
    );
}


/*
 * =====================================================
 * Ručně vybraní členové
 * =====================================================
 */

$manualMembers = [];

if ($manualMemberIds) {

    $placeholders = implode(
        ',',
        array_fill(
            0,
            count($manualMemberIds),
            '?'
        )
    );

    $types = str_repeat(
        'i',
        count($manualMemberIds)
    );

    $stmt = $conn->prepare("
        SELECT
            id,
            name,
            slug,
            position,
            email,
            phone,
            photo,
            show_detail,
            category_id,
            sort_order
        FROM team_members
        WHERE id IN ($placeholders)
          AND is_active = 1
    ");

    $stmt->bind_param(
        $types,
        ...$manualMemberIds
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $membersById = [];

    while ($row = $result->fetch_assoc()) {

        $membersById[
            (int)$row['id']
        ] = $row;
    }

    $stmt->close();


    /*
     * Zachování pořadí z pickeru
     */
    foreach ($manualMemberIds as $memberId) {

        if (isset($membersById[$memberId])) {

            $manualMembers[] =
                $membersById[$memberId];
        }
    }
}


/*
 * =====================================================
 * Členové z vybraných kategorií
 * =====================================================
 */

$categoryMembers = [];

if ($teamCategoryIds) {

    $placeholders = implode(
        ',',
        array_fill(
            0,
            count($teamCategoryIds),
            '?'
        )
    );

    $types = str_repeat(
        'i',
        count($teamCategoryIds)
    );

    $stmt = $conn->prepare("
        SELECT
            id,
            name,
            slug,
            position,
            email,
            phone,
            photo,
            show_detail,
            category_id,
            sort_order
        FROM team_members
        WHERE category_id IN ($placeholders)
          AND is_active = 1
        ORDER BY sort_order ASC, id ASC
    ");

    $stmt->bind_param(
        $types,
        ...$teamCategoryIds
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $categoryMembers[] = $row;
    }

    $stmt->close();
}


/*
 * =====================================================
 * Sloučení bez duplicit
 * =====================================================
 */

$members = [];
$usedMemberIds = [];


/*
 * 1. Ručně vybraní
 */
foreach ($manualMembers as $member) {

    $memberId = (int)$member['id'];

    if (isset($usedMemberIds[$memberId])) {
        continue;
    }

    $usedMemberIds[$memberId] = true;

    $members[] = $member;
}


/*
 * 2. Kategorie
 */
foreach ($categoryMembers as $member) {

    $memberId = (int)$member['id'];

    if (isset($usedMemberIds[$memberId])) {
        continue;
    }

    $usedMemberIds[$memberId] = true;

    $members[] = $member;
}


/*
 * =====================================================
 * Zpětná kompatibilita
 * =====================================================
 *
 * Pokud není vybraná kategorie ani konkrétní člen,
 * zobrazíme první 3 aktivní členy jako původně.
 */

if (
    !$teamCategoryIds
    && !$manualMemberIds
) {

    $members = [];

    $stmt = $conn->prepare("
        SELECT
            id,
            name,
            slug,
            position,
            email,
            phone,
            photo,
            show_detail,
            category_id,
            sort_order
        FROM team_members
        WHERE is_active = 1
        ORDER BY sort_order ASC, id ASC
        LIMIT 3
    ");

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $members[] = $row;
    }

    $stmt->close();
}


/*
 * =====================================================
 * Pozadí karet
 * =====================================================
 */

$cardBackground =
    ($block['card_background'] ?? 'white') === 'light'
        ? 'bg-light-5'
        : 'bg-white';


/*
 * =====================================================
 * Typ zobrazení
 * =====================================================
 */

$layout = $block['layout'] ?? 'cards';

if (!in_array(
    $layout,
    ['cards', 'list'],
    true
)) {
    $layout = 'cards';
}


/*
 * =====================================================
 * Render
 * =====================================================
 */
?>

<section class="section <?= e($block['section_class'] ?? '') ?>">

    <div class="container">

        <?php if (!empty($block['subtitle'])): ?>

            <span class="top-sub-title text-color-primary">
                <?= e($block['subtitle']) ?>
            </span>

        <?php endif; ?>


        <?php if (!empty($block['title'])): ?>

            <h2 class="font-weight-bold mb-4">
                <?= e($block['title']) ?>
            </h2>

        <?php endif; ?>


        <?php if (!$members): ?>

            <p class="text-muted">
                Zatím nejsou přidáni žádní pracovníci.
            </p>

        <?php else: ?>


            <?php if ($layout === 'list'): ?>

                <!-- =================================================
                     Seznam
                     ================================================= -->

                <div class="list-group list-group-flush">

                    <?php foreach ($members as $member): ?>

                        <?php

                        $hasDetail =
                            (int)($member['show_detail'] ?? 0) === 1
                            && !empty($member['slug'])
                            && $teamPageUrl !== '';

                        $detailUrl = '';

                        if ($hasDetail) {

                            $detailUrl =
                                rtrim(
                                    $teamPageUrl,
                                    '/'
                                )
                                . '/'
                                . trim(
                                    (string)$member['slug'],
                                    '/'
                                );
                        }


                        $phoneLink = '';

                        if (!empty($member['phone'])) {

                            $phoneLink = preg_replace(
                                '/[^0-9+]/',
                                '',
                                $member['phone']
                            );
                        }

                        ?>


                        <div class="list-group-item px-0 py-3 bg-transparent">

                            <div class="d-flex align-items-center gap-3">


                                <?php if (!empty($member['photo'])): ?>

                                    <?php if ($hasDetail): ?>

                                        <a href="<?= e($detailUrl) ?>">

                                    <?php endif; ?>

                                        <img
                                            src="<?= e($member['photo']) ?>"
                                            alt="<?= e($member['name']) ?>"
                                            class="rounded-circle border"
                                            style="
                                                width:80px;
                                                height:80px;
                                                object-fit:cover;
                                            "
                                        >

                                    <?php if ($hasDetail): ?>

                                        </a>

                                    <?php endif; ?>

                                <?php endif; ?>


                                <div class="flex-grow-1">

                                    <h3 class="h5 mb-1">

                                        <?php if ($hasDetail): ?>

                                            <a
                                                href="<?= e($detailUrl) ?>"
                                                class="text-decoration-none text-color-dark"
                                            >
                                                <?= e($member['name']) ?>
                                            </a>

                                        <?php else: ?>

                                            <?= e($member['name']) ?>

                                        <?php endif; ?>

                                    </h3>


                                    <?php if (!empty($member['position'])): ?>

                                        <div class="text-muted small mb-2">
                                            <?= e($member['position']) ?>
                                        </div>

                                    <?php endif; ?>


                                    <?php if (!empty($member['email'])): ?>

                                        <div class="small">

                                            <a href="mailto:<?= e($member['email']) ?>">

                                                <i class="fas fa-envelope me-1"></i>

                                                <?= e($member['email']) ?>

                                            </a>

                                        </div>

                                    <?php endif; ?>


                                    <?php if (!empty($member['phone'])): ?>

                                        <div class="small">

                                            <a href="tel:<?= e($phoneLink) ?>">

                                                <i class="fas fa-phone me-1"></i>

                                                <?= e($member['phone']) ?>

                                            </a>

                                        </div>

                                    <?php endif; ?>

                                </div>


                                <?php if ($hasDetail): ?>

                                    <div>

                                        <a
                                            href="<?= e($detailUrl) ?>"
                                            class="btn btn-primary btn-3"
                                        >
                                            Zobrazit profil
                                        </a>

                                    </div>

                                <?php endif; ?>


                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>


            <?php else: ?>


                <!-- =================================================
                     Karty
                     ================================================= -->

                <div class="row g-4">

                    <?php foreach ($members as $member): ?>

                        <?php

                        $hasDetail =
                            (int)($member['show_detail'] ?? 0) === 1
                            && !empty($member['slug'])
                            && $teamPageUrl !== '';


                        $detailUrl = '';

                        if ($hasDetail) {

                            $detailUrl =
                                rtrim(
                                    $teamPageUrl,
                                    '/'
                                )
                                . '/'
                                . trim(
                                    (string)$member['slug'],
                                    '/'
                                );
                        }


                        $phoneLink = '';

                        if (!empty($member['phone'])) {

                            $phoneLink = preg_replace(
                                '/[^0-9+]/',
                                '',
                                $member['phone']
                            );
                        }

                        ?>


                        <div class="col-md-6 col-lg-4">

                            <article
                                class="card h-100 border-0 shadow-sm <?= e($cardBackground) ?> text-center"
                            >


                                <?php if (!empty($member['photo'])): ?>

                                    <div class="p-5 pb-0">


                                        <?php if ($hasDetail): ?>

                                            <a href="<?= e($detailUrl) ?>">

                                        <?php endif; ?>


                                            <img
                                                src="<?= e($member['photo']) ?>"
                                                alt="<?= e($member['name']) ?>"
                                                class="rounded-circle border"
                                                style="
                                                    width:160px;
                                                    height:160px;
                                                    object-fit:cover;
                                                "
                                            >


                                        <?php if ($hasDetail): ?>

                                            </a>

                                        <?php endif; ?>


                                    </div>

                                <?php endif; ?>


                                <div class="card-body d-flex flex-column">


                                    <h3 class="h5 mb-1">

                                        <?php if ($hasDetail): ?>

                                            <a
                                                href="<?= e($detailUrl) ?>"
                                                class="text-decoration-none text-color-dark"
                                            >
                                                <?= e($member['name']) ?>
                                            </a>

                                        <?php else: ?>

                                            <?= e($member['name']) ?>

                                        <?php endif; ?>

                                    </h3>


                                    <?php if (!empty($member['position'])): ?>

                                        <div class="text-muted small mb-3">

                                            <?= e($member['position']) ?>

                                        </div>

                                    <?php endif; ?>


                                    <div class="mt-auto">


                                        <?php if (!empty($member['email'])): ?>

                                            <div class="mb-1">

                                                <a
                                                    href="mailto:<?= e($member['email']) ?>"
                                                >

                                                    <i class="fas fa-envelope me-1"></i>

                                                    <?= e($member['email']) ?>

                                                </a>

                                            </div>

                                        <?php endif; ?>


                                        <?php if (!empty($member['phone'])): ?>

                                            <div class="<?= $hasDetail ? 'mb-3' : '' ?>">

                                                <a
                                                    href="tel:<?= e($phoneLink) ?>"
                                                >

                                                    <i class="fas fa-phone me-1"></i>

                                                    <?= e($member['phone']) ?>

                                                </a>

                                            </div>

                                        <?php endif; ?>


                                        <?php if ($hasDetail): ?>

                                            <a
                                                href="<?= e($detailUrl) ?>"
                                                class="btn btn-primary btn-3 mt-2"
                                            >
                                                Zobrazit profil
                                            </a>

                                        <?php endif; ?>


                                    </div>

                                </div>

                            </article>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>


            <?php if (
                $showTeamButton
                && $teamPageUrl !== ''
                && $totalMembers > count($members)
            ): ?>

                <div class="text-center mt-5">

                    <a
                        href="<?= e($teamPageUrl) ?>"
                        class="btn btn-primary btn-3"
                    >
                        Zobrazit celý tým
                    </a>

                </div>

            <?php endif; ?>


        <?php endif; ?>

    </div>

</section>