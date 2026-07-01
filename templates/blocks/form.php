<style>
.rw-form .form-control,
.rw-form .form-select {
    border: 2px solid #ced4da !important;
    background-color: #fff !important;
    border-radius: 6px !important;
    min-height: 44px;
}

.rw-form textarea.form-control {
    min-height: 180px;
}

.rw-form .form-control:focus,
.rw-form .form-select:focus {
    border-color: #0d6efd !important;
    box-shadow: 0 0 0 .2rem rgba(13,110,253,.15) !important;
}
</style>
<?php
// /templates/blocks/form.php

$formId = (int)($block['form_id'] ?? 0);

if ($formId <= 0) {
    return;
}

$stmt = $conn->prepare("
    SELECT *
    FROM forms
    WHERE id = ?
      AND is_active = 1
    LIMIT 1
");
$stmt->bind_param("i", $formId);
$stmt->execute();
$form = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$form) {
    return;
}

$stmt = $conn->prepare("
    SELECT *
    FROM form_fields
    WHERE form_id = ?
      AND is_active = 1
    ORDER BY sort_order ASC, id ASC
");
$stmt->bind_param("i", $formId);
$stmt->execute();
$fieldsRes = $stmt->get_result();

$fields = [];
while ($row = $fieldsRes->fetch_assoc()) {
    $fields[] = $row;
}
$stmt->close();

$success = false;
$errors = [];
$postedValues = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && (int)($_POST['rw_form_id'] ?? 0) === $formId
) {
    $postedValues = $_POST['fields'] ?? [];

    if (!is_array($postedValues)) {
        $postedValues = [];
    }

    // Honeypot
    if (!empty($_POST['website'] ?? '')) {
        $errors[] = 'Formulář se nepodařilo odeslat.';
    }

    foreach ($fields as $field) {
        $name = $field['name'];
        $label = $field['label'];
        $type = $field['type'];
        $required = (int)$field['is_required'] === 1;

        $value = trim((string)($postedValues[$name] ?? ''));

        if ($type === 'checkbox') {
            $value = isset($postedValues[$name]) ? '1' : '';
        }

        if ($required && $value === '') {
            $errors[] = 'Vyplňte pole: ' . $label;
        }

        if ($type === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail není ve správném formátu.';
        }
    }

    if (!$errors) {
        $submissionId = 0;

        if ((int)$form['save_submissions'] === 1) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $referer = $_SERVER['HTTP_REFERER'] ?? '';

            $stmt = $conn->prepare("
                INSERT INTO form_submissions
                    (form_id, ip_address, user_agent, referer)
                VALUES
                    (?, ?, ?, ?)
            ");
            $stmt->bind_param("isss", $formId, $ip, $ua, $referer);
            $stmt->execute();
            $submissionId = (int)$stmt->insert_id;
            $stmt->close();

            if ($submissionId > 0) {
                $stmt = $conn->prepare("
                    INSERT INTO form_submission_values
                        (submission_id, field_id, field_label, field_name, field_value)
                    VALUES
                        (?, ?, ?, ?, ?)
                ");

                foreach ($fields as $field) {
                    $fieldId = (int)$field['id'];
                    $fieldName = $field['name'];
                    $fieldLabel = $field['label'];
                    $fieldValue = trim((string)($postedValues[$fieldName] ?? ''));

                    if ($field['type'] === 'checkbox') {
                        $fieldValue = isset($postedValues[$fieldName]) ? 'Ano' : 'Ne';
                    }

                    $stmt->bind_param(
                        "iisss",
                        $submissionId,
                        $fieldId,
                        $fieldLabel,
                        $fieldName,
                        $fieldValue
                    );
                    $stmt->execute();
                }

                $stmt->close();
            }
        }

if ((int)$form['send_email'] === 1 && !empty($form['recipient_email'])) {
    $subject = $form['email_subject'] ?: 'Nová zpráva z formuláře ' . $form['name'];

    $body = '<h2>Nová zpráva z formuláře: ' . e($form['name']) . '</h2>';
    $body .= '<table cellpadding="8" cellspacing="0" border="1" style="border-collapse:collapse;width:100%;">';

    foreach ($fields as $field) {
        $fieldName = $field['name'];
        $fieldValue = trim((string)($postedValues[$fieldName] ?? ''));

        if ($field['type'] === 'checkbox') {
            $fieldValue = isset($postedValues[$fieldName]) ? 'Ano' : 'Ne';
        }

        $body .= '<tr>';
        $body .= '<th align="left" style="width:220px;">' . e($field['label']) . '</th>';
        $body .= '<td>' . nl2br(e($fieldValue)) . '</td>';
        $body .= '</tr>';
    }

    $body .= '</table>';

    try {
        $mail = mailer_from_settings();

        if ($mail) {
            $mail->isHTML(true);
            $mail->addAddress($form['recipient_email']);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags(str_replace(['</tr>', '</p>', '<br>', '<br/>', '<br />'], "\n", $body));

            foreach ($fields as $field) {
                if ($field['type'] === 'email') {
                    $replyEmail = trim((string)($postedValues[$field['name']] ?? ''));

                    if ($replyEmail !== '' && filter_var($replyEmail, FILTER_VALIDATE_EMAIL)) {
                        $mail->addReplyTo($replyEmail);
                        break;
                    }
                }
            }

            $mail->send();
        }
    } catch (Throwable $e) {
        error_log('Form mail error: ' . $e->getMessage());
    }
}

        $success = true;
        $postedValues = [];
    }
}

function rw_form_col_class($width): string
{
    $width = (string)$width;

    return match ($width) {
        '25' => 'col-md-3',
        '33' => 'col-md-4',
        '50' => 'col-md-6',
        '66' => 'col-md-8',
        '75' => 'col-md-9',
        default => 'col-12',
    };
}
?>

<section id="form-<?= (int)$block['id'] ?>" class="section <?= e($block['section_class'] ?? '') ?>">
    <div class="container">

        <?php if (!empty($block['title'])): ?>
            <h2 class="font-weight-bold mb-4"><?= e($block['title']) ?></h2>
        <?php endif; ?>

        <?php if ($success): ?>

            <div class="alert alert-success">
                <?= nl2br(e($form['success_message'] ?: 'Děkujeme, formulář byl úspěšně odeslán.')) ?>
            </div>

        <?php else: ?>

            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><?= e($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post"
      action="#form-<?= (int)$block['id'] ?>"
      class="rw-form">
                <input type="hidden" name="rw_form_id" value="<?= (int)$formId ?>">

                <div style="display:none;">
                    <label>Website</label>
                    <input type="text" name="website" value="">
                </div>

                <div class="row g-3">
                    <?php foreach ($fields as $field): ?>
                        <?php
                        $name = $field['name'];
                        $type = $field['type'];
                        $value = $postedValues[$name] ?? '';
                        $required = (int)$field['is_required'] === 1;
                        $colClass = rw_form_col_class($field['width'] ?? '100');
                        ?>

                        <div class="<?= e($colClass) ?>">

                            <?php if ($type === 'checkbox'): ?>

                                <div class="form-check mt-4">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="fields[<?= e($name) ?>]"
                                           id="field_<?= e($name) ?>"
                                           value="1"
                                           <?= !empty($value) ? 'checked' : '' ?>
                                           <?= $required ? 'required' : '' ?>>

                                    <label class="form-check-label" for="field_<?= e($name) ?>">
                                        <?= e($field['label']) ?>
                                        <?= $required ? '<span class="text-danger">*</span>' : '' ?>
                                    </label>
                                </div>

                            <?php else: ?>

                                <label class="form-label" for="field_<?= e($name) ?>">
                                    <?= e($field['label']) ?>
                                    <?= $required ? '<span class="text-danger">*</span>' : '' ?>
                                </label>

                                <?php if ($type === 'textarea'): ?>

                                    <textarea name="fields[<?= e($name) ?>]"
                                              id="field_<?= e($name) ?>"
                                              class="form-control"
                                              rows="5"
                                              placeholder="<?= e($field['placeholder'] ?? '') ?>"
                                              <?= $required ? 'required' : '' ?>><?= e($value) ?></textarea>

                                <?php elseif ($type === 'select'): ?>

                                    <select name="fields[<?= e($name) ?>]"
                                            id="field_<?= e($name) ?>"
                                            class="form-select"
                                            <?= $required ? 'required' : '' ?>>

                                        <option value="">— Vyberte —</option>

                                        <?php
                                        $options = preg_split('/\r\n|\r|\n/', (string)($field['options'] ?? ''));
                                        foreach ($options as $option):
                                            $option = trim($option);
                                            if ($option === '') continue;
                                        ?>
                                            <option value="<?= e($option) ?>" <?= $value === $option ? 'selected' : '' ?>>
                                                <?= e($option) ?>
                                            </option>
                                        <?php endforeach; ?>

                                    </select>

                                <?php else: ?>

                                    <?php
                                    $inputType = 'text';

                                    if ($type === 'email') {
                                        $inputType = 'email';
                                    } elseif ($type === 'phone') {
                                        $inputType = 'tel';
                                    }
                                    ?>

                                    <input type="<?= e($inputType) ?>"
                                           name="fields[<?= e($name) ?>]"
                                           id="field_<?= e($name) ?>"
                                           class="form-control"
                                           value="<?= e($value) ?>"
                                           placeholder="<?= e($field['placeholder'] ?? '') ?>"
                                           <?= $required ? 'required' : '' ?>>

                                <?php endif; ?>

                                <?php if (!empty($field['help_text'])): ?>
                                    <div class="form-text">
                                        <?= e($field['help_text']) ?>
                                    </div>
                                <?php endif; ?>

                            <?php endif; ?>

                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        Odeslat
                    </button>
                </div>
            </form>

        <?php endif; ?>

    </div>
</section>