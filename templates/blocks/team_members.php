<?php
// /templates/blocks/team_members.php

/*
 * =====================================================
 * Najdeme stránku s kompletním výpisem týmu
 * =====================================================
 *
 * URL tedy nemusí být /tym.
 * Může být například /vypis-tymu, /nas-tym atd.
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
 * Počet aktivních členů
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
 * První 3 členové týmu
 * =====================================================
 */

$stmt = $conn->prepare("
    SELECT
        id,
        name,
        slug,
        position,
        email,
        phone,
        photo,
        show_detail
    FROM team_members
    WHERE is_active = 1
    ORDER BY sort_order ASC, id ASC
    LIMIT 3
");

$stmt->execute();

$members = $stmt->get_result();

$stmt->close();
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


        <?php if ($members->num_rows === 0): ?>

            <p class="text-muted">
                Zatím nejsou přidáni žádní pracovníci.
            </p>

        <?php else: ?>

            <div class="row g-4">

                <?php while ($member = $members->fetch_assoc()): ?>

                    <?php

                    /*
                     * Detail člena je možný pouze pokud:
                     *
                     * - má show_detail = 1
                     * - má slug
                     * - existuje stránka výpisu týmu
                     */
                    $hasDetail =
                        (int)($member['show_detail'] ?? 0) === 1
                        && !empty($member['slug'])
                        && $teamPageUrl !== '';

                    $detailUrl = '';

                    if ($hasDetail) {
                        $detailUrl =
                            rtrim($teamPageUrl, '/')
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
                            class="card h-100 border-0 shadow-sm bg-light-5 text-center"
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

                <?php endwhile; ?>

            </div>


            <?php if (
                $teamPageUrl !== ''
                && $totalMembers > 3
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