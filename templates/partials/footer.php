<?php
// Získáme slug sekce článků
$articlesBaseSlug = null;
$resArticlesPage = $conn->query("
    SELECT slug
    FROM pages
    WHERE status='published'
      AND (template='articles' OR template LIKE 'articles|%')
    ORDER BY id ASC
    LIMIT 1
");
if ($resArticlesPage && $row = $resArticlesPage->fetch_assoc()) {
    $articlesBaseSlug = trim($row['slug'], '/');
}

// Posledních 5 článků
$recentPosts = [];
$stmt = $conn->prepare("
    SELECT id, title, slug, publish_date
    FROM articles
    WHERE status='published'
    ORDER BY publish_date DESC, id DESC
    LIMIT 3
");
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $recentPosts[] = $row;
}
$stmt->close();

// Nastavení z adminu
$site     = setting('site_title', 'Web');
$tagline  = setting('site_tagline', '');
$logo     = setting('site_logo_url', '');
$logo     = $logo ? media_url($logo) : '';
$email    = trim((string) setting('contact_email', ''));
$phone    = trim((string) setting('contact_phone', ''));
$address  = setting('contact_address', setting('contact_address_html', '')); // podporuješ-li oba klíče
$socials  = settings_social_links(); // očekává se pole odkazů (typicky type + url)
$year     = (int)date('Y');
?>

<footer id="footer" class="footer footer-hover-links-light mt-0">
	<div class="container container-lg-custom">

		<div class="row">
			<div class="col-lg-2 align-self-center text-center mb-5 mb-lg-0">
				<a href="/" class="logo">
					<?php if ($logo): ?>
						<img src="<?= htmlspecialchars($logo) ?>" class="img-fluid mb-lg-5" width="92" height="35" alt="<?= htmlspecialchars($site) ?>">
					<?php else: ?>
						<span class="d-inline-block text-color-light font-weight-bold mb-lg-5">
							<?= htmlspecialchars($site) ?>
						</span>
					<?php endif; ?>
				</a>
				<?php if ($tagline): ?>
					<p class="text-color-light small mt-2 mb-0"><?= htmlspecialchars($tagline) ?></p>
				<?php endif; ?>
			</div>

			<div class="col-lg-3 text-center text-lg-start mb-5 mb-lg-0">
				<h4 class="font-weight-bold text-4-5 pb-1 mb-3">Kontaktujte nás</h4>
				<ul class="list list-unstyled">
					<li class="text-color-light pb-1 mb-2">
						<span class="d-block font-weight-semibold line-height-1 text-color-grey">ADRESA</span>
						<?php if ($address): ?>
							<?= $address /* HTML z adminu, nescapeovat */ ?>
						<?php else: ?>
							1234 Street Name, City, State, USA
						<?php endif; ?>
					</li>

					<li class="text-color-light pb-1 mb-2">
						<span class="d-block font-weight-semibold line-height-1 text-color-grey">TELEFON</span>
						<?php if ($phone): ?>
							<a href="tel:<?= preg_replace('/\s+/', '', $phone) ?>" class="link-color-light">
								<?= htmlspecialchars($phone) ?>
							</a>
						<?php else: ?>
							<a href="tel:+1234567890" class="link-color-light">Toll Free (123) 456-7890</a>
						<?php endif; ?>
					</li>

					<li class="text-color-light pb-1 mb-2">
						<span class="d-block font-weight-semibold line-height-1 text-color-grey">E-MAIL</span>
						<?php if ($email): ?>
							<a href="mailto:<?= htmlspecialchars($email) ?>" class="link-color-light">
								<?= htmlspecialchars($email) ?>
							</a>
						<?php else: ?>
							<a href="mailto:mail@example.com" class="link-color-light">mail@example.com</a>
						<?php endif; ?>
					</li>
				</ul>

				<ul class="social-icons social-icons-icon-dark social-icons-lg">
					<?php if (!empty($socials)): ?>
						<?php foreach ($socials as $social): ?>
							<?php
								$type = strtolower($social['type'] ?? 'link');
								$url  = $social['url'] ?? '#';
							?>
							<li class="social-icons-<?= htmlspecialchars($type) ?>">
								<a href="<?= htmlspecialchars($url) ?>" target="_blank" title="<?= ucfirst($type) ?>">
									<i class="fab fa-<?= htmlspecialchars($type) ?>"></i>
								</a>
							</li>
						<?php endforeach; ?>
					<?php else: ?>
						<li class="social-icons-instagram"><a href="http://www.instagram.com/" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a></li>
						<li class="social-icons-twitter mx-2"><a href="http://www.twitter.com/" target="_blank" title="Twitter"><i class="fab fa-twitter"></i></a></li>
						<li class="social-icons-facebook"><a href="http://www.facebook.com/" target="_blank" title="Facebook"><i class="fab fa-facebook-f"></i></a></li>
					<?php endif; ?>
				</ul>
			</div>

				<?php
				// Rychlé odkazy = root stránky bez children
				$quickLinks = [];
				foreach ($menu as $item) {
					if (empty($item['children'])) {
						$slug = trim($item['slug'], '/');
						$url = ($homeSlug && $slug === $homeSlug) ? '/' : '/' . $slug;

						$quickLinks[] = [
							'title' => $item['title'],
							'url'   => $url,
						];
					}
				}
				?>

				<div class="col-lg-3 text-center text-lg-start mb-5 mb-lg-0">
					<h4 class="font-weight-bold text-4-5 pb-1 mb-3">Rychlé odkazy</h4>
					<ul class="list list-unstyled mb-0">
						<?php foreach ($quickLinks as $link): ?>
							<li>
								<a href="<?= htmlspecialchars($link['url']) ?>">
									<?= htmlspecialchars($link['title']) ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>

			


				<div class="col-lg-3 text-center text-lg-start">
					<h4 class="font-weight-bold text-4-5 pb-1 mb-3">Aktuální články</h4>
					<div class="recent-posts">
						<ul class="list list-unstyled d-flex flex-column align-items-center align-items-lg-start">
							<?php if ($recentPosts): ?>
								<?php foreach ($recentPosts as $post): ?>
									<?php
									$slug = trim($post['slug'], '/');
									$url = ($articlesBaseSlug)
										? '/' . $articlesBaseSlug . '/' . $slug
										: '/' . $slug;

									$date = $post['publish_date']
										? date('j. n. Y', strtotime($post['publish_date']))
										: '';
									?>
									<li>
										<a href="<?= htmlspecialchars($url) ?>">
											<?= htmlspecialchars($post['title']) ?>
										</a>
										<?php if ($date): ?>
											<span class="text-muted small d-block"><?= $date ?></span>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							<?php else: ?>
								<li class="text-muted small">Žádné články</li>
							<?php endif; ?>
						</ul>
					</div>
				</div>

		</div>

	</div>

	<div class="footer-copyrig ht footer-copyright-container-border-top footer-copyright-container-border-top-opacity">
		<div class="container">
			<div class="row text-center">
				<div class="col">
					<p>
						<?= htmlspecialchars($site) ?>
						<?php if ($tagline): ?> – <?= htmlspecialchars($tagline) ?><?php endif; ?>.
						&copy; <?= $year ?>. All Rights Reserved.
					</p>
				</div>
			</div>
		</div>
	</div>
</footer>
