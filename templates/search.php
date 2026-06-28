<?php
// /templates/search.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $conn;

// základní nastavení pro EZY
$assets = ['base' => '/public/themes/ezy'];

// dotaz z query stringu
$searchQuery = trim($_GET['s'] ?? '');
$searchQueryDisplay = $searchQuery;

$resultsPages    = [];
$resultsArticles = [];

if ($searchQuery !== '' && $conn instanceof mysqli) {
    $like = '%' . $searchQuery . '%';

    // --- 1) Stránky (pages) ---
    $stmt = $conn->prepare("
        SELECT id, title, slug, meta_description, content
        FROM pages
        WHERE status = 'published'
          AND (title LIKE ? OR content LIKE ?)
        ORDER BY id DESC
        LIMIT 50
    ");
    if ($stmt) {
        $stmt->bind_param("ss", $like, $like);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $resultsPages[] = $row;
        }
        $stmt->close();
    }

    // --- 2) Články (articles) ---
    // Nejprve zjistíme slug stránky, která má šablonu "articles" (sekce článků)
    $articlesBaseSlug = null;
    $resArticlesPage = $conn->query("
        SELECT slug
        FROM pages
        WHERE status = 'published'
          AND (template = 'articles' OR template LIKE 'articles|%')
        ORDER BY id ASC
        LIMIT 1
    ");
    if ($resArticlesPage && $rowAp = $resArticlesPage->fetch_assoc()) {
        $articlesBaseSlug = trim($rowAp['slug'] ?? '', '/');
    }

    // Teď načteme samotné články
    $stmt = $conn->prepare("
        SELECT id, title, slug, content, publish_date
        FROM articles
        WHERE status = 'published'
          AND (title LIKE ? OR content LIKE ?)
        ORDER BY publish_date DESC, id DESC
        LIMIT 50
    ");
    if ($stmt) {
        $stmt->bind_param("ss", $like, $like);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            // přidáme base slug k výsledku, aby se dala složit URL
            $row['articles_base_slug'] = $articlesBaseSlug;
            $resultsArticles[] = $row;
        }
        $stmt->close();
    }
}

function search_snippet(?string $html, string $term, int $maxLen = 220): string {
    // odstraníme HTML značky
    $text = strip_tags((string)$html);

    // dekódujeme HTML entity → normální text
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $text = trim($text);
    if ($text === '') return '';

    $term = trim($term);
    if ($term === '') {
        return mb_substr($text, 0, $maxLen) . (mb_strlen($text) > $maxLen ? '…' : '');
    }

    $pos = mb_stripos($text, $term);
    if ($pos === false) {
        return mb_substr($text, 0, $maxLen) . (mb_strlen($text) > $maxLen ? '…' : '');
    }

    $start = max(0, $pos - 60);
    $snippet = mb_substr($text, $start, $maxLen);

    if ($start > 0) $snippet = '…' . $snippet;
    if ($start + $maxLen < mb_strlen($text)) $snippet .= '…';

    return $snippet;
}


ob_start();
?>

  <section class="page-header">
					<div class="container">
						<div class="row align-items-center">
							<div class="col-md-12 text-start">
								<h1 class="font-weight-bold">Výsledky hledání</h1>

							</div>
						</div>
					</div>
				</section>

<div class="container my-5">
    <div class="row mb-4">
    <div class="col-lg-6">
        <form method="get" action="/hledat" class="contact-form form-style-2">
            <div class="form-row row mb-0">
                <div class="form-group col-8 col-md-9">
                    <input type="text"
                           name="s"
                           class="form-control"
                           placeholder="Hledám…"
                           value="<?= htmlspecialchars($searchQueryDisplay) ?>">
                </div>
                <div class="form-group col-4 col-md-3 d-grid">
                    <button type="submit"
                            class="btn btn-primary btn-rounded btn-4 font-weight-semibold w-100">
                        Vyhledat
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


    <?php if ($searchQuery === ''): ?>
        <div class="alert alert-info">
            Zadejte prosím vyhledávaný výraz.
        </div>
    <?php else: ?>
        <?php
            $countPages    = count($resultsPages);
            $countArticles = count($resultsArticles);
            $totalCount    = $countPages + $countArticles;
        ?>

        <?php if ($totalCount === 0): ?>
            <div class="alert alert-warning">
                Pro dotaz <strong><?= htmlspecialchars($searchQueryDisplay) ?></strong> nebyly nalezeny žádné výsledky.
            </div>
        <?php else: ?>
            <p class="text-muted mb-4">
                Nalezeno výsledků: <strong><?= (int)$totalCount ?></strong>
            </p>

            <?php if ($countPages): ?>
                <h2 class="h4 mb-3">Stránky</h2>
                <div class="list-group mb-4">
                    <?php foreach ($resultsPages as $p): ?>
                        <?php
                        $url = '/' . trim($p['slug'] ?? '', '/');
                        $snippet = search_snippet($p['meta_description'] ?: $p['content'], $searchQuery);
                        ?>
                        <a href="<?= htmlspecialchars($url) ?>" class="list-group-item list-group-item-action">
                            <h3 class="h5 mb-1"><?= htmlspecialchars($p['title']) ?></h3>
                            <?php if ($snippet): ?>
                                <p class="mb-1 text-muted"><?= htmlspecialchars($snippet) ?></p>
                            <?php endif; ?>
                            <small class="text-secondary"><?= htmlspecialchars($url) ?></small>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($countArticles): ?>
                <h2 class="h4 mb-3">Články</h2>
                <div class="list-group">
                    <?php foreach ($resultsArticles as $a): ?>
                        <?php
                        $articleSlug      = trim($a['slug'] ?? '', '/');
                        $articlesBaseSlug = trim($a['articles_base_slug'] ?? '', '/');

                        if ($articlesBaseSlug !== '') {
                            // standardní varianta: /{sekce článků}/{slug článku}
                            $url = '/' . $articlesBaseSlug . '/' . $articleSlug;
                        } else {
                            // fallback, kdybychom nenašli stránku se šablonou "articles"
                            $url = '/' . $articleSlug;
                        }

                        $snippet = search_snippet($a['content'], $searchQuery);
                        ?>
                        <a href="<?= htmlspecialchars($url) ?>" class="list-group-item list-group-item-action">
                            <h3 class="h5 mb-1"><?= htmlspecialchars($a['title']) ?></h3>
                            <?php if ($snippet): ?>
                                <p class="mb-1 text-muted"><?= htmlspecialchars($snippet) ?></p>
                            <?php endif; ?>
                            <small class="text-secondary">
                                <?= htmlspecialchars($url) ?>
                                <?php if (!empty($a['publish_date'])): ?>
                                    • <?= htmlspecialchars(date('j.n.Y', strtotime($a['publish_date']))) ?>
                                <?php endif; ?>
                            </small>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
