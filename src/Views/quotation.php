<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quotation <?= esc($quoteNumber ?? 'QUO-001') ?></title>
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
        .header-table { width: 100%; margin-bottom: 25px; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .brand-title { font-size: 22px; font-weight: bold; color: <?= esc($primaryColor ?? '#4f46e5') ?>; letter-spacing: -0.5px; }
        .brand-subtitle { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-top: 2px; }
        .quote-title { font-size: 26px; font-weight: 800; color: #1e1b4b; text-transform: uppercase; letter-spacing: 1px; text-align: right; }
        .quote-meta { font-size: 10px; color: #64748b; text-align: right; margin-top: 3px; }
        .validity-badge {
            display: inline-block;
            background-color: #eef2ff;
            color: <?= esc($primaryColor ?? '#4f46e5') ?>;
            font-weight: bold;
            font-size: 9px;
            padding: 3px 8px;
            border-radius: 4px;
            border: 1px solid #c7d2fe;
            text-transform: uppercase;
            margin-top: 6px;
        }
        .divider { height: 2px; background-color: <?= esc($primaryColor ?? '#4f46e5') ?>; margin-bottom: 20px; }
        .info-table { width: 100%; margin-bottom: 25px; border-collapse: collapse; }
        .info-table td { width: 50%; vertical-align: top; }
        .section-label { font-size: 9px; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
        .entity-name { font-size: 13px; font-weight: bold; color: #0f172a; margin-bottom: 3px; }
        .entity-detail { font-size: 10px; color: #475569; line-height: 1.4; }
        .project-scope { background: #f8fafc; border-left: 4px solid <?= esc($primaryColor ?? '#4f46e5') ?>; padding: 12px 16px; margin-bottom: 20px; border-radius: 0 6px 6px 0; }
        .project-title { font-size: 12px; font-weight: bold; color: #1e1b4b; }
        .project-desc { font-size: 10px; color: #475569; margin-top: 3px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th {
            background-color: <?= esc($primaryColor ?? '#4f46e5') ?>;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            text-align: left;
        }
        .items-table th.text-right, .items-table td.text-right { text-align: right; }
        .items-table th.text-center, .items-table td.text-center { text-align: center; }
        .items-table td { padding: 9px 10px; border-bottom: 1px solid #f1f5f9; font-size: 10px; color: #334155; }
        .items-table tr:nth-child(even) { background-color: #f8fafc; }
        .item-name { font-weight: bold; color: #0f172a; font-size: 11px; }
        .item-desc { font-size: 9px; color: #64748b; margin-top: 2px; }
        .totals-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        .totals-table td { vertical-align: top; }
        .terms-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; font-size: 9px; color: #475569; margin-right: 20px; }
        .terms-title { font-weight: bold; color: #0f172a; margin-bottom: 4px; text-transform: uppercase; font-size: 9px; }
        .calculation-table { width: 100%; border-collapse: collapse; }
        .calculation-table td { padding: 5px 8px; font-size: 10px; }
        .calc-label { color: #64748b; text-align: right; }
        .calc-value { text-align: right; font-weight: 600; color: #0f172a; width: 90px; }
        .calc-grand-total { border-top: 2px solid <?= esc($primaryColor ?? '#4f46e5') ?>; padding-top: 8px !important; }
        .grand-total-label { font-size: 12px !important; font-weight: bold !important; color: <?= esc($primaryColor ?? '#4f46e5') ?> !important; }
        .grand-total-value { font-size: 14px !important; font-weight: 800 !important; color: <?= esc($primaryColor ?? '#4f46e5') ?> !important; }
        .acceptance-box { width: 100%; border: 1px dashed #cbd5e1; border-radius: 6px; padding: 15px; margin-top: 20px; }
        .sign-table { width: 100%; border-collapse: collapse; }
        .sign-table td { width: 50%; vertical-align: bottom; }
        .sign-line { width: 180px; border-top: 1px solid #64748b; padding-top: 4px; font-size: 9px; color: #475569; margin-top: 35px; }
        .footer { margin-top: 25px; padding-top: 10px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 8.5px; color: #94a3b8; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td>
                <?php if (!empty($company['logo'])): ?>
                    <div style="margin-bottom: 6px;"><img src="<?= esc($company['logo']) ?>" alt="Logo" style="max-height: 45px;"></div>
                <?php endif; ?>
                <div class="brand-title"><?= esc($company['name'] ?? 'Provider Name') ?></div>
                <?php if (!empty($company['tagline'])): ?>
                    <div class="brand-subtitle"><?= esc($company['tagline']) ?></div>
                <?php endif; ?>
            </td>
            <td>
                <div class="quote-title">QUOTATION</div>
                <div class="quote-meta">#<?= esc($quoteNumber ?? 'QUO-001') ?></div>
                <div class="quote-meta">Date: <?= esc($quoteDate ?? date($dateFormat ?? 'M d, Y')) ?></div>
                <div class="quote-meta">Valid Until: <?= esc($validUntil ?? date($dateFormat ?? 'M d, Y', strtotime('+30 days'))) ?></div>
                <div style="text-align: right;">
                    <span class="validity-badge">Valid for 30 Days</span>
                </div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <table class="info-table">
        <tr>
            <td>
                <div class="section-label">Prepared For:</div>
                <div class="entity-name"><?= esc($client['name'] ?? 'Client Organization') ?></div>
                <?php if (!empty($client['contact'])): ?><div class="entity-detail">Attn: <?= esc($client['contact']) ?></div><?php endif; ?>
                <?php if (!empty($client['address'])): ?><div class="entity-detail"><?= esc($client['address']) ?></div><?php endif; ?>
                <?php if (!empty($client['email'])): ?><div class="entity-detail"><?= esc($client['email']) ?></div><?php endif; ?>
            </td>
            <td style="text-align: right;">
                <div class="section-label">Prepared By:</div>
                <div class="entity-name"><?= esc($company['name'] ?? 'Provider Name') ?></div>
                <?php if (!empty($company['address'])): ?><div class="entity-detail"><?= esc($company['address']) ?></div><?php endif; ?>
                <?php if (!empty($company['email'])): ?><div class="entity-detail"><?= esc($company['email']) ?></div><?php endif; ?>
                <?php if (!empty($company['phone'])): ?><div class="entity-detail"><?= esc($company['phone']) ?></div><?php endif; ?>
            </td>
        </tr>
    </table>

    <?php if (!empty($projectTitle)): ?>
    <div class="project-scope">
        <div class="project-title">Project Scope: <?= esc($projectTitle) ?></div>
        <?php if (!empty($projectDesc)): ?><div class="project-desc"><?= esc($projectDesc) ?></div><?php endif; ?>
    </div>
    <?php endif; ?>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%;">#</th>
                <th style="width: 50%;">Deliverable / Description</th>
                <th class="text-center" style="width: 12%;">Qty / Est.</th>
                <th class="text-right" style="width: 16%;">Rate</th>
                <th class="text-right" style="width: 16%;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $items = $items ?? [];
            $subtotal = 0;
            foreach ($items as $idx => $item):
                $itemTotal = ($item['qty'] ?? 1) * ($item['price'] ?? 0);
                $subtotal += $itemTotal;
            ?>
            <tr>
                <td><?= $idx + 1 ?></td>
                <td>
                    <div class="item-name"><?= esc($item['name'] ?? 'Task') ?></div>
                    <?php if (!empty($item['desc'])): ?><div class="item-desc"><?= esc($item['desc']) ?></div><?php endif; ?>
                </td>
                <td class="text-center"><?= esc($item['qty'] ?? 1) ?> <?= esc($item['unit'] ?? 'hrs') ?></td>
                <td class="text-right"><?= esc($currency ?? '$') ?><?= number_format((float) ($item['price'] ?? 0), 2) ?></td>
                <td class="text-right"><?= esc($currency ?? '$') ?><?= number_format($itemTotal, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php
        $taxRate = $taxRate ?? 0.0;
        $taxAmount = $subtotal * $taxRate;
        $discount = $discount ?? 0.0;
        $grandTotal = ($subtotal - $discount) + $taxAmount;
    ?>
    <table class="totals-table">
        <tr>
            <td style="width: 55%;">
                <div class="terms-box">
                    <div class="terms-title">Terms & Conditions</div>
                    <p><?= esc($terms ?? '1. 50% deposit required upon project commencement. 2. Final deliverables transferred upon full settlement. 3. Estimate valid for 30 days from date of issue.') ?></p>
                </div>
            </td>
            <td style="width: 45%;">
                <table class="calculation-table">
                    <tr>
                        <td class="calc-label">Subtotal:</td>
                        <td class="calc-value"><?= esc($currency ?? '$') ?><?= number_format($subtotal, 2) ?></td>
                    </tr>
                    <?php if ($discount > 0): ?>
                    <tr>
                        <td class="calc-label">Discount:</td>
                        <td class="calc-value" style="color: #059669;">-<?= esc($currency ?? '$') ?><?= number_format($discount, 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($taxRate > 0): ?>
                    <tr>
                        <td class="calc-label">Tax (<?= ($taxRate * 100) ?>%):</td>
                        <td class="calc-value"><?= esc($currency ?? '$') ?><?= number_format($taxAmount, 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="calc-grand-total">
                        <td class="calc-label grand-total-label">Estimated Total:</td>
                        <td class="calc-value grand-total-value"><?= esc($currency ?? '$') ?><?= number_format($grandTotal, 2) ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="acceptance-box">
        <div style="font-weight: bold; color: #1e1b4b; font-size: 10px; margin-bottom: 4px;">Quote Acceptance & Authorization</div>
        <p style="font-size: 9px; color: #64748b;">To accept this estimate, please sign below and return via email.</p>
        <table class="sign-table">
            <tr>
                <td>
                    <div class="sign-line">Client Authorized Signature & Date</div>
                </td>
                <td style="text-align: right;">
                    <div class="sign-line" style="margin-left: auto;">Provider Authorized Representative</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <?php if (!empty($footerText)): ?>
            <div><?= esc($footerText) ?></div>
        <?php else: ?>
            <div><?= esc($company['name'] ?? 'Company') ?> &bull; Proposal Confidential</div>
        <?php endif; ?>
        <?php if (!empty($showPoweredBy)): ?>
            <div style="margin-top: 3px; font-size: 7.5px; color: #cbd5e1;">Generated by Jengo PDF Engine</div>
        <?php endif; ?>
    </div>

</body>
</html>
