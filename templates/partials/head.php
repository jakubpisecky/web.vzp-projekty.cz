<?php
// /templates/partials/head.php

// Základní nastavení z tabulky settings
$siteTitle   = setting('site_title', 'Můj web');
$siteTagline = setting('site_tagline', '');
$siteDesc    = setting('site_description', '');

// Router (index.php) ti typicky předá $title a $meta_description
$pageTitle   = $title ?? $siteTitle;
$pageDesc    = $meta_description ?? '';

// Vzor pro meta title – např. "{title} | {site}"
$titlePattern = setting('seo_meta_title_pattern', '{title} | {site}');
if ($titlePattern) {
    $seoTitle = strtr($titlePattern, [
        '{title}' => $pageTitle ?: $siteTitle,
        '{site}'  => $siteTitle,
    ]);
} else {
    $seoTitle = $pageTitle ?: $siteTitle;
}

// Popis – nejdřív konkrétní, pak obecný a nakonec default
$seoDesc = $pageDesc !== '' ? $pageDesc : (
    $siteDesc !== '' ? $siteDesc : setting('seo_meta_description_default', '')
);

// Keywords – klidně si je někdy přidej do settings (např. "seo_keywords")
$seoKeys = $seo_keywords ?? setting('seo_keywords', '');

// Favicon + případné logo z settings
$favicon = setting('site_favicon_url', '');
if ($favicon) {
    $favicon = media_url($favicon);
} else {
    $favicon = '/favicon.ico';
}

// Případné globální noindex (třeba pro staging) – nechávám volitelné
$noindex = !empty($seo['noindex'] ?? null);
?>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title><?= htmlspecialchars($seoTitle) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1.0, shrink-to-fit=no">
<?php if ($seoDesc): ?><meta name="description" content="<?= htmlspecialchars($seoDesc) ?>"><?php endif; ?>
<?php if ($seoKeys): ?><meta name="keywords" content="<?= htmlspecialchars($seoKeys) ?>"><?php endif; ?>
<?php if ($noindex): ?><meta name="robots" content="noindex, nofollow"><?php endif; ?>

<link href="https://fonts.googleapis.com/css?family=Poppins:100,200,300,400,500,600,700,800,900" rel="stylesheet" type="text/css">
<link rel="stylesheet" href="<?= $themeBase ?>/vendor/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="<?= $themeBase ?>/vendor/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="<?= $themeBase ?>/vendor/animate/animate.min.css">
<link rel="stylesheet" href="<?= $themeBase ?>/vendor/simple-line-icons/css/simple-line-icons.min.css">
<link rel="stylesheet" href="<?= $themeBase ?>/vendor/linear-icons/css/linear-icons.min.css">
<link rel="stylesheet" href="<?= $themeBase ?>/vendor/owl.carousel/assets/owl.carousel.min.css">
<link rel="stylesheet" href="<?= $themeBase ?>/vendor/owl.carousel/assets/owl.theme.default.min.css">
<link rel="stylesheet" href="<?= $themeBase ?>/vendor/magnific-popup/magnific-popup.min.css">
<link rel="stylesheet" href="<?= $themeBase ?>/css/theme.css">
<link rel="stylesheet" href="<?= $themeBase ?>/css/theme-elements.css">
<link rel="stylesheet" href="<?= $themeBase ?>/vendor/rs-plugin/css/settings.css">
<link rel="stylesheet" href="<?= $themeBase ?>/vendor/rs-plugin/css/layers.css">
<link rel="stylesheet" href="<?= $themeBase ?>/vendor/rs-plugin/css/navigation.css">
<link rel="stylesheet" href="<?= $themeBase ?>/css/skins/default.css">
<link rel="stylesheet" href="<?= $themeBase ?>/css/custom.css">

<link rel="icon" href="<?= htmlspecialchars($favicon) ?>">

<script src="<?= $themeBase ?>/vendor/modernizr/modernizr.min.js"></script>
