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
            padding: 20px;
            text-align: center;
        }

        .outer-border {
            border: 6px double #d97706;
            border-radius: 4px;
            padding: 25px;
            background: #ffffff;
            min-height: 500px;
        }

        .inner-border {
            border: 1px solid #fde68a;
            padding: 25px;
            background: #ffffff;
        }

        .header-title {
            font-size: 13px;
            font-weight: bold;
            color: #b45309;
            text-transform: uppercase;
            letter-spacing: 4px;
            margin-bottom: 8px;
        }

        .certificate-title {
            font-size: 30px;
            font-weight: bold;
            color: #1e1b4b;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 12px;
        }

        .presented-to {
            font-size: 12px;
            color: #64748b;
            font-style: italic;
            margin-bottom: 12px;
            letter-spacing: 1px;
        }

        .recipient-name {
            font-size: 26px;
            font-weight: bold;
            color: #0f172a;
            text-decoration: underline;
            text-decoration-color: #f59e0b;
            margin-bottom: 14px;
        }

        .achievement-text {
            font-size: 12px;
            color: #475569;
            max-width: 650px;
            margin: 0 auto 25px auto;
            line-height: 1.6;
        }

        .course-highlight {
            font-weight: bold;
            color: #4338ca;
        }

        .signatures-table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
        }

        .signatures-table td {
            width: 33.33%;
            vertical-align: bottom;
            text-align: center;
        }

        .signature-line {
            width: 180px;
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
        }

        .seal-container {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .seal {
            background-color: #fef3c7;
            width: 70px;
            height: 70px;
            margin: 0 auto;
            border: 2px solid #d97706;
            border-radius: 50%;
            background: radial-gradient(circle, #fef3c7 0%, #fde68a 100%);
            display: inline-block;
            line-height: 70px;
            font-size: 9px;
            font-weight: bold;
            color: #92400e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .certificate-meta {
            margin-top: 25px;
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
                <?= esc($description ?? 'For successfully completing and mastering the curriculum in') ?>
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
                    <td>
                        <div class="seal-container">
                            <div class="seal">★ VERIFIED ★</div>
                        </div>
                    </td>
                    <td>
                        <div class="signature-line">
                            <div class="signatory-name"><?= esc($director ?? 'Director Name') ?></div>
                            <div class="signatory-title"><?= esc($directorTitle ?? 'Executive Director') ?></div>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="certificate-meta">
                Certificate ID: <strong><?= esc($certificateId ?? 'CERT-2026-98124') ?></strong> &bull;
                Date Issued: <strong><?= esc($issueDate ?? date('F d, Y')) ?></strong>
            </div>

        </div>
    </div>

</body>

</html>