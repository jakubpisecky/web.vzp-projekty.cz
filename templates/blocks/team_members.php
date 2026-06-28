<?php
// /templates/blocks/team_members.php

$layout = $block['layout'] ?: 'cards';

if (!in_array($layout, ['cards', 'list'], true)) {
    $layout = 'cards';
}

$stmt = $conn->prepare("
    SELECT name, position, description, email, phone, photo
    FROM team_members
    WHERE is_active = 1
    ORDER BY sort_order ASC, id ASC
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

            <p class="text-muted">Zatím nejsou přidáni žádní pracovníci.</p>

        <?php else: ?>

            <?php if ($layout === 'list'): ?>

                <?php while ($m = $members->fetch_assoc()): ?>
                    <div class="row pt-4 pb-1 align-items-start">

                        <?php if (!empty($m['photo'])): ?>
                            <div class="col-md-2 mb-3 mb-md-0">
                                <img src="<?= e($m['photo']) ?>"
                                     alt="<?= e($m['name']) ?>"
                                     class="img-fluid rounded border"
                                     style="width:140px;height:140px;object-fit:cover;">
                            </div>

                            <div class="col-md-10">
                        <?php else: ?>
                            <div class="col-12">
                        <?php endif; ?>

                                <h3 class="font-weight-semibold h4 mb-1">
                                    <?= e($m['name']) ?>
                                </h3>

                                <?php if (!empty($m['position'])): ?>
                                    <div class="text-muted mb-2">
                                        <?= e($m['position']) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($m['description'])): ?>
                                    <p class="mb-2">
                                        <?= nl2br(e($m['description'])) ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($m['email']) || !empty($m['phone'])): ?>
                                    <p class="mb-0">
                                        <?php if (!empty($m['email'])): ?>
                                            <a href="mailto:<?= e($m['email']) ?>" class="me-3">
                                                <i class="fas fa-envelope me-1"></i>
                                                <?= e($m['email']) ?>
                                            </a>
                                        <?php endif; ?>

                                        <?php if (!empty($m['phone'])): ?>
                                            <a href="tel:<?= e(preg_replace('/\s+/', '', $m['phone'])) ?>">
                                                <i class="fas fa-phone me-1"></i>
                                                <?= e($m['phone']) ?>
                                            </a>
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>

                            </div>
                    </div>

                    <hr class="my-4">
                <?php endwhile; ?>

            <?php else: ?>

                <div class="row g-4">
                    <?php while ($m = $members->fetch_assoc()): ?>

                        <div class="col-md-6 col-lg-4">
                            <article class="card h-100 border-0 shadow-sm bg-white text-center">

                                <?php if (!empty($m['photo'])): ?>
                                    <div class="p-4 pb-0">
                                        <img src="<?= e($m['photo']) ?>"
                                             alt="<?= e($m['name']) ?>"
                                             class="rounded-circle border"
                                             style="width:160px;height:160px;object-fit:cover;">
                                    </div>
                                <?php endif; ?>

                                <div class="card-body d-flex flex-column">

                                    <h3 class="h5 mb-1">
                                        <?= e($m['name']) ?>
                                    </h3>

                                    <?php if (!empty($m['position'])): ?>
                                        <div class="text-muted small mb-3">
                                            <?= e($m['position']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($m['description'])): ?>
                                        <p class="mb-3">
                                            <?= nl2br(e($m['description'])) ?>
                                        </p>
                                    <?php endif; ?>

                                    <div class="mt-auto">
                                        <?php if (!empty($m['email'])): ?>
                                            <div>
                                                <a href="mailto:<?= e($m['email']) ?>">
                                                    <?= e($m['email']) ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($m['phone'])): ?>
                                            <div>
                                                <a href="tel:<?= e(preg_replace('/\s+/', '', $m['phone'])) ?>">
                                                    <?= e($m['phone']) ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                </div>

                            </article>
                        </div>

                    <?php endwhile; ?>
                </div>

            <?php endif; ?>

        <?php endif; ?>

    </div>
</section>