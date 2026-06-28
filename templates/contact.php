<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$assets = ['base' => '/public/themes/ezy'];

$contactEmail   = trim((string) setting('contact_email', ''));
$contactPhone   = trim((string) setting('contact_phone', ''));
$contactAddress = setting('contact_address', '');
$siteTitle      = setting('site_title', 'Web');

// flash zprávy
$successMsg = $_SESSION['contact_success'] ?? null;
$errorMsg   = $_SESSION['contact_error']   ?? null;

// data z předchozího submitu
$old    = $_SESSION['contact_old']    ?? [];
$errors = $_SESSION['contact_errors'] ?? [];

// reCAPTCHA nastavení
$recaptchaEnabled = (int) setting('recaptcha_enabled', 0) === 1;
$recaptchaSiteKey = trim((string) setting('recaptcha_site_key', ''));

// po načtení je smažeme (flash)
unset($_SESSION['contact_success'], $_SESSION['contact_error'], $_SESSION['contact_old'], $_SESSION['contact_errors']);

ob_start();
?>
<section class="page-header mb-0 bg-color-light-scale-1">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 text-start">
                <h1 class="font-weight-bold mb-0">
                    <?= htmlspecialchars($page['title'] ?? 'Kontakt') ?>
                </h1>
            </div>
            <div class="col-md-6">
                <ul class="breadcrumb justify-content-start justify-content-md-end mb-0 text-4">
                    <li><a href="/">Domů</a></li>
                    <li class="active"><?= htmlspecialchars($page['title'] ?? 'Kontakt') ?></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- <div id="googlemaps" class="google-map" style="height: 350px;"></div> -->

<section class="section" id="kontakt-form">
    <div class="container">
        <div class="row text-center">
            <div class="col">
                <span class="top-sub-title text-color-primary">KONTAKTUJTE NÁS 2</span>
                <h2 class="font-weight-bold mb-2">Máte dotaz nebo poptávku?</h2>
                <p class="lead mb-0">
                    Napište nám pomocí formuláře nebo využijte kontaktní údaje níže.
                </p>
            </div>
        </div>

        <div class="row pt-5">
            <div class="col-lg-4 mb-4 mb-lg-0">
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="icon-box icon-box-style-1">
                            <div class="icon-box-icon">
                                <i class="lnr lnr-apartment text-color-primary"></i>
                            </div>
                            <div class="icon-box-info mt-1">
                                <div class="icon-box-info-title">
                                    <h3 class="font-weight-bold text-4 mb-0">Adresa</h3>
                                </div>
                                <p class="mb-0">
                                    <?= nl2br(htmlspecialchars($contactAddress ?: 'Adresa zatím není vyplněna.')) ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mb-4">
                        <div class="icon-box icon-box-style-1">
                            <div class="icon-box-icon icon-box-icon-no-top">
                                <i class="lnr lnr-envelope text-color-primary"></i>
                            </div>
                            <div class="icon-box-info mt-1">
                                <div class="icon-box-info-title">
                                    <h3 class="font-weight-bold text-4 mb-0">E-mail</h3>
                                </div>
                                <p class="mb-0">
                                    <?php if ($contactEmail): ?>
                                        <a href="mailto:<?= htmlspecialchars($contactEmail) ?>">
                                            <?= htmlspecialchars($contactEmail) ?>
                                        </a>
                                    <?php else: ?>
                                        <span>E-mail zatím není vyplněn.</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="icon-box icon-box-style-1">
                            <div class="icon-box-icon">
                                <i class="lnr lnr-phone-handset text-color-primary"></i>
                            </div>
                            <div class="icon-box-info mt-1">
                                <div class="icon-box-info-title">
                                    <h3 class="font-weight-bold text-4 mb-0">Telefon</h3>
                                </div>
                                <p class="mb-0">
                                    <?php if ($contactPhone): ?>
                                        <a href="tel:<?= preg_replace('/\s+/', '', $contactPhone) ?>">
                                            <?= htmlspecialchars($contactPhone) ?>
                                        </a>
                                    <?php else: ?>
                                        <span>Telefon zatím není vyplněn.</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
				<?php if ($successMsg): ?>
					<div class="alert alert-success py-3 px-4 mb-4 shadow-sm border-0">
						<i class="fas fa-check-circle me-2"></i>
						<?= htmlspecialchars($successMsg) ?>
					</div>
				<?php elseif ($errorMsg): ?>
					<div class="alert alert-danger py-3 px-4 mb-4 shadow-sm border-0">
						<i class="fas fa-exclamation-circle me-2"></i>
						<?= htmlspecialchars($errorMsg) ?>
					</div>
				<?php endif; ?>
                <form class="contact-form form-style-2"
                      action="/<?= htmlspecialchars($page['slug'] ?? 'kontakt') ?>#kontakt-form"
                      method="post">
                    <div class="form-row row mb-3">
                        <div class="form-group col-md-6">
                            <input type="text"
                                class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                name="name"
                                maxlength="100"
                                placeholder="Jméno a příjmení"
                                value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                                required>
                            <?php if (!empty($errors['name'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group col-md-6">
                            <input type="email"
                                class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                name="email"
                                maxlength="100"
                                placeholder="E-mail"
                                value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                                required>
                            <?php if (!empty($errors['email'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row row mb-3">
                        <div class="form-group col-md-6">
                            <input type="text"
                                class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                                name="phone"
                                maxlength="50"
                                placeholder="Telefon (nepovinné)"
                                value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
                            <?php if (!empty($errors['phone'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['phone']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group col-md-6">
                            <input type="text"
                                class="form-control <?= isset($errors['subject']) ? 'is-invalid' : '' ?>"
                                name="subject"
                                maxlength="150"
                                placeholder="Předmět"
                                value="<?= htmlspecialchars($old['subject'] ?? '') ?>"
                                required>
                            <?php if (!empty($errors['subject'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['subject']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row row mb-3">
                        <div class="form-group col">
                            <textarea class="form-control <?= isset($errors['message']) ? 'is-invalid' : '' ?>"
                                    name="message"
                                    rows="5"
                                    maxlength="5000"
                                    placeholder="Zpráva"
                                    required><?= htmlspecialchars($old['message'] ?? '') ?></textarea>
                            <?php if (!empty($errors['message'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['message']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- honeypot proti robotům -->
                    <div style="display:none;">
                        <input type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>
                    <?php if ($recaptchaEnabled && $recaptchaSiteKey): ?>
                        <div class="form-row row mb-3">
                            <div class="form-group col">
                                <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($recaptchaSiteKey) ?>"></div>
                                <?php if (!empty($errors['recaptcha'])): ?>
                                    <div class="text-danger small mt-2">
                                        <?= htmlspecialchars($errors['recaptcha']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="form-row row mb-3 mt-2">
                        <div class="col">
                            <button type="submit"
                                    class="btn btn-primary btn-rounded btn-4 font-weight-semibold text-0">
                                Odeslat zprávu
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php if ($recaptchaEnabled && $recaptchaSiteKey): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif;

$content = ob_get_clean();
include __DIR__ . '/layout.php';
