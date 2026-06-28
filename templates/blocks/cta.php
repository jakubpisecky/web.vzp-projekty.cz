<section class="section <?= e($block['section_class'] ?? 'bg-primary') ?> text-light">
    <div class="container text-center">

        <?php if (!empty($block['title'])): ?>
            <h2 class="font-weight-bold text-light mb-3">
                <?= e($block['title']) ?>
            </h2>
        <?php endif; ?>

        <?php if (!empty($block['content'])): ?>
            <div class="mb-4 cta-content">
                <?= $block['content'] ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($block['button_text']) && !empty($block['button_url'])): ?>
            <a href="<?= e($block['button_url']) ?>" class="btn btn-light btn-rounded btn-4">
                <?= e($block['button_text']) ?>
            </a>
        <?php endif; ?>

    </div>
</section>