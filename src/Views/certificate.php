<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate - <?= esc($recipientName ?? 'Recipient') ?></title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'DejaVu Sans', 'Georgia', serif;
            background-color: #fcfbf7;
            color: #1e293b;
            padding: 15px;
            text-align: center;
        }

        .outer-border {
            border: 5px double #d97706;
            border-radius: 4px;
            padding: 20px;
            background: #ffffff;
            min-height: 480px;
        }

        .inner-border {
            border: 1px solid #fde68a;
            padding: 20px;
            background: #ffffff;
        }

        .header-title {
            font-size: 12px;
            font-weight: bold;
            color: #b45309;
            text-transform: uppercase;
            letter-spacing: 4px;
            margin-bottom: 6px;
        }

        .certificate-title {
            font-size: 28px;
            font-weight: bold;
            color: #1e1b4b;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }

        .presented-to {
            font-size: 11px;
            color: #64748b;
            font-style: italic;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }

        .recipient-name {
            font-size: 24px;
            font-weight: bold;
            color: #0f172a;
            text-decoration: underline;
            text-decoration-color: #f59e0b;
            margin-bottom: 12px;
        }

        .achievement-text {
            font-size: 11px;
            color: #475569;
            max-width: 650px;
            margin: 0 auto 20px auto;
            line-height: 1.6;
        }

        .course-highlight {
            font-weight: bold;
            color: #4338ca;
        }

        .signatures-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .signatures-table td {
            width: 33.33%;
            vertical-align: middle;
            text-align: center;
        }

        .signature-line {
            width: 170px;
            margin: 0 auto;
            border-top: 1px solid #64748b;
            padding-top: 6px;
        }

        .signatory-name {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
        }

        .signatory-title {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }

        .seal-cell {
            text-align: center;
            vertical-align: middle;
        }

        .certificate-meta {
            margin-top: 20px;
            font-size: 8px;
            color: #94a3b8;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
    </style>
</head>

<body>

    <div class="outer-border">
        <div class="inner-border">

            <div class="header-title"><?= esc($issuer ?? 'Academic / Corporate Institution') ?></div>
            <div class="certificate-title"><?= esc($title ?? 'Certificate of Excellence') ?></div>

            <div class="presented-to">This certificate is proudly awarded to</div>

            <div class="recipient-name"><?= esc($recipientName ?? 'Recipient Name') ?></div>

            <div class="achievement-text">
                <?= esc($description ?? 'For successfully completing and demonstrating exceptional proficiency in') ?>
                <br>
                <span class="course-highlight"><?= esc($courseName ?? 'Specialized Advanced Program') ?></span>
            </div>

            <table class="signatures-table">
                <tr>
                    <td>
                        <div class="signature-line">
                            <div class="signatory-name"><?= esc($instructor ?? 'Dr. Elizabeth Vance') ?></div>
                            <div class="signatory-title"><?= esc($instructorTitle ?? 'Lead Instructor') ?></div>
                        </div>
                    </td>
                    <td class="seal-cell">
                        <!-- Pure Vector Gold Medal Seal -->
                        <svg width="86" height="86" viewBox="0 0 100 100" style="display: block; margin: 0 auto;">
                            <!-- Ribbon Tails -->
                            <polygon points="32,74 22,96 36,88 46,96 43,74" fill="#b45309" />
                            <polygon points="68,74 78,96 64,88 54,96 57,74" fill="#78350f" />
                            
                            <!-- Outer Gold Rings -->
                            <circle cx="50" cy="50" r="45" fill="#d97706" />
                            <circle cx="50" cy="50" r="42" fill="#fef3c7" />
                            <circle cx="50" cy="50" r="39" fill="#fde68a" />
                            
                            <!-- Concentric Detail Rings -->
                            <circle cx="50" cy="50" r="35" fill="none" stroke="#b45309" stroke-width="1.2" stroke-dasharray="3,2" />
                            <circle cx="50" cy="50" r="31" fill="#fef9c3" stroke="#b45309" stroke-width="0.8" />
                            
                            <!-- Seal Text and Stars -->
                            <text x="50" y="38" text-anchor="middle" font-family="'DejaVu Sans', sans-serif" font-size="6.5" font-weight="bold" fill="#78350f">★ ★ ★</text>
                            <text x="50" y="51" text-anchor="middle" font-family="'DejaVu Sans', sans-serif" font-size="8" font-weight="bold" fill="#78350f" letter-spacing="1">VERIFIED</text>
                            <text x="50" y="62" text-anchor="middle" font-family="'DejaVu Sans', sans-serif" font-size="6.5" font-weight="bold" fill="#92400e">EXCELLENCE</text>
                        </svg>
                    </td>
                    <td>
                        <div class="signature-line">
                            <div class="signatory-name"><?= esc($director ?? 'Director Name') ?></div>
                            <div class="signatory-title"><?= esc($directorTitle ?? 'Executive Director') ?></div>
                        </div>
                    </td>
                </tr>
            </table>

            <table style="width: 100%; margin-top: 15px; border-collapse: collapse;">
                <tr>
                    <td style="text-align: left; vertical-align: middle;">
                        <div class="certificate-meta" style="margin-top: 0;">
                            Certificate ID: <strong><?= esc($certificateId ?? 'CERT-2026-98124') ?></strong> &bull;
                            Date Issued: <strong><?= esc($issueDate ?? date('F d, Y')) ?></strong>
                        </div>
                    </td>
                    <?php if (!isset($showQrCode) || $showQrCode !== false): ?>
                    <td style="width: 55px; text-align: right; vertical-align: middle;">
                        <div style="display: inline-block; background: #ffffff; padding: 2px; border-radius: 3px; border: 1px solid #e2e8f0;">
                            <?= pdf_qr_code($qrCodeUrl ?? ('https://verify.jengo.dev/cert/' . ($certificateId ?? 'CERT-2026-98124')), size: 45) ?>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
            </table>

        </div>
    </div>

</body>
</html>