<section class="section <?= e($block['section_class'] ?? '') ?>">
    <div class="container">
        <?php if (!empty($block['title'])): ?>
            <h2 class="font-weight-bold mb-4"><?= e($block['title']) ?></h2>
        <?php endif; ?>

        <div class="content">
            <?= $block['content'] ?>
        </div>
    </div>
</section>