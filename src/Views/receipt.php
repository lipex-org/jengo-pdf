<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Receipt <?= esc($receiptNumber ?? 'REC-001') ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: <?= esc($fontFamily ?? 'DejaVu Sans, Helvetica, Arial, sans-serif') ?>;
            font-size: 11px;
            color: #1e293b;
            background: #ffffff;
            padding: 30px;
            line-height: 1.5;
        }
        .header-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .brand-title { font-size: 22px; font-weight: bold; color: <?= esc($primaryColor ?? '#059669') ?>; letter-spacing: -0.5px; }
        .brand-subtitle { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-top: 2px; }
        .receipt-title { font-size: 26px; font-weight: 800; color: #064e3b; text-transform: uppercase; letter-spacing: 1px; text-align: right; }
        .receipt-meta { font-size: 10px; color: #64748b; text-align: right; margin-top: 3px; }
        .paid-stamp {
            display: inline-block;
            border: 2px solid <?= esc($primaryColor ?? '#059669') ?>;
            color: <?= esc($primaryColor ?? '#059669') ?>;
            font-weight: 900;
            font-size: 11px;
            letter-spacing: 2px;
            padding: 4px 12px;
            border-radius: 4px;
            text-transform: uppercase;
            margin-top: 6px;
        }
        .divider { height: 2px; background-color: <?= esc($primaryColor ?? '#059669') ?>; margin-bottom: 20px; }
        .callout-amount {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
            text-align: center;
        }
        .amount-label { font-size: 10px; font-weight: bold; color: #047857; text-transform: uppercase; letter-spacing: 1px; }
        .amount-value { font-size: 28px; font-weight: 800; color: #064e3b; margin: 4px 0; }
        .amount-words { font-size: 10px; font-style: italic; color: #065f46; }
        .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .info-table td { width: 50%; vertical-align: top; }
        .section-label { font-size: 9px; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
        .entity-name { font-size: 13px; font-weight: bold; color: #0f172a; margin-bottom: 3px; }
        .entity-detail { font-size: 10px; color: #475569; line-height: 1.4; }
        .payment-meta-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 20px;
        }
        .meta-grid { width: 100%; border-collapse: collapse; }
        .meta-grid td { padding: 4px 8px; font-size: 10px; }
        .meta-key { color: #64748b; font-weight: 500; }
        .meta-val { color: #0f172a; font-weight: 600; text-align: right; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th {
            background-color: <?= esc($primaryColor ?? '#059669') ?>;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            text-align: left;
        }
        .items-table th.text-right, .items-table td.text-right { text-align: right; }
        .items-table td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; font-size: 10px; color: #334155; }
        .items-table tr:nth-child(even) { background-color: #f8fafc; }
        .auth-table { width: 100%; margin-top: 30px; border-collapse: collapse; }
        .auth-table td { width: 50%; vertical-align: bottom; }
        .sign-line { width: 180px; border-top: 1px solid #64748b; padding-top: 4px; font-size: 9px; color: #475569; margin-top: 35px; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 8.5px; color: #94a3b8; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td>
                <?php if (!empty($company['logo'])): ?>
                    <div style="margin-bottom: 6px;"><img src="<?= esc($company['logo']) ?>" alt="Logo" style="max-height: 45px;"></div>
                <?php endif; ?>
                <div class="brand-title"><?= esc($company['name'] ?? 'Company / Organization') ?></div>
                <?php if (!empty($company['tagline'])): ?>
                    <div class="brand-subtitle"><?= esc($company['tagline']) ?></div>
                <?php endif; ?>
            </td>
            <td>
                <div class="receipt-title">RECEIPT</div>
                <div class="receipt-meta">Receipt #: <strong><?= esc($receiptNumber ?? 'REC-001') ?></strong></div>
                <div class="receipt-meta">Date: <?= esc($receiptDate ?? date(($dateFormat ?? 'M d, Y') . ' H:i')) ?></div>
                <div style="text-align: right;">
                    <div class="paid-stamp">✔ OFFICIAL RECEIPT</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="callout-amount">
        <div class="amount-label">Amount Received</div>
        <div class="amount-value"><?= esc($currency ?? '$') ?><?= number_format((float) ($amountPaid ?? 0), 2) ?></div>
        <?php if (!empty($amountInWords)): ?>
            <div class="amount-words">(<?= esc($amountInWords) ?>)</div>
        <?php endif; ?>
    </div>

    <table class="info-table">
        <tr>
            <td>
                <div class="section-label">Received From:</div>
                <div class="entity-name"><?= esc($payer['name'] ?? 'Payer / Client Name') ?></div>
                <?php if (!empty($payer['id_or_tax'])): ?><div class="entity-detail">ID/Tax #: <?= esc($payer['id_or_tax']) ?></div><?php endif; ?>
                <?php if (!empty($payer['email'])): ?><div class="entity-detail"><?= esc($payer['email']) ?></div><?php endif; ?>
                <?php if (!empty($payer['phone'])): ?><div class="entity-detail"><?= esc($payer['phone']) ?></div><?php endif; ?>
            </td>
            <td style="text-align: right;">
                <div class="section-label">Issued By:</div>
                <div class="entity-name"><?= esc($company['name'] ?? 'Company Name') ?></div>
                <?php if (!empty($company['address'])): ?><div class="entity-detail"><?= esc($company['address']) ?></div><?php endif; ?>
                <?php if (!empty($company['tax_id'])): ?><div class="entity-detail">PIN/Tax: <?= esc($company['tax_id']) ?></div><?php endif; ?>
                <?php if (!empty($company['email'])): ?><div class="entity-detail"><?= esc($company['email']) ?></div><?php endif; ?>
            </td>
        </tr>
    </table>

    <div class="payment-meta-box">
        <table class="meta-grid">
            <tr>
                <td class="meta-key">Payment Method:</td>
                <td class="meta-val"><?= esc($paymentMethod ?? 'Credit Card / M-Pesa / Bank Wire') ?></td>
                <td class="meta-key" style="text-align: right;">Transaction Ref:</td>
                <td class="meta-val"><?= esc($transactionRef ?? 'TXN-001') ?></td>
            </tr>
            <tr>
                <td class="meta-key">Invoice / Order Ref:</td>
                <td class="meta-val"><?= esc($invoiceRef ?? 'INV-001') ?></td>
                <td class="meta-key" style="text-align: right;">Outstanding Balance:</td>
                <td class="meta-val" style="color: #059669;"><?= esc($currency ?? '$') ?><?= number_format((float) ($balanceRemaining ?? 0.0), 2) ?></td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 8%;">#</th>
                <th style="width: 62%;">Description of Payment / Settled Services</th>
                <th class="text-right" style="width: 30%;">Amount Settled</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $items = $items ?? [];
            foreach ($items as $idx => $item):
            ?>
            <tr>
                <td><?= $idx + 1 ?></td>
                <td><?= esc($item['description'] ?? 'Payment for services') ?></td>
                <td class="text-right"><?= esc($currency ?? '$') ?><?= number_format((float) ($item['amount'] ?? 0), 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table class="auth-table">
        <tr>
            <td>
                <div class="sign-line">Received By (Authorized Cashier)</div>
            </td>
            <td style="text-align: right;">
                <div class="sign-line" style="margin-left: auto;">Client / Representative Signature</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        <?php if (!empty($footerText)): ?>
            <div><?= esc($footerText) ?></div>
        <?php else: ?>
            <div>Generated electronically via Jengo PDF Engine &bull; Valid without physical stamp if verified</div>
        <?php endif; ?>
        <?php if (!empty($showPoweredBy)): ?>
            <div style="margin-top: 3px; font-size: 7.5px; color: #cbd5e1;">Generated by Jengo PDF Engine</div>
        <?php endif; ?>
    </div>

</body>
</html>
