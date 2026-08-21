<?php

$meta_description = $meta_description
    ?? ($page['meta_description'] ?? '')
    ?? text_excerpt($page['content'] ?? '', 180);

$meta_image = $meta_image ?? null;
$breadcrumbs = page_breadcrumbs($conn, $page);

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
");

$stmt->execute();
$members = $stmt->get_result();

$teamBaseUrl = '/' . trim($page['slug'] ?? 'tym', '/');

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

            <?php if ($members->num_rows === 0): ?>

                <p class="text-muted">
                    Zatím nejsou přidáni žádní pracovníci.
                </p>

            <?php else: ?>

                <div class="row g-4">

                    <?php while ($member = $members->fetch_assoc()): ?>

                        <?php
                        $hasDetail =
                            (int)($member['show_detail'] ?? 0) === 1
                            && !empty($member['slug']);

                        $detailUrl = $teamBaseUrl . '/' . $member['slug'];

                        $phoneLink = !empty($member['phone'])
                            ? preg_replace('/[^0-9+]/', '', $member['phone'])
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

                                    <h2 class="h5 mb-1">

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

                                    </h2>

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

                    <?php endwhile; ?>

                </div>

            <?php endif; ?>

        </div>
    </section>

</div>

<?php
$stmt->close();

$content = ob_get_clean();

include __DIR__ . '/layout.php';