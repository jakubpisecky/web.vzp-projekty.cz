<?php

/*
 * =====================================================
 * Dokumenty – frontend blok
 * =====================================================
 */

$categoryIds = array_values(
    array_filter(
        array_map(
            'intval',
            explode(
                ';',
                $block['document_category_ids'] ?? ''
            )
        )
    )
);

$manualDocumentIds = array_values(
    array_filter(
        array_map(
            'intval',
            explode(
                ';',
                $block['document_ids'] ?? ''
            )
        )
    )
);

$documentsOrder =
    $block['documents_order']
    ?? 'newest';

$documentsLimit =
    (int)($block['documents_limit'] ?? 0);


/*
 * Povolené řazení.
 */
if (!in_array(
    $documentsOrder,
    [
        'newest',
        'oldest',
        'title_asc',
        'title_desc'
    ],
    true
)) {
    $documentsOrder = 'newest';
}


/*
 * =====================================================
 * Ručně vybrané dokumenty
 * =====================================================
 */

$manualDocuments = [];

if ($manualDocumentIds) {

    $placeholders = implode(
        ',',
        array_fill(
            0,
            count($manualDocumentIds),
            '?'
        )
    );

    $types = str_repeat(
        'i',
        count($manualDocumentIds)
    );

    $stmt = $conn->prepare("
        SELECT
            id,
            title,
            filename,
            category_id,
            uploaded_at
        FROM documents
        WHERE id IN ($placeholders)
    ");

    $stmt->bind_param(
        $types,
        ...$manualDocumentIds
    );

    $stmt->execute();

    $res = $stmt->get_result();

    $byId = [];

    while ($row = $res->fetch_assoc()) {
        $byId[(int)$row['id']] = $row;
    }

    $stmt->close();

    /*
     * Zachování ručního pořadí.
     */
    foreach ($manualDocumentIds as $documentId) {

        if (isset($byId[$documentId])) {
            $manualDocuments[] =
                $byId[$documentId];
        }
    }
}


/*
 * =====================================================
 * Dokumenty z kategorií
 * =====================================================
 */

$categoryDocuments = [];

if ($categoryIds) {

    $placeholders = implode(
        ',',
        array_fill(
            0,
            count($categoryIds),
            '?'
        )
    );

    $types = str_repeat(
        'i',
        count($categoryIds)
    );

    switch ($documentsOrder) {

        case 'oldest':
            $orderSql =
                'd.uploaded_at ASC, d.id ASC';
            break;

        case 'title_asc':
            $orderSql =
                'd.title ASC, d.id ASC';
            break;

        case 'title_desc':
            $orderSql =
                'd.title DESC, d.id DESC';
            break;

        case 'newest':
        default:
            $orderSql =
                'd.uploaded_at DESC, d.id DESC';
            break;
    }

    $stmt = $conn->prepare("
        SELECT
            d.id,
            d.title,
            d.filename,
            d.category_id,
            d.uploaded_at
        FROM documents d
        WHERE d.category_id IN ($placeholders)
        ORDER BY {$orderSql}
    ");

    $stmt->bind_param(
        $types,
        ...$categoryIds
    );

    $stmt->execute();

    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $categoryDocuments[] = $row;
    }

    $stmt->close();
}


/*
 * =====================================================
 * Sloučení bez duplicit
 * =====================================================
 *
 * Ručně vybrané dokumenty mají prioritu
 * a zůstávají nahoře v ručním pořadí.
 */

$documents = [];
$usedIds = [];


/*
 * 1. Ručně vybrané
 */
foreach ($manualDocuments as $document) {

    $id = (int)$document['id'];

    if (isset($usedIds[$id])) {
        continue;
    }

    $usedIds[$id] = true;
    $documents[] = $document;
}


/*
 * 2. Kategorie
 */
foreach ($categoryDocuments as $document) {

    $id = (int)$document['id'];

    if (isset($usedIds[$id])) {
        continue;
    }

    $usedIds[$id] = true;
    $documents[] = $document;
}


/*
 * =====================================================
 * Limit
 * =====================================================
 */

if (
    $documentsLimit > 0
    && count($documents) > $documentsLimit
) {
    $documents = array_slice(
        $documents,
        0,
        $documentsLimit
    );
}


/*
 * =====================================================
 * Helper – typ dokumentu
 * =====================================================
 */

function document_type_label(string $filename): string
{
    $ext = strtoupper(
        pathinfo(
            $filename,
            PATHINFO_EXTENSION
        )
    );

    return $ext !== ''
        ? $ext
        : 'SOUBOR';
}


/*
 * =====================================================
 * Render
 * =====================================================
 */
?>

<section class="section <?= e($block['section_class'] ?? '') ?>">

    <div class="container">

        <?php if (!empty($block['subtitle'])): ?>

            <span class="top-sub-title text-color-primary">
                <?= e($block['subtitle']) ?>
            </span>

        <?php endif; ?>


        <?php if (!empty($block['title'])): ?>

            <h2 class="font-weight-bold mb-4">
                <?= e($block['title']) ?>
            </h2>

        <?php endif; ?>


        <?php if (!empty($block['content'])): ?>

            <div class="content mb-4">
                <?= $block['content'] ?>
            </div>

        <?php endif; ?>


        <?php if (!$documents): ?>

    <p class="text-muted">
        Nejsou k dispozici žádné dokumenty.
    </p>

<?php else: ?>

    <div class="list-group list-group-flush border-top">

        <?php foreach ($documents as $document): ?>

            <?php
            $title = trim(
                (string)($document['title'] ?? '')
            );

            if ($title === '') {
                $title = $document['filename'] ?? 'Dokument';
            }

            $filename = $document['filename'] ?? '';

            $url =
                '/uploads/documents/'
                . rawurlencode($filename);

            $type = document_type_label($filename);

            $icon = 'fa-file';

            switch (strtolower($type)) {

                case 'pdf':
                    $icon = 'fa-file-pdf';
                    break;

                case 'doc':
                case 'docx':
                    $icon = 'fa-file-word';
                    break;

                case 'xls':
                case 'xlsx':
                    $icon = 'fa-file-excel';
                    break;

                case 'ppt':
                case 'pptx':
                    $icon = 'fa-file-powerpoint';
                    break;

                case 'zip':
                case 'rar':
                    $icon = 'fa-file-archive';
                    break;
            }
            ?>

            <a
                href="<?= e($url) ?>"
                target="_blank"
                rel="noopener"
                class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-3 px-4"
            >

                <div class="d-flex align-items-center">

                    <div class="me-3 fs-4 text-color-primary">

                        <i class="fas <?= e($icon) ?>"></i>

                    </div>

                    <div>

                        <div class="font-weight-semibold">
                            <?= e($title) ?>
                        </div>

                        <div class="small text-muted">
                            <?= e($filename) ?>
                        </div>

                    </div>

                </div>

                <div class="d-flex align-items-center ms-3">

                    <span class="badge bg-light text-dark border me-3">
                        <?= e($type) ?>
                    </span>

                    <span class="text-color-primary">
                        <i class="fas fa-download"></i>
                    </span>

                </div>

            </a>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

    </div>

</section>