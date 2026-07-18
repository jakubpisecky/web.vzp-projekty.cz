<?php
// === Globální helpery pro frontend ===
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Vrátí nakonfigurovaný PHPMailer podle settings.
 * Pokud není PHPMailer dostupný, vrátí null.
 */
function mailer_from_settings(): ?PHPMailer
{
    if (!class_exists(PHPMailer::class)) {
        // UPRAV CESTY podle svého projektu
        require_once __DIR__ . '/php-mailer/src/Exception.php';
        require_once __DIR__ . '/php-mailer/src/PHPMailer.php';
        require_once __DIR__ . '/php-mailer/src/SMTP.php';
    }

    if (!class_exists(PHPMailer::class)) {
        return null;
    }

    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';

    $smtpEnabled = (int) setting('smtp_enabled', 0) === 1;

    if ($smtpEnabled) {
        $mail->isSMTP();
        $mail->Host       = setting('smtp_host', '');
        $mail->Port       = (int) setting('smtp_port', 587);
        $mail->SMTPAuth   = true;
        $mail->Username   = setting('smtp_username', '');
        $mail->Password   = setting('smtp_password', '');
        $secure           = setting('smtp_secure', 'tls');
        if ($secure === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($secure === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
    }

    $fromEmail = setting('smtp_from_email', setting('contact_email', 'no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost')));
    $fromName  = setting('smtp_from_name', setting('site_title', 'Web'));

    $mail->setFrom($fromEmail, $fromName);

    return $mail;
}

function save_contact_message(array $page, array $fields, bool $emailSent): ?int
{
    global $conn;
    if (!($conn instanceof mysqli)) {
        return null;
    }

    $pageId   = isset($page['id']) ? (int)$page['id'] : null;
    $pageSlug = $page['slug'] ?? null;

    $name    = $fields['name']    ?? '';
    $email   = $fields['email']   ?? '';
    $phone   = $fields['phone']   ?? '';
    $subject = $fields['subject'] ?? '';
    $message = $fields['message'] ?? '';

    $ip        = $_SERVER['REMOTE_ADDR']  ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $emailSentFlag = $emailSent ? 1 : 0;

    $stmt = $conn->prepare("
        INSERT INTO contact_messages
        (created_at, page_id, page_slug, name, email, phone, subject, message, ip_address, user_agent, email_sent)
        VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        error_log('save_contact_message: prepare failed: ' . $conn->error);
        return null;
    }

    $stmt->bind_param(
        "issssssssi",
        $pageId,
        $pageSlug,
        $name,
        $email,
        $phone,
        $subject,
        $message,
        $ip,
        $userAgent,
        $emailSentFlag
    );

    if (!$stmt->execute()) {
        error_log('save_contact_message: execute failed: ' . $stmt->error);
        $stmt->close();
        return null;
    }

    $id = $stmt->insert_id;
    $stmt->close();

    return $id;
}


/**
 * Zpracuje kontaktní formulář z frontendu.
 * Přesměruje zpět na /{slug}#kontakt-form s flash zprávou v $_SESSION.
 */
function handle_contact_form(array $page): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $slug        = $page['slug'] ?? 'kontakt';
    $redirectUrl = '/' . $slug . '#kontakt-form';

    // 1) Honeypot – když je vyplněný, děláme, že je vše ok, ale nic neposíláme
    if (!empty($_POST['website'] ?? '')) {
        $_SESSION['contact_success'] = 'Zpráva byla odeslána.';
        header('Location: ' . $redirectUrl);
        exit;
    }

    // 2) Data z formuláře
    $fields = [
        'name'    => trim($_POST['name'] ?? ''),
        'email'   => trim($_POST['email'] ?? ''),
        'phone'   => trim($_POST['phone'] ?? ''),
        'subject' => trim($_POST['subject'] ?? ''),
        'message' => trim($_POST['message'] ?? ''),
    ];

    $errors = [];

    // 3) Validace polí
    if ($fields['name'] === '') {
        $errors['name'] = 'Prosím vyplňte jméno.';
    }

    if ($fields['email'] === '' || !filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Zadejte platný e-mail.';
    }

    if ($fields['subject'] === '') {
        $errors['subject'] = 'Prosím vyplňte předmět.';
    }

    if ($fields['message'] === '') {
        $errors['message'] = 'Prosím napište zprávu.';
    }

    // 4) reCAPTCHA – až po tom, co máme $fields
    $recaptchaToken = $_POST['g-recaptcha-response'] ?? null;
    if (!verify_recaptcha_token($recaptchaToken)) {
        $errors['recaptcha'] = 'Potvrďte, že nejste robot.';
    }

    // 5) Když jsou chyby (včetně recaptchy) → uložit do session a redirect
    if (!empty($errors)) {
        $_SESSION['contact_errors'] = $errors;
        $_SESSION['contact_old']    = $fields;
        header('Location: ' . $redirectUrl);
        exit;
    }

    // 6) Všechno OK → posíláme e-mail
    $siteTitle = setting('site_title', 'Web');
    $to        = setting('contact_email', setting('smtp_from_email', ''));

    if ($to === '') {
        $_SESSION['contact_error'] = 'Kontakt nelze odeslat – není nastaven cílový e-mail.';
        header('Location: ' . $redirectUrl);
        exit;
    }

    $fullSubject = '[' . $siteTitle . '] Nová zpráva z kontaktního formuláře: ' . $fields['subject'];

    $html  = '<p>Na webu <strong>' . htmlspecialchars($siteTitle) . '</strong> byla odeslána nová zpráva z kontaktního formuláře:</p>';
    $html .= '<table cellpadding="4" cellspacing="0" border="0">';
    $html .= '<tr><td><strong>Jméno:</strong></td><td>' . nl2br(htmlspecialchars($fields['name'])) . '</td></tr>';
    $html .= '<tr><td><strong>E-mail:</strong></td><td>' . htmlspecialchars($fields['email']) . '</td></tr>';
    if ($fields['phone'] !== '') {
        $html .= '<tr><td><strong>Telefon:</strong></td><td>' . htmlspecialchars($fields['phone']) . '</td></tr>';
    }
    $html .= '<tr><td valign="top"><strong>Zpráva:</strong></td><td>' . nl2br(htmlspecialchars($fields['message'])) . '</td></tr>';
    $html .= '</table>';

    $text  = "Na webu {$siteTitle} byla odeslána nová zpráva:\n\n";
    $text .= "Jméno: {$fields['name']}\n";
    $text .= "E-mail: {$fields['email']}\n";
    if ($fields['phone'] !== '') {
        $text .= "Telefon: {$fields['phone']}\n";
    }
    $text .= "\nZpráva:\n{$fields['message']}\n";

    $ok = send_mail_from_settings($to, $fullSubject, $html, $text);

// uložíme zprávu do DB (i když se e-mail nepodařil odeslat)
$messageId = save_contact_message($page, $fields, $ok);

unset($_SESSION['contact_old'], $_SESSION['contact_errors']);

if ($ok) {
    $_SESSION['contact_success'] = 'Děkujeme, vaše zpráva byla úspěšně odeslána.';
} else {
    $_SESSION['contact_error'] = 'Omlouváme se, při odesílání došlo k chybě. Zkuste to prosím později.';
}

header('Location: ' . $redirectUrl);
exit;
}



/**
 * Odešle e-mail dle nastavení.
 */
function send_mail_from_settings(string $to, string $subject, string $htmlBody, string $textBody = ''): bool
{
    $mail = mailer_from_settings();

    if ($mail) {
        try {
            $mail->clearAllRecipients();
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body    = $htmlBody;
            $mail->AltBody = $textBody ?: strip_tags(str_replace('<br>', "\n", $htmlBody));
            return $mail->send();
        } catch (Exception $e) {
            error_log('Mail error: ' . $e->getMessage());
            return false;
        }
    }

    // Fallback na mail() pokud PHPMailer není
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . setting('smtp_from_email', 'no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost')) . "\r\n";

    return mail($to, $subject, $htmlBody, $headers);
}

// Bezpečný výstup (HTML escape)
if (!function_exists('e')) {
  function e($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}

// Segmenty aktuální URL jako pole (['sekce','detail'] ...)
if (!function_exists('url_segments')) {
  function url_segments(): array {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $t = trim((string)$path, '/');
    return $t === '' ? [] : explode('/', $t);
  }
}

// Základní URL pro uploady (bere absolutní URL/cesty beze změny)
if (!function_exists('media_url')) {
  function media_url(?string $path): string {
    if (!$path) return '';
    if (preg_match('~^(?:https?:)?//|^/~', $path)) return $path;
    // Fallback, můžeš si předefinovat v includes/config.php
    if (!defined('UPLOAD_BASE_URL')) define('UPLOAD_BASE_URL', 'https://cms.vzp-projekty.cz/uploads');
    return rtrim(UPLOAD_BASE_URL, '/') . '/' . ltrim($path, '/');
  }
}

// URL fotky v albu: /uploads/galleries/{gallery_id}/{filename}
// Umí i plné URL a relativní cesty typu "galleries/13/file.jpg" nebo "13/file.jpg"
if (!function_exists('gallery_photo_url')) {
  function gallery_photo_url(string $filename, int $galleryId): string {
    if ($filename === '') return '';
    if (preg_match('~^(?:https?:)?//|^/~', $filename)) return $filename;
    $filename = ltrim($filename, '/');
    if (preg_match('~^galleries/\d+/~', $filename)) return media_url($filename);
    if (preg_match('~^\d+/~', $filename))        return media_url('galleries/'.$filename);
    return media_url('galleries/'.$galleryId.'/'.$filename);
  }
}

// Normalizace klíče šablony z administrace
if (!function_exists('tpl_key')) {
  function tpl_key(?string $tpl): string {
    $t = strtolower(trim((string)$tpl));
    $map = [
      'homepage'=>'homepage','home'=>'homepage','index'=>'homepage','domu'=>'homepage','domů'=>'homepage',
      'articles'=>'articles','news'=>'articles','blog'=>'articles','výpis článků'=>'articles','vypis clanku'=>'articles',
      'gallery'=>'gallery','galleries'=>'gallery','fotogalerie'=>'gallery',
      'contact'=>'contact','contact_form'=>'contact','kontakt'=>'contact','kontaktní formulář'=>'contact',
      'page'=>'page','obecná stránka'=>'page','obecna stranka'=>'page',
    ];
    if (isset($map[$t])) return $map[$t];
    // automaticky použij stejnojmenný soubor v /templates
    if ($t !== '' && preg_match('~^[a-z0-9_\-]+$~', $t) && is_file(__DIR__."/../templates/$t.php")) return $t;
    return 'page';
  }
}

// Includne šablonu a předá jí potřebné proměnné
if (!function_exists('include_template')) {
  function include_template(string $name): void {
    global $conn, $page, $section, $article, $gallery,
           $title, $meta_description,
           $articlesBase, $galleryBase, $homeSlug;

    $tpl = __DIR__ . "/../templates/{$name}.php";
    if (!is_file($tpl)) $tpl = __DIR__ . "/../templates/page.php";
    include $tpl;
  }
}

// === SETTINGS (tabulka: settings[key, value]) ===
if (!defined('SETTINGS_TABLE')) define('SETTINGS_TABLE', 'settings');
if (!defined('SETTINGS_KEY'))   define('SETTINGS_KEY',   'key');
if (!defined('SETTINGS_VALUE')) define('SETTINGS_VALUE', 'value');

$GLOBALS['SETTINGS'] = [];

/** Načti key→value do paměti (jen jednou). */
function settings_load_all(mysqli $conn): void {
  static $loaded = false; if ($loaded) return;
  $tbl = SETTINGS_TABLE; $k = SETTINGS_KEY; $v = SETTINGS_VALUE;

  $res = $conn->query("SELECT `$k` AS k, `$v` AS v FROM `$tbl`");
  $out = [];
  while ($row = $res->fetch_assoc()) $out[(string)$row['k']] = (string)$row['v'];
  $GLOBALS['SETTINGS'] = $out;
  $loaded = true;
}

/** Získání hodnoty. */
function setting(string $key, $default = null) {
  $s = $GLOBALS['SETTINGS'] ?? [];
  return array_key_exists($key, $s) ? $s[$key] : $default;
}

/** Bool přepínač (1,true,yes,on). */
function setting_bool(string $key, bool $default=false): bool {
  $v = setting($key, null); if ($v === null) return $default;
  return in_array(strtolower(trim((string)$v)), ['1','true','yes','on'], true);
}

/** JSON → array. */
function setting_json(string $key, $default=[]) {
  $v = setting($key, null); if ($v === null || $v === '') return $default;
  $d = json_decode($v, true); return is_array($d) ? $d : $default;
}

/** Pokud máš proxy /uploads → CMS, klidně nech BASE prázdné.
 *  Jinak si definuj v includes/config.php: define('UPLOAD_BASE_URL','https://cms.vzp-projekty.cz/uploads');
 */
if (!function_exists('media_url')) {
  function media_url(?string $path): string {
    if (!$path) return '';
    if (preg_match('~^(?:https?:)?//|^/~', $path)) return $path;
    $base = defined('UPLOAD_BASE_URL') ? rtrim(UPLOAD_BASE_URL,'/') : '/uploads';
    return $base . '/' . ltrim($path,'/');
  }
}

/** URL pro fotku z galerie: /uploads/galleries/{id}/{file}, umí i plné/relativní cesty. */
function gallery_photo_url(string $filename, int $galleryId): string {
  if ($filename === '') return '';
  if (preg_match('~^(?:https?:)?//|^/~', $filename)) return $filename;
  $filename = ltrim($filename,'/');
  if (preg_match('~^galleries/\d+/~', $filename)) return media_url($filename);
  if (preg_match('~^\d+/~', $filename))            return media_url('galleries/'.$filename);
  return media_url('galleries/'.$galleryId.'/'.$filename);
}

/** Titulek stránky podle vzoru z adminu (seo_meta_title_pattern). */
function seo_title(?string $pageTitle): string {
  $site    = setting('site_title','');
  $tagline = setting('site_tagline','');
  $pat     = setting('seo_meta_title_pattern', '{title} | {site}');
  $title   = (string)($pageTitle ?? $site);
  $out = strtr($pat, [
    '{title}'   => $title,
    '{site}'    => $site,
    '{tagline}' => $tagline,
  ]);
  // pro případ, že stránka nemá title a pattern by začínal oddělovačem
  return trim(preg_replace('~\s+\|\s+$~','', $out));
}

function client_ip(): string
{
    // respektuj proxy / load balancer, pokud nějaký je
    $keys = [
        'HTTP_X_FORWARDED_FOR',
        'HTTP_CLIENT_IP',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR',
    ];

    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $raw = $_SERVER[$key];

            // X_FORWARDED_FOR může obsahovat více IP "a, b, c"
            if ($key === 'HTTP_X_FORWARDED_FOR') {
                $parts = array_map('trim', explode(',', $raw));
                foreach ($parts as $p) {
                    if ($p !== '' && strtolower($p) !== 'unknown') {
                        return $p;
                    }
                }
            }

            return trim($raw);
        }
    }

    return '';
}


/** Maintenance stránka (503) s whitelistem IP (čárky/mezery). */
// Režim údržby frontendu ----------------------------------------------
function maintenance_guard(): void
{
    $enabled = (int) setting('maintenance_enabled', 0) === 1;
    $GLOBALS['MAINTENANCE_MODE'] = $enabled;

    if (!$enabled) {
        return;
    }

    // zjištění IP uživatele
    $ip = client_ip();

    // whitelist – můžeš zadat více IP oddělených čárkou, mezerou nebo novým řádkem
    $whitelistRaw = (string) setting('maintenance_ip_whitelist', '');
    $whitelist = array_filter(
        array_map('trim', preg_split('~[\s,;]+~', $whitelistRaw))
    );

    // localhost IP povolíme vždy
    $alwaysAllowed = ['127.0.0.1', '::1'];

    $isAllowed = in_array($ip, $alwaysAllowed, true);

    if (!$isAllowed && $whitelist) {
        foreach ($whitelist as $allowedIp) {
            if ($allowedIp === $ip) {
                $isAllowed = true;
                break;
            }
        }
    }

    // pokud je povolený (whitelist), jen nastavíme MAINTENANCE_MODE a pokračujeme dál
    if ($isAllowed) {
        return;
    }

    // ostatní uvidí stránku údržby
    http_response_code(503);
    header('Retry-After: 3600');

    $siteTitle = setting('site_title', 'Web je dočasně nedostupný');
    $message   = setting('maintenance_message', 'Probíhá plánovaná údržba. Zkuste to prosím později.');

    ?>
    <!DOCTYPE html>
    <html lang="cs">
    <head>
        <meta charset="utf-8">
        <title><?= htmlspecialchars($siteTitle) ?> – Údržba</title>
        <meta name="robots" content="noindex, nofollow">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            body {
                font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                background: #f5f5f5;
                color: #333;
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .box {
                background: #fff;
                padding: 2rem 2.5rem;
                border-radius: 8px;
                box-shadow: 0 10px 30px rgba(0,0,0,.08);
                max-width: 520px;
                text-align: center;
            }
            h1 {
                margin-top: 0;
                font-size: 1.6rem;
            }
            p {
                margin: .75rem 0;
            }
        </style>
    </head>
    <body>
        <div class="box">
            <h1>Dočasně nedostupné</h1>
            <p><?= nl2br(htmlspecialchars($message)) ?></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}





/** GA4 + Meta Pixel snippet (head/body). */
function analytics_head_html(): string {
  $ga = trim((string)setting('ga4_measurement_id',''));
  $mp = trim((string)setting('meta_pixel_id',''));
  $h  = '';

  if ($ga !== '') {
    $h .= <<<HTML
<!-- GA4 -->
<script async src="https://www.googletagmanager.com/gtag/js?id={$ga}"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '{$ga}');
</script>

HTML;
  }

  if ($mp !== '') {
    $h .= <<<HTML
<!-- Meta Pixel -->
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '{$mp}'); fbq('track', 'PageView');
</script>

HTML;
  }
  return $h;
}
function analytics_body_html(): string {
  $mp = trim((string)setting('meta_pixel_id',''));
  return $mp ? '<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id='
               . e($mp) . '&ev=PageView&noscript=1"/></noscript>' : '';
}

// ===== Pagination helpers =====

/** Bezpečně získá aktuální číslo stránky z ?page= */
function get_page_param(): int {
  $p = isset($_GET['page']) ? (int)$_GET['page'] : 1;
  return max(1, $p);
}

/** Vrátí prefix pro odkazy ve stránkování: "/cesta?param=1&" (bez page=) */
function pagination_base_url(): string {
  $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
  $q = $_GET ?? [];
  unset($q['page']);
  $qs = http_build_query($q);
  return $path . ($qs === '' ? '?' : '?' . $qs . '&');
}

/** Bootstrap stránkování (1 … n), „okno“ kolem aktivní stránky */
function render_pagination(int $totalItems, int $perPage, int $currentPage): void {
  if ($perPage <= 0) $perPage = 10;
  $totalPages = (int)max(1, ceil($totalItems / $perPage));
  if ($totalPages === 1) return;

  $base = pagination_base_url();
  $win  = 2; // kolik čísel vlevo/vpravo
  $start = max(1, $currentPage - $win);
  $end   = min($totalPages, $currentPage + $win);

  echo '<nav aria-label="Stránkování"><ul class="pagination justify-content-center mt-4">';
  // první / předchozí
  $disabled = ($currentPage <= 1) ? ' disabled' : '';
  echo '<li class="page-item'.$disabled.'"><a class="page-link" href="'.($currentPage<=1?'#':$base.'page=1').'" aria-label="První">«</a></li>';
  echo '<li class="page-item'.$disabled.'"><a class="page-link" href="'.($currentPage<=1?'#':$base.'page='.($currentPage-1)).'" aria-label="Předchozí">‹</a></li>';

  // případné "1 …"
  if ($start > 1) {
    echo '<li class="page-item"><a class="page-link" href="'.$base.'page=1">1</a></li>';
    if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
  }

  // okno
  for ($i=$start; $i<=$end; $i++) {
    $active = ($i === $currentPage) ? ' active' : '';
    echo '<li class="page-item'.$active.'"><a class="page-link" href="'.$base.'page='.$i.'">'.$i.'</a></li>';
  }

  // „… n“
  if ($end < $totalPages) {
    if ($end < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
    echo '<li class="page-item"><a class="page-link" href="'.$base.'page='.$totalPages.'">'.$totalPages.'</a></li>';
  }

  // další / poslední
  $disabled = ($currentPage >= $totalPages) ? ' disabled' : '';
  echo '<li class="page-item'.$disabled.'"><a class="page-link" href="'.($currentPage>=$totalPages?'#':$base.'page='.($currentPage+1)).'" aria-label="Další">›</a></li>';
  echo '<li class="page-item'.$disabled.'"><a class="page-link" href="'.($currentPage>=$totalPages?'#':$base.'page='.$totalPages).'" aria-label="Poslední">»</a></li>';

  echo '</ul></nav>';
}

// Klikatelné tel: odkazy
function tel_href(string $phone): string {
  $n = preg_replace('~[^\d\+]~', '', $phone);
  return 'tel:' . $n;
}

// Krátký výtah z HTML
function text_excerpt(string $html, int $len = 160): string {
  $t = trim(preg_replace('~\s+~', ' ', strip_tags($html)));
  if (function_exists('mb_strlen')) {
    return mb_strlen($t) > $len ? mb_substr($t, 0, $len) . '…' : $t;
  }
  return strlen($t) > $len ? substr($t, 0, $len) . '…' : $t;
}

// Absolutní URL aktuální stránky (pro canonical/og:url)
function canonical_url(): string {
  $base = rtrim(setting('site_url', ''), '/'); // např. https://web.vzp-projekty.cz
  $uri  = $_SERVER['REQUEST_URI'] ?? '/';
  if ($base) return $base . $uri;
  // fallback bez settings
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  return $scheme . '://' . $host . $uri;
}

// OG image z náhledu článku/stránky (s fallbackem na settings)
function meta_image(?string $explicitUrl = null): string {
  if ($explicitUrl) return media_url($explicitUrl);
  $def = setting('og_image_url', '');
  return $def ? media_url($def) : '';
}

// Najdi první rubriku se šablonou Articles/Gallery (pro sitemap/feed)
function first_articles_base(mysqli $conn): ?string {
  $res = $conn->query("SELECT slug FROM pages WHERE status='published' AND LOWER(template) IN ('articles','news','blog','výpis článků','vypis clanku') ORDER BY menu_order ASC, id ASC LIMIT 1");
  $row = $res?->fetch_assoc();
  return $row['slug'] ?? null;
}
function first_gallery_base(mysqli $conn): ?string {
  $res = $conn->query("SELECT slug FROM pages WHERE status='published' AND LOWER(template) IN ('gallery','galleries','fotogalerie') ORDER BY menu_order ASC, id ASC LIMIT 1");
  $row = $res?->fetch_assoc();
  return $row['slug'] ?? null;
}

// Breadcrumbs: obecné vykreslení
function render_breadcrumbs(array $items): void {
  if (empty($items)) return;
  echo '<ul class="breadcrumb justify-content-start justify-content-md-end mb-0 text-4">';
  $last = count($items) - 1;
  foreach ($items as $i => $it) {
    $active = $i === $last ? ' active' : '';
    $aria   = $i === $last ? ' aria-current="page"' : '';
    if ($active) {
      echo '<li class="breadcrumb-item'.$active.'"'.$aria.'>'.e($it['label']).'</li>';
    } else {
      echo '<li class="breadcrumb-item"><a href="'.e($it['href']).'">'.e($it['label']).'</a></li>';
    }
  }
  echo '</ul>';
}

// Breadcrumbs pro page (podle parent_id)
function page_breadcrumbs(mysqli $conn, array $page): array {
  $trail = [];
  $homeSlug = $GLOBALS['homeSlug'] ?? '';
  $homeUrl  = '/';
  $trail[] = ['label' => 'Domů', 'href' => $homeUrl];
  // posbírej předky
  $cur = $page;
  $chain = [];
  while (!empty($cur['parent_id'])) {
    $pid = (int)$cur['parent_id'];
    $res = $conn->query("SELECT id, title, slug, parent_id FROM pages WHERE id=".$pid);
    if (!$res || !$res->num_rows) break;
    $p = $res->fetch_assoc();
    $chain[] = $p;
    $cur = $p;
  }
  $chain = array_reverse($chain);
  foreach ($chain as $p) $trail[] = ['label' => $p['title'], 'href' => '/'.ltrim($p['slug'],'/')];
  // aktuální
  $trail[] = ['label' => $page['title'] ?? '', 'href' => '/'.ltrim($page['slug'] ?? '', '/')];
  return $trail;
}

function settings_social_links(): array
{
    $map = [
        'facebook'  => setting('social_facebook_url', ''),
        'instagram' => setting('social_instagram_url', ''),
        'twitter'   => setting('social_twitter_url', ''),
        'linkedin'  => setting('social_linkedin_url', ''),
        'youtube'   => setting('social_youtube_url', ''),
        'tiktok'    => setting('social_tiktok_url', ''),
    ];

    $items = [];
    foreach ($map as $type => $url) {
        $url = trim((string) $url);
        if ($url !== '') {
            $items[] = ['type' => $type, 'url' => $url];
        }
    }

    return $items;
}

function verify_recaptcha_token(?string $token): bool
{
    $enabled = (int) setting('recaptcha_enabled', 0) === 1;
    $secret  = trim((string) setting('recaptcha_secret_key', ''));

    if (!$enabled || $secret === '') {
        // reCAPTCHA je v adminu vypnutá nebo nenastavená → neblokujeme odeslání
        return true;
    }

    $token = trim((string) $token);
    if ($token === '') {
        return false;
    }

    $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';

    $postData = http_build_query([
        'secret'   => $secret,
        'response' => $token,
        'remoteip' => $remoteIp,
    ]);

    // Preferujeme cURL, pokud je dostupné
    if (function_exists('curl_init')) {
        $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postData,
            CURLOPT_TIMEOUT        => 5,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
    } else {
        // fallback na file_get_contents
        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => $postData,
                'timeout' => 5,
            ]
        ]);
        $response = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
    }

    if (!$response) {
        // pokud se nepodařilo ověřit, radši formulář blokneme
        return false;
    }

    $data = json_decode($response, true);
    return !empty($data['success']);
}
function getPageBlocks(mysqli $conn, int $pageId): array
{
    $blocks = [];

    $stmt = $conn->prepare("
        SELECT *
        FROM page_blocks
        WHERE page_id = ?
          AND is_active = 1
        ORDER BY sort_order ASC, id ASC
    ");
    $stmt->bind_param("i", $pageId);
    $stmt->execute();

    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $blocks[] = $row;
    }

    $stmt->close();

    return $blocks;
}
function renderPageBlocks(mysqli $conn, $page): void
{
    if (is_array($page)) {
        $pageId = (int)($page['id'] ?? 0);
    } else {
        $pageId = (int)$page;
    }

    if ($pageId <= 0) {
        return;
    }

    $stmt = $conn->prepare("
        SELECT *
        FROM page_blocks
        WHERE page_id = ?
          AND is_active = 1
        ORDER BY sort_order ASC, id ASC
    ");
    $stmt->bind_param("i", $pageId);
    $stmt->execute();
    $blocks = $stmt->get_result();
    $stmt->close();

    while ($block = $blocks->fetch_assoc()) {
        $type = preg_replace('/[^a-z0-9_]/', '', $block['type'] ?? '');

        if ($type === '') {
            continue;
        }

        $file = __DIR__ . '/../templates/blocks/' . $type . '.php';

        if (is_file($file)) {
            include $file;
        }
    }
}function handleRedirects(mysqli $conn): void
{
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path = '/' . trim($path, '/');

    if ($path === '') {
        $path = '/';
    }

    $segments = array_values(array_filter(explode('/', trim($path, '/'))));

    /*
     * 1) Existující stránka první úrovně: /kontakt
     */
    if (count($segments) === 1) {
        $slug = $segments[0];

        $stmt = $conn->prepare("
            SELECT id
            FROM pages
            WHERE slug = ?
              AND status = 'published'
            LIMIT 1
        ");
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $page = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($page) {
            return;
        }
    }

    /*
     * 2) Existující podstránka: /rodic/podstranka
     */
    if (count($segments) === 2) {
        [$parentSlug, $childSlug] = $segments;

        $stmt = $conn->prepare("
            SELECT child.id
            FROM pages parent
            INNER JOIN pages child ON child.parent_id = parent.id
            WHERE parent.slug = ?
              AND child.slug = ?
              AND parent.status = 'published'
              AND child.status = 'published'
            LIMIT 1
        ");
        $stmt->bind_param("ss", $parentSlug, $childSlug);
        $stmt->execute();
        $child = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($child) {
            return;
        }
    }

    /*
     * 3) Existující detail článku: /aktuality/clanek
     */
    if (count($segments) === 2) {
        [$sectionSlug, $articleSlug] = $segments;

        $stmt = $conn->prepare("
            SELECT a.id
            FROM pages p
            INNER JOIN articles a ON a.slug = ?
            WHERE p.slug = ?
              AND p.status = 'published'
              AND p.template = 'articles'
              AND a.status = 'published'
            LIMIT 1
        ");
        $stmt->bind_param("ss", $articleSlug, $sectionSlug);
        $stmt->execute();
        $article = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($article) {
            return;
        }
    }

    /*
     * 4) Existující detail galerie: /fotogalerie/album
     */
    if (count($segments) === 2) {
        [$sectionSlug, $gallerySlug] = $segments;

        $stmt = $conn->prepare("
            SELECT g.id
            FROM pages p
            INNER JOIN galleries g ON g.slug = ?
            WHERE p.slug = ?
              AND p.status = 'published'
              AND p.template IN ('gallery', 'galleries')
            LIMIT 1
        ");
        $stmt->bind_param("ss", $gallerySlug, $sectionSlug);
        $stmt->execute();
        $gallery = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($gallery) {
            return;
        }
    }

    /*
     * 5) Teprve když nic neexistuje, hledáme redirect.
     */
    $stmt = $conn->prepare("
        SELECT target_url, status_code
        FROM redirects
        WHERE source_url = ?
          AND is_active = 1
        LIMIT 1
    ");
    $stmt->bind_param("s", $path);
    $stmt->execute();
    $redirect = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$redirect) {
        return;
    }

    $code = (int)($redirect['status_code'] ?? 301);

    if (!in_array($code, [301, 302, 307, 308], true)) {
        $code = 301;
    }

    header('Location: ' . $redirect['target_url'], true, $code);
    exit;
}
function renderArticleBlocks(mysqli $conn, array $article): void
{
    $articleId = (int)($article['id'] ?? 0);

    if ($articleId <= 0) {
        return;
    }

    $stmt = $conn->prepare("
        SELECT *
        FROM article_blocks
        WHERE article_id = ?
          AND is_active = 1
        ORDER BY sort_order ASC, id ASC
    ");

    $stmt->bind_param("i", $articleId);
    $stmt->execute();

    $result = $stmt->get_result();

    $articleBlocks = [];

    while ($row = $result->fetch_assoc()) {
        $articleBlocks[] = $row;
    }

    $stmt->close();

    foreach ($articleBlocks as $block) {
        $type = preg_replace('/[^a-z0-9_]/', '', $block['type'] ?? '');

        if ($type === '') {
            continue;
        }

        $file = __DIR__ . '/../templates/blocks/' . $type . '.php';

        if (!is_file($file)) {
            continue;
        }

        include $file;
    }
}
/**
 * Ověření Google reCAPTCHA v2.
 *
 * @param string $token Hodnota z POST[g-recaptcha-response]
 * @return bool
 */
function verifyRecaptcha(string $token): bool
{
    $token = trim($token);

    if ($token === '') {
        return false;
    }

    $secret = trim((string)setting('recaptcha_secret_key'));

    if ($secret === '') {
        // Pokud není reCAPTCHA nastavena, považujeme ověření za neúspěšné.
        return false;
    }

    $postData = http_build_query([
        'secret'   => $secret,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ]);

    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $postData,
            'timeout' => 10
        ]
    ]);

    $response = @file_get_contents(
        'https://www.google.com/recaptcha/api/siteverify',
        false,
        $context
    );

    if ($response === false) {
        return false;
    }

    $result = json_decode($response, true);

    return !empty($result['success']);
}

