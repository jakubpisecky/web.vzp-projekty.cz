<?php
// /templates/404.php
// Očekává: $title, $meta_description (z index.php)

$assets = ['base' => '/public/themes/ezy']; // pokud máš jinou cestu k EZY, uprav
ob_start();
?>

<div role="main" class="main">
  <section class="page-header">
					<div class="container">
						<div class="row align-items-center">
							<div class="col-md-8 text-start">
								<h1 class="font-weight-bold"><?= htmlspecialchars($title ?? 'Stránka nenalezena') ?></h1>
							</div>
							<div class="col-md-4">
								  <ul class="breadcrumb justify-content-start justify-content-md-end mb-0 text-4">
									<li><a href="/">Domů</a></li>
									<li class="active">Stránka nenalezena</li>
								</ul>
							</div>
						</div>
					</div>
				</section>

<div class="container my-5">
    <div class="row justify-content-center text-center">
        <div class="col-lg-8">
            <div class="display-1 fw-bold text-muted mb-3">404</div>
            <h2 class="mb-3">Stránka nebyla nalezena</h2>
            <p class="lead mb-4">
                Je nám líto, ale stránka, kterou hledáte, už neexistuje nebo byla přesunuta.
            </p>

            <a href="/" class="btn btn-outline-secondary">
                ← Zpět na homepage
            </a>
        </div>
    </div>
</div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
