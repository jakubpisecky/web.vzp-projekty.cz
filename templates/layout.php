<!DOCTYPE html>
<html lang="zxx">
<head>
  <?php
    $assets = $assets ?? ['base' => '/public/themes/ezy'];
    $themeBase = rtrim($assets['base'], '/');
    $GLOBALS['themeBase'] = $themeBase;
    $GLOBALS['assets'] = $assets;
?>
  <?php include __DIR__ . '/partials/head.php'; ?>
</head>
<body>
  <div class="body">
  <?php include __DIR__ . '/partials/header.php'; ?>

  <?= $content ?>

  <?php include __DIR__ . '/partials/footer.php'; ?>
  <!-- Bootstrap JS (toggler v headeru) -->
    <script src="<?= $themeBase ?>/vendor/jquery/jquery.min.js"></script>
    <script src="<?= $themeBase ?>/vendor/jquery.appear/jquery.appear.min.js"></script>
		<script src="<?= $themeBase ?>/vendor/jquery.easing/jquery.easing.min.js"></script>
		<script src="<?= $themeBase ?>/vendor/jquery.cookie/jquery.cookie.js"></script>
		<script src="<?= $themeBase ?>/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
		<script src="<?= $themeBase ?>/vendor/common/common.min.js"></script>
		<script src="<?= $themeBase ?>/vendor/jquery.validation/jquery.validate.min.js"></script>
		<script src="<?= $themeBase ?>/vendor/jquery.easy-pie-chart/jquery.easypiechart.min.js"></script>
		<script src="<?= $themeBase ?>/vendor/jquery.gmap/jquery.gmap.min.js"></script>
		<script src="<?= $themeBase ?>/vendor/jquery.lazyload/jquery.lazyload.min.js"></script>
		<script src="<?= $themeBase ?>/vendor/isotope/jquery.isotope.min.js"></script>
		<script src="<?= $themeBase ?>/vendor/owl.carousel/owl.carousel.min.js"></script>
		<script src="<?= $themeBase ?>/vendor/magnific-popup/jquery.magnific-popup.min.js"></script>
		<script src="<?= $themeBase ?>/vendor/vide/jquery.vide.min.js"></script>
		<script src="<?= $themeBase ?>/vendor/vivus/vivus.min.js"></script>

		<!-- Theme Base, Components and Settings -->
		<script src="<?= $themeBase ?>/js/theme.js"></script>

		<!-- Current Page Vendor and Views -->
		<script src="<?= $themeBase ?>/vendor/rs-plugin/js/jquery.themepunch.tools.min.js"></script>
		<script src="<?= $themeBase ?>/vendor/rs-plugin/js/jquery.themepunch.revolution.min.js"></script>

		<!-- Theme Custom -->
		<script src="<?= $themeBase ?>/js/custom.js"></script>

		<!-- Theme Initialization Files -->
		<script async src="<?= $themeBase ?>/js/theme.init.js"></script>

		<!-- Examples -->
		<script src="<?= $themeBase ?>/js/examples/examples.portfolio.js"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

  <!-- Analytics (BODY) -->
<?= analytics_body_html() ?>

<?php if (setting_bool('cookiebar_enabled')): ?>
  <div id="cookiebar" class="position-fixed bottom-0 start-0 end-0 p-3" style="z-index:1055; display:none;">
    <div class="bg-dark text-white p-3 rounded shadow d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
      <div><?= setting('cookiebar_text', 'Tento web používá soubory cookies.') ?></div>
      <button id="cookiebar-accept" class="btn btn-light btn-sm">Rozumím</button>
    </div>
  </div>
  <script>
    (function () {
      var key='cookiebarAccepted';
      if(!localStorage.getItem(key)){
        var el=document.getElementById('cookiebar'); if(el) el.style.display='block';
        var btn=document.getElementById('cookiebar-accept');
        if(btn) btn.addEventListener('click', function(){ localStorage.setItem(key,'1'); el.style.display='none'; });
      }
    })();
  </script>
<?php endif; ?>
</div>
</body>
</html>
