<?php

$meta_description = $meta_description
    ?? ($page['meta_description'] ?? '')
    ?? text_excerpt($page['content'] ?? '', 180);

$meta_image = $meta_image ?? null;
$breadcrumbs = page_breadcrumbs($conn, $page);

$teamBaseUrl = '/' . trim($page['slug'] ?? 'tym', '/');


/*
 * =====================================================
 * Kategorie týmu + členové
 * =====================================================
 */

$stmt = $conn->prepare("
    SELECT
        tm.id,
        tm.name,
        tm.slug,
        tm.position,
        tm.email,
        tm.phone,
        tm.photo,
        tm.show_detail,
        tm.category_id,

        tc.id AS team_category_id,
        tc.name AS category_name,
        tc.sort_order AS category_sort_order

    FROM team_members tm

    LEFT JOIN team_categories tc
        ON tc.id = tm.category_id
        AND tc.is_active = 1

    WHERE tm.is_active = 1

    ORDER BY
        CASE
            WHEN tc.id IS NULL THEN 1
            ELSE 0
        END ASC,
        tc.sort_order ASC,
        tc.name ASC,
        tm.sort_order ASC,
        tm.id ASC
");

$stmt->execute();
$result = $stmt->get_result();


/*
 * =====================================================
 * Seskupení členů podle kategorií
 * =====================================================
 */

$teamGroups = [];

while ($member = $result->fetch_assoc()) {

    if (!empty($member['team_category_id'])) {

        $groupKey = 'category_' . (int)$member['team_category_id'];
        $groupTitle = $member['category_name'];

    } else {

        $groupKey = 'uncategorized';
        $groupTitle = 'Ostatní';
    }


    if (!isset($teamGroups[$groupKey])) {

        $teamGroups[$groupKey] = [
            'title' => $groupTitle,
            'members' => []
        ];
    }


    $teamGroups[$groupKey]['members'][] = $member;
}

$stmt->close();


ob_start();
?>

<div role="main" class="main">

    <section class="page-header">

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


    <section class="section pt-4">

        <div class="container">


            <?php if (!empty($page['content'])): ?>

                <div class="content mb-5">
                    <?= $page['content'] ?>
                </div>

            <?php endif; ?>


            <?php if (!$teamGroups): ?>

                <p class="text-muted">
                    Zatím nejsou přidáni žádní pracovníci.
                </p>


            <?php else: ?>


                <?php foreach ($teamGroups as $group): ?>


                    <!-- Kategorie -->

                    <div class="mb-5">

                        <h2 class="font-weight-bold mb-4">
                            <?= e($group['title']) ?>
                        </h2>


                        <div class="row g-4">


                            <?php foreach ($group['members'] as $member): ?>


                                <?php

                                $hasDetail =
                                    (int)($member['show_detail'] ?? 0) === 1
                                    && !empty($member['slug']);


                                $detailUrl =
                                    $teamBaseUrl
                                    . '/'
                                    . $member['slug'];


                                $phoneLink =
                                    !empty($member['phone'])
                                        ? preg_replace(
                                            '/[^0-9+]/',
                                            '',
                                            $member['phone']
                                        )
                                        : '';

                                ?>


                                <div class="col-md-6 col-lg-4">


                                    <article class="card h-100 border-0 shadow-sm bg-light-5 text-center">


                                        <?php if (!empty($member['photo'])): ?>


                                            <div class="p-5 pb-0">


                                                <?php if ($hasDetail): ?>

                                                    <a href="<?= e($detailUrl) ?>">

                                                <?php endif; ?>


                                                    <img
                                                        src="<?= e($member['photo']) ?>"
                                                        alt="<?= e($member['name']) ?>"
                                                        class="rounded-circle border"
                                                        style="width:160px;height:160px;object-fit:cover;"
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

                                                        <a href="mailto:<?= e($member['email']) ?>">

                                                            <i class="fas fa-envelope me-1"></i>

                                                            <?= e($member['email']) ?>

                                                        </a>

                                                    </div>


                                                <?php endif; ?>


                                                <?php if (!empty($member['phone'])): ?>


                                                    <div class="mb-3">

                                                        <a href="tel:<?= e($phoneLink) ?>">

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


                    </div>


                <?php endforeach; ?>


            <?php endif; ?>


        </div>

    </section>

</div>

<?php

$content = ob_get_clean();

include __DIR__ . '/layout.php';