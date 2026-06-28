<?php
// /templates/homepage.php
// očekává: $page, $conn, $title, $meta_description, helper e()

$themeBase = $GLOBALS['themeBase'] ?? '/public/themes/ezy';

ob_start();
?>

<div role="main" class="main">

<?php
// REVOLUTION SLIDER – ponecháno napevno přes modul bannery
global $conn;

$slides = [];

if ($conn instanceof mysqli) {
    $res = $conn->query("
        SELECT *
        FROM banners
        WHERE status = 'published'
        ORDER BY sort_order ASC, id ASC
    ");

    while ($row = $res->fetch_assoc()) {
        $slides[] = $row;
    }
}
?>

<div class="slider-container slider-container-height-550 rev_slider_wrapper">
    <div id="revolutionSlider"
         class="slider rev_slider"
         data-version="5.4.8"
         data-plugin-revolution-slider
         data-plugin-options="{'delay': 9000, 'gridwidth': [1140,960,720,540], 'gridheight': [550,550,550,550], 'responsiveLevels': [4096,1200,992,576], 'parallax': { 'type': 'mouse', 'origo': 'slidercenter', 'speed': 2000, 'levels': [2,3,4,5,6,7,12,16,10,50], 'disable_onmobile': 'on' }, 'navigation' : {'arrows': { 'enable': true, 'hide_under': 767, 'style': 'slider-arrows-style-3' }, 'bullets': {'enable': true, 'style': 'bullets-style-2', 'h_align': 'center', 'v_align': 'bottom', 'space': 7, 'v_offset': 25, 'h_offset': 0}}}">

        <ul>
            <?php if ($slides): ?>
                <?php foreach ($slides as $slide): ?>
                    <?php
                    $imgRaw   = $slide['image'] ?? $slide['image_url'] ?? '';
                    $img      = $imgRaw ? media_url($imgRaw) : $themeBase . '/img/slides/multi-purpose/slide-1-1.jpg';

                    $slideTitle = trim($slide['title'] ?? '');
                    $sub        = trim($slide['subheadline'] ?? ($slide['subtitle'] ?? ''));
                    $btnLabel   = trim($slide['button_label'] ?? ($slide['link_label'] ?? 'Více informací'));
                    $btnUrl     = trim($slide['button_url'] ?? ($slide['link_url'] ?? '#'));
                    ?>

                    <li data-transition="fade">
                        <img src="<?= e($img) ?>"
                             alt=""
                             data-bgposition="50% 20%"
                             data-bgfit="cover"
                             data-bgrepeat="no-repeat"
                             data-kenburns="on"
                             data-duration="12000"
                             data-ease="Linear.easeNone"
                             data-scalestart="110"
                             data-scaleend="100"
                             data-offsetstart="250 100"
                             class="rev-slidebg">

                        <?php if ($sub !== ''): ?>
                            <div class="tp-caption text-color-light font-weight-light ws-normal letter-spacing-0"
                                 data-frames='[{"delay":2600,"speed":1300,"frame":"0","from":"opacity:0;y:10%;","to":"opacity:1;y:0;","ease":"Power3.easeInOut"},{"delay":"wait","speed":300,"frame":"999","to":"opacity:0;fb:0;","ease":"Power3.easeInOut"}]'
                                 data-x="35"
                                 data-y="center"
                                 data-voffset="['-85','-85','-85','-85']"
                                 data-width="['631','631','631','463']"
                                 data-textAlign="['left','left','left','center']"
                                 data-fontsize="['29','29','29','29']"
                                 data-lineheight="['34','34','34','34']">
                                <?= e($sub) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($slideTitle !== ''): ?>
                            <h1 class="tp-caption text-color-light font-alternative font-weight-bold ws-normal letter-spacing-0"
                                data-frames='[{"delay":1000,"speed":2000,"frame":"0","from":"sX:1.5;opacity:0;fb:20px;","to":"o:1;fb:0;","ease":"Power3.easeInOut"},{"delay":"wait","speed":300,"frame":"999","to":"opacity:0;fb:0;","ease":"Power3.easeInOut"}]'
                                data-x="35"
                                data-y="center"
                                data-voffset="['-30','-30','-30','-30']"
                                data-width="['635','635','635','468']"
                                data-textAlign="['left','left','left','center']"
                                data-fontsize="['75','75','75','55']"
                                data-lineheight="['81','81','81','60']">
                                <?= e($slideTitle) ?>
                            </h1>
                        <?php endif; ?>

                        <?php if ($btnUrl !== ''): ?>
                            <a class="tp-caption btn btn-rounded btn-primary font-weight-semibold letter-spacing-0"
                               href="<?= e($btnUrl) ?>"
                               data-frames='[{"delay":3400,"speed":1300,"frame":"0","from":"opacity:0;y:10%;","to":"opacity:1;y:0;","ease":"Power3.easeInOut"},{"delay":"wait","speed":300,"frame":"999","to":"opacity:0;fb:0;","ease":"Power3.easeInOut"}]'
                               data-x="center"
                               data-hoffset="['-25','-25','-25','0']"
                               data-y="center"
                               data-voffset="['80','80','80','80']"
                               data-start="2100"
                               data-whitespace="nowrap"
                               data-fontsize="['14','14','14','19']"
                               data-paddingtop="['17','17','17','21']"
                               data-paddingbottom="['17','17','17','21']"
                               data-paddingleft="['65','65','65','75']"
                               data-paddingright="['65','65','65','75']">
                                <?= e($btnLabel) ?>
                            </a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li data-transition="fade">
                    <img src="<?= e($themeBase) ?>/img/slides/multi-purpose/slide-1-1.jpg"
                         alt=""
                         data-bgposition="50% 20%"
                         data-bgfit="cover"
                         data-bgrepeat="no-repeat"
                         data-kenburns="on"
                         data-duration="12000"
                         data-ease="Linear.easeNone"
                         data-scalestart="110"
                         data-scaleend="100"
                         data-offsetstart="250 100"
                         class="rev-slidebg">

                    <h1 class="tp-caption text-color-light font-alternative font-weight-bold ws-normal letter-spacing-0"
                        data-frames='[{"delay":1000,"speed":2000,"frame":"0","from":"sX:1.5;opacity:0;fb:20px;","to":"o:1;fb:0;","ease":"Power3.easeInOut"},{"delay":"wait","speed":300,"frame":"999","to":"opacity:0;fb:0;","ease":"Power3.easeInOut"}]'
                        data-x="35"
                        data-y="center"
                        data-voffset="['-30','-30','-30','-30']"
                        data-width="['635','635','635','468']"
                        data-textAlign="['left','left','left','center']"
                        data-fontsize="['75','75','75','55']"
                        data-lineheight="['81','81','81','60']">
                        Vítejte na webu
                    </h1>
                </li>
            <?php endif; ?>
        </ul>

    </div>
</div>

<?php renderPageBlocks($conn, (int)$page['id']); ?>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';