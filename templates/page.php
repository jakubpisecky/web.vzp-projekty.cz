
<?php
$meta_description = $meta_description
  ?? ($page['meta_description'] ?? '')
  ?? text_excerpt($page['content'] ?? '', 180);
$meta_image = $meta_image ?? null; // pokud někdy přidáš thumbnail pro stránky, tady ho použij
$breadcrumbs = page_breadcrumbs($conn, $page);

// dostupné: $page (z routeru), $title, $meta_description
ob_start(); ?>
<div role="main" class="main">
  <section class="page-header">
					<div class="container">
						<div class="row align-items-center">
							<div class="col-md-8 text-start">
								<h1 class="font-weight-bold"><?= e($page['title'] ?? '') ?></h1>

							</div>
							<div class="col-md-4">
								  <?php render_breadcrumbs($breadcrumbs); ?>
							</div>
						</div>
					</div>
				</section>

  <div class="container mb-5">
					<div class="row">
						<div class="col-md-12">
               <div class="content">
                <?= $page['content'] ?? '' /* HTML z editoru, nevypisovat přes e() */ ?>
              </div>
						</div>
					</div>
				</div>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
