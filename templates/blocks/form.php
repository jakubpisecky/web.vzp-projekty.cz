<?php
// /templates/blocks/form.php

$formId  = (int)($block['form_id'] ?? 0);
$blockId = (int)($block['id'] ?? 0);

if ($formId <= 0 || $blockId <= 0) {
    return;
}

/*
 * Formulář
 */
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

/*
 * Pole formuláře
 */
$stmt = $conn->prepare("
    SELECT *
    FROM form_fields
    WHERE form_id = ?
      AND is_active = 1
    ORDER BY sort_order ASC, id ASC
");
$stmt->bind_param("i", $formId);
$stmt->execute();
$fieldsResult = $stmt->get_result();

$fields = [];

while ($row = $fieldsResult->fetch_assoc()) {
    $fields[] = $row;
}

$stmt->close();

/*
 * Pomocné funkce jako closures, aby nedošlo ke kolizi,
 * pokud bude na jedné stránce více formulářů.
 */
$getFieldOptions = static function (array $field): array {
    $options = preg_split(
        '/\r\n|\r|\n/',
        (string)($field['options'] ?? '')
    );

    if (!is_array($options)) {
        return [];
    }

    $options = array_map('trim', $options);

    return array_values(array_filter(
        $options,
        static fn($option) => $option !== ''
    ));
};

$getColumnClass = static function ($width): string {
    return match ((string)$width) {
        '25' => 'col-md-3',
        '33' => 'col-md-4',
        '50' => 'col-md-6',
        '66' => 'col-md-8',
        '75' => 'col-md-9',
        default => 'col-12',
    };
};

$getPostedValue = static function (
    array $field,
    array $postedValues
) {
    $name = (string)($field['name'] ?? '');
    $type = (string)($field['type'] ?? 'text');

    if ($type === 'checkbox_group') {
        $value = $postedValues[$name] ?? [];

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            array_map(
                static fn($item) => trim((string)$item),
                $value
            ),
            static fn($item) => $item !== ''
        ));
    }

    if ($type === 'checkbox') {
        return isset($postedValues[$name]) ? '1' : '';
    }

    return trim((string)($postedValues[$name] ?? ''));
};

$getDisplayValue = static function (
    array $field,
    array $postedValues
) use ($getPostedValue): string {
    $type = (string)($field['type'] ?? 'text');
    $value = $getPostedValue($field, $postedValues);

    if ($type === 'checkbox') {
        return $value === '1' ? 'Ano' : 'Ne';
    }

    if ($type === 'checkbox_group') {
        return is_array($value)
            ? implode(', ', $value)
            : '';
    }

    return (string)$value;
};

$success = false;
$errors = [];
$postedValues = [];

/*
 * Zpracujeme pouze formulář z tohoto konkrétního bloku.
 */
$isSubmittedForm =
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && (int)($_POST['rw_form_id'] ?? 0) === $formId
    && (int)($_POST['rw_form_block_id'] ?? 0) === $blockId;

if ($isSubmittedForm) {
    $postedValues = $_POST['fields'] ?? [];

    if (!is_array($postedValues)) {
        $postedValues = [];
    }

    /*
     * Honeypot proti robotům.
     */
    if (!empty($_POST['website'] ?? '')) {
        $errors[] = 'Formulář se nepodařilo odeslat.';
    }
    // reCAPTCHA
    if ((int)$form['use_recaptcha'] === 1) {

        if (!verifyRecaptcha($_POST['g-recaptcha-response'] ?? '')) {
            $errors[] = 'Potvrďte prosím, že nejste robot.';
        }

    }

    /*
     * Serverová validace.
     */
    foreach ($fields as $field) {
        $name = (string)($field['name'] ?? '');
        $label = (string)($field['label'] ?? '');
        $type = (string)($field['type'] ?? 'text');
        $required = (int)($field['is_required'] ?? 0) === 1;

        /*
         * Nadpis a informační text nejsou vstupní pole.
         */
        if (in_array($type, ['heading', 'html'], true)) {
            continue;
        }

        $value = $getPostedValue($field, $postedValues);

        if ($type === 'checkbox_group') {
            $isEmpty = !is_array($value) || count($value) === 0;
        } else {
            $isEmpty = trim((string)$value) === '';
        }

        if ($required && $isEmpty) {
            $errors[] = 'Vyplňte pole: ' . $label;
            continue;
        }

        if ($isEmpty) {
            continue;
        }

        if (
            $type === 'email'
            && !filter_var((string)$value, FILTER_VALIDATE_EMAIL)
        ) {
            $errors[] = 'Pole „' . $label . '“ neobsahuje platný e-mail.';
        }

        if (
            $type === 'url'
            && !filter_var((string)$value, FILTER_VALIDATE_URL)
        ) {
            $errors[] = 'Pole „' . $label . '“ neobsahuje platnou webovou adresu.';
        }

        if (
            $type === 'number'
            && !is_numeric((string)$value)
        ) {
            $errors[] = 'Pole „' . $label . '“ musí obsahovat číslo.';
        }

        /*
         * Kontrola, že select/radio obsahuje povolenou hodnotu.
         */
        if (in_array($type, ['select', 'radio'], true)) {
            $allowedOptions = $getFieldOptions($field);

            if (
                !empty($allowedOptions)
                && !in_array((string)$value, $allowedOptions, true)
            ) {
                $errors[] = 'Pole „' . $label . '“ obsahuje neplatnou hodnotu.';
            }
        }

        /*
         * Kontrola hodnot skupiny checkboxů.
         */
        if ($type === 'checkbox_group' && is_array($value)) {
            $allowedOptions = $getFieldOptions($field);

            foreach ($value as $selectedOption) {
                if (!in_array($selectedOption, $allowedOptions, true)) {
                    $errors[] = 'Pole „' . $label . '“ obsahuje neplatnou hodnotu.';
                    break;
                }
            }
        }
    }

    if (!$errors) {
        $submissionId = 0;

        /*
         * Uložení zprávy do DB.
         */
        if ((int)$form['save_submissions'] === 1) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $referer = $_SERVER['HTTP_REFERER'] ?? '';

            $stmt = $conn->prepare("
                INSERT INTO form_submissions
                    (form_id, ip_address, user_agent, referer)
                VALUES
                    (?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "isss",
                $formId,
                $ip,
                $userAgent,
                $referer
            );
            $stmt->execute();

            $submissionId = (int)$stmt->insert_id;

            $stmt->close();

            if ($submissionId > 0) {
                $stmt = $conn->prepare("
                    INSERT INTO form_submission_values
                        (
                            submission_id,
                            field_id,
                            field_label,
                            field_name,
                            field_value
                        )
                    VALUES
                        (?, ?, ?, ?, ?)
                ");

                foreach ($fields as $field) {
                    $type = (string)($field['type'] ?? 'text');

                    /*
                     * Nadpisy a informační texty se neukládají.
                     */
                    if (in_array($type, ['heading', 'html'], true)) {
                        continue;
                    }

                    $fieldId = (int)$field['id'];
                    $fieldName = (string)$field['name'];
                    $fieldLabel = (string)$field['label'];
                    $fieldValue = $getDisplayValue(
                        $field,
                        $postedValues
                    );

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

        /*
         * Odeslání e-mailu přes SMTP nastavení CMS.
         */
        if (
            (int)$form['send_email'] === 1
            && !empty($form['recipient_email'])
        ) {
            $subject = $form['email_subject']
                ?: 'Nová zpráva z formuláře ' . $form['name'];

            $body = '<h2>Nová zpráva z formuláře: '
                . e($form['name'])
                . '</h2>';

            $body .= '
                <table
                    cellpadding="8"
                    cellspacing="0"
                    border="1"
                    style="border-collapse:collapse;width:100%;">
            ';

            foreach ($fields as $field) {
                $type = (string)($field['type'] ?? 'text');

                if (in_array($type, ['heading', 'html'], true)) {
                    continue;
                }

                $fieldValue = $getDisplayValue(
                    $field,
                    $postedValues
                );

                $body .= '<tr>';
                $body .= '
                    <th
                        align="left"
                        style="width:220px;vertical-align:top;">
                        ' . e($field['label']) . '
                    </th>
                ';
                $body .= '<td>' . nl2br(e($fieldValue)) . '</td>';
                $body .= '</tr>';
            }

            $body .= '</table>';

            try {
                $mail = mailer_from_settings();

                if (!$mail) {
                    throw new RuntimeException(
                        'Nepodařilo se vytvořit e-mailového klienta.'
                    );
                }

                $mail->isHTML(true);
                $mail->addAddress($form['recipient_email']);
                $mail->Subject = $subject;
                $mail->Body = $body;

                $mail->AltBody = strip_tags(
                    str_replace(
                        [
                            '</tr>',
                            '</p>',
                            '<br>',
                            '<br/>',
                            '<br />',
                        ],
                        "\n",
                        $body
                    )
                );

                /*
                 * První e-mailové pole použijeme jako Reply-To.
                 */
                foreach ($fields as $field) {
                    if (($field['type'] ?? '') !== 'email') {
                        continue;
                    }

                    $replyEmail = trim((string)(
                        $postedValues[$field['name']] ?? ''
                    ));

                    if (
                        $replyEmail !== ''
                        && filter_var(
                            $replyEmail,
                            FILTER_VALIDATE_EMAIL
                        )
                    ) {
                        $mail->addReplyTo($replyEmail);
                        break;
                    }
                }

                $mail->send();

            } catch (Throwable $e) {
                error_log(
                    'Form mail error: ' . $e->getMessage()
                );
            }
        }
/*
 * Potvrzovací e-mail návštěvníkovi.
 */
if ((int)($form['send_confirmation'] ?? 0) === 1) {

    $visitorEmail = '';

    foreach ($fields as $field) {
        if (($field['type'] ?? '') !== 'email') {
            continue;
        }

        $fieldName = (string)($field['name'] ?? '');

        if ($fieldName === '') {
            continue;
        }

        $candidate = trim((string)($postedValues[$fieldName] ?? ''));

        if (
            $candidate !== ''
            && filter_var($candidate, FILTER_VALIDATE_EMAIL)
        ) {
            $visitorEmail = $candidate;
            break;
        }
    }

    if ($visitorEmail !== '') {
        try {
            $confirmationMail = mailer_from_settings();

            if (!$confirmationMail) {
                throw new RuntimeException(
                    'Nepodařilo se vytvořit e-mailového klienta.'
                );
            }

            $confirmationSubject = trim(
                (string)($form['confirmation_subject'] ?? '')
            );

            if ($confirmationSubject === '') {
                $confirmationSubject = 'Děkujeme za vaši zprávu';
            }

            $confirmationText = trim(
                (string)($form['confirmation_message'] ?? '')
            );

            if ($confirmationText === '') {
                $confirmationText =
                    "Dobrý den,\n\n"
                    . "děkujeme za odeslání formuláře. "
                    . "Vaši zprávu jsme přijali.";
            }

            $confirmationMail->isHTML(true);
            $confirmationMail->addAddress($visitorEmail);
            $confirmationMail->Subject = $confirmationSubject;
            $confirmationMail->Body = nl2br(e($confirmationText));
            $confirmationMail->AltBody = $confirmationText;

            $confirmationMail->send();

        } catch (Throwable $e) {
            error_log(
                'Form confirmation mail error: ' . $e->getMessage()
            );
        }
    } else {
        error_log(
            'Form confirmation mail: nebylo nalezeno vyplněné pole typu email.'
        );
    }
}
        $success = true;
        $postedValues = [];
    }
    
}
?>

<?php if (!defined('RW_FORM_STYLES_LOADED')): ?>
    <?php define('RW_FORM_STYLES_LOADED', true); ?>

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
            box-shadow: 0 0 0 .2rem rgba(13, 110, 253, .15) !important;
        }

        .rw-form-heading {
            margin-top: .5rem;
            margin-bottom: .25rem;
        }

        .rw-form-info {
            padding: 1rem;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            background: rgba(0, 0, 0, .025);
        }

        .rw-form .form-check-group .form-check {
            margin-bottom: .5rem;
        }
    </style>
<?php endif; ?>

<section
    id="form-<?= $blockId ?>"
    class="section <?= e($block['section_class'] ?? '') ?>">

    <div class="container">

        <?php if (!empty($block['title'])): ?>
            <h2 class="font-weight-bold mb-4">
                <?= e($block['title']) ?>
            </h2>
        <?php endif; ?>

        <?php if ($success): ?>

            <div class="alert alert-success">
                <?= nl2br(e(
                    $form['success_message']
                    ?: 'Děkujeme, formulář byl úspěšně odeslán.'
                )) ?>
            </div>

        <?php else: ?>

            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><?= e($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form
                method="post"
                action="#form-<?= $blockId ?>"
                class="rw-form">

                <input
                    type="hidden"
                    name="rw_form_id"
                    value="<?= $formId ?>">

                <input
                    type="hidden"
                    name="rw_form_block_id"
                    value="<?= $blockId ?>">

                <div
                    style="
                        position:absolute;
                        left:-9999px;
                        width:1px;
                        height:1px;
                        overflow:hidden;
                    "
                    aria-hidden="true">

                    <label for="website_<?= $blockId ?>">
                        Website
                    </label>

                    <input
                        type="text"
                        name="website"
                        id="website_<?= $blockId ?>"
                        value=""
                        tabindex="-1"
                        autocomplete="off">
                </div>

                <div class="row g-3">

                    <?php foreach ($fields as $field): ?>
                        <?php
                        $fieldId = (int)$field['id'];
                        $name = (string)$field['name'];
                        $type = (string)$field['type'];
                        $required = (int)$field['is_required'] === 1;
                        $colClass = $getColumnClass(
                            $field['width'] ?? '100'
                        );

                        $value = $getPostedValue(
                            $field,
                            $postedValues
                        );

                        $inputId = 'field_'
                            . $blockId
                            . '_'
                            . $fieldId;
                        ?>

                        <?php if ($type === 'hidden'): ?>

                            <input
                                type="hidden"
                                name="fields[<?= e($name) ?>]"
                                value="<?= e(
                                    $postedValues[$name]
                                    ?? $field['placeholder']
                                    ?? ''
                                ) ?>">

                            <?php continue; ?>

                        <?php endif; ?>

                        <div class="<?= e($colClass) ?>">

                            <?php if ($type === 'heading'): ?>

                                <div class="rw-form-heading">
                                    <h3 class="h4 mb-2">
                                        <?= e($field['label']) ?>
                                    </h3>

                                    <?php if (!empty($field['help_text'])): ?>
                                        <div class="text-muted">
                                            <?= nl2br(e($field['help_text'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                            <?php elseif ($type === 'html'): ?>

                                <div class="rw-form-info">
                                    <?php if (!empty($field['label'])): ?>
                                        <strong class="d-block mb-1">
                                            <?= e($field['label']) ?>
                                        </strong>
                                    <?php endif; ?>

                                    <?php if (!empty($field['help_text'])): ?>
                                        <?= nl2br(e($field['help_text'])) ?>
                                    <?php endif; ?>
                                </div>

                            <?php elseif ($type === 'checkbox'): ?>

                                <div class="form-check mt-4">

                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="fields[<?= e($name) ?>]"
                                        id="<?= e($inputId) ?>"
                                        value="1"
                                        <?= $value === '1' ? 'checked' : '' ?>
                                        <?= $required ? 'required' : '' ?>>

                                    <label
                                        class="form-check-label"
                                        for="<?= e($inputId) ?>">

                                        <?= e($field['label']) ?>

                                        <?php if ($required): ?>
                                            <span class="text-danger">*</span>
                                        <?php endif; ?>

                                    </label>

                                    <?php if (!empty($field['help_text'])): ?>
                                        <div class="form-text">
                                            <?= e($field['help_text']) ?>
                                        </div>
                                    <?php endif; ?>

                                </div>

                            <?php elseif ($type === 'radio'): ?>

                                <fieldset>

                                    <legend class="form-label">
                                        <?= e($field['label']) ?>

                                        <?php if ($required): ?>
                                            <span class="text-danger">*</span>
                                        <?php endif; ?>
                                    </legend>

                                    <?php foreach ($getFieldOptions($field) as $option): ?>
                                        <?php
                                        $optionId = $inputId
                                            . '_'
                                            . substr(md5($option), 0, 10);
                                        ?>

                                        <div class="form-check">

                                            <input
                                                class="form-check-input"
                                                type="radio"
                                                name="fields[<?= e($name) ?>]"
                                                id="<?= e($optionId) ?>"
                                                value="<?= e($option) ?>"
                                                <?= (string)$value === $option ? 'checked' : '' ?>
                                                <?= $required ? 'required' : '' ?>>

                                            <label
                                                class="form-check-label"
                                                for="<?= e($optionId) ?>">
                                                <?= e($option) ?>
                                            </label>

                                        </div>
                                    <?php endforeach; ?>

                                    <?php if (!empty($field['help_text'])): ?>
                                        <div class="form-text">
                                            <?= e($field['help_text']) ?>
                                        </div>
                                    <?php endif; ?>

                                </fieldset>

                            <?php elseif ($type === 'checkbox_group'): ?>

                                <fieldset class="form-check-group">

                                    <legend class="form-label">
                                        <?= e($field['label']) ?>

                                        <?php if ($required): ?>
                                            <span class="text-danger">*</span>
                                        <?php endif; ?>
                                    </legend>

                                    <?php
                                    $selectedValues = is_array($value)
                                        ? $value
                                        : [];
                                    ?>

                                    <?php foreach ($getFieldOptions($field) as $option): ?>
                                        <?php
                                        $optionId = $inputId
                                            . '_'
                                            . substr(md5($option), 0, 10);
                                        ?>

                                        <div class="form-check">

                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                name="fields[<?= e($name) ?>][]"
                                                id="<?= e($optionId) ?>"
                                                value="<?= e($option) ?>"
                                                <?= in_array($option, $selectedValues, true) ? 'checked' : '' ?>>

                                            <label
                                                class="form-check-label"
                                                for="<?= e($optionId) ?>">
                                                <?= e($option) ?>
                                            </label>

                                        </div>
                                    <?php endforeach; ?>

                                    <?php if (!empty($field['help_text'])): ?>
                                        <div class="form-text">
                                            <?= e($field['help_text']) ?>
                                        </div>
                                    <?php endif; ?>

                                </fieldset>

                            <?php else: ?>

                                <label
                                    class="form-label"
                                    for="<?= e($inputId) ?>">

                                    <?= e($field['label']) ?>

                                    <?php if ($required): ?>
                                        <span class="text-danger">*</span>
                                    <?php endif; ?>

                                </label>

                                <?php if ($type === 'textarea'): ?>

                                    <textarea
                                        name="fields[<?= e($name) ?>]"
                                        id="<?= e($inputId) ?>"
                                        class="form-control"
                                        rows="5"
                                        placeholder="<?= e($field['placeholder'] ?? '') ?>"
                                        <?= $required ? 'required' : '' ?>><?= e((string)$value) ?></textarea>

                                <?php elseif ($type === 'select'): ?>

                                    <select
                                        name="fields[<?= e($name) ?>]"
                                        id="<?= e($inputId) ?>"
                                        class="form-select"
                                        <?= $required ? 'required' : '' ?>>

                                        <option value="">
                                            — Vyberte —
                                        </option>

                                        <?php foreach ($getFieldOptions($field) as $option): ?>
                                            <option
                                                value="<?= e($option) ?>"
                                                <?= (string)$value === $option ? 'selected' : '' ?>>
                                                <?= e($option) ?>
                                            </option>
                                        <?php endforeach; ?>

                                    </select>

                                <?php else: ?>

                                    <?php
                                    $inputType = match ($type) {
                                        'email' => 'email',
                                        'phone' => 'tel',
                                        'number' => 'number',
                                        'date' => 'date',
                                        'time' => 'time',
                                        'url' => 'url',
                                        default => 'text',
                                    };
                                    ?>

                                    <input
                                        type="<?= e($inputType) ?>"
                                        name="fields[<?= e($name) ?>]"
                                        id="<?= e($inputId) ?>"
                                        class="form-control"
                                        value="<?= e((string)$value) ?>"
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
                 <?php if ((int)$form['use_recaptcha'] === 1): ?>

                    <div class="mt-4">
                        <div class="g-recaptcha"
                            data-sitekey="<?= e(setting('recaptcha_site_key')) ?>">
                        </div>
                    </div>

                <?php endif; ?>                    
                <div class="mt-4">
                    
                    <button
                        type="submit"
                        class="btn btn-primary">
                        Odeslat
                    </button>
                </div>

            </form>

        <?php endif; ?>

    </div>

</section>