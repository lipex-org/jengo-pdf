<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice <?= esc($invoiceNumber ?? 'INV-001') ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            background: #ffffff;
            padding: 30px;
            line-height: 1.5;
        }
        .header-table { width: 100%; margin-bottom: 25px; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .brand-title { font-size: 22px; font-weight: bold; color: #0284c7; letter-spacing: -0.5px; }
        .brand-subtitle { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-top: 2px; }
        .invoice-title { font-size: 26px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 1px; text-align: right; }
        .invoice-meta { font-size: 10px; color: #64748b; text-align: right; margin-top: 3px; }
        .status-badge {
            display: inline-block;
            background-color: #ecfdf5;
            color: #059669;
            font-weight: bold;
            font-size: 9px;
            padding: 3px 8px;
            border-radius: 4px;
            border: 1px solid #a7f3d0;
            text-transform: uppercase;
            margin-top: 6px;
        }
        .divider { height: 2px; background-color: #0284c7; margin-bottom: 20px; }
        .info-table { width: 100%; margin-bottom: 25px; border-collapse: collapse; }
        .info-table td { width: 50%; vertical-align: top; }
        .section-label { font-size: 9px; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
        .entity-name { font-size: 13px; font-weight: bold; color: #0f172a; margin-bottom: 3px; }
        .entity-detail { font-size: 10px; color: #475569; line-height: 1.4; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th {
            background-color: #0284c7;
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
        .notes-card { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; font-size: 9px; color: #475569; margin-right: 20px; }
        .notes-title { font-weight: bold; color: #0f172a; margin-bottom: 4px; text-transform: uppercase; font-size: 9px; }
        .calculation-table { width: 100%; border-collapse: collapse; }
        .calculation-table td { padding: 5px 8px; font-size: 10px; }
        .calc-label { color: #64748b; text-align: right; }
        .calc-value { text-align: right; font-weight: 600; color: #0f172a; width: 90px; }
        .calc-grand-total { border-top: 2px solid #0284c7; padding-top: 8px !important; }
        .grand-total-label { font-size: 12px !important; font-weight: bold !important; color: #0284c7 !important; }
        .grand-total-value { font-size: 14px !important; font-weight: 800 !important; color: #0284c7 !important; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 8px; color: #94a3b8; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td>
                <div class="brand-title"><?= esc($company['name'] ?? 'Company Name') ?></div>
                <div class="brand-subtitle"><?= esc($company['tagline'] ?? 'Business & Technology Solutions') ?></div>
            </td>
            <td>
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-meta">#<?= esc($invoiceNumber ?? 'INV-001') ?></div>
                <div class="invoice-meta">Date: <?= esc($invoiceDate ?? date('M d, Y')) ?></div>
                <div class="invoice-meta">Due: <?= esc($dueDate ?? date('M d, Y', strtotime('+14 days'))) ?></div>
                <div style="text-align: right;">
                    <span class="status-badge"><?= esc($status ?? 'PAID') ?></span>
                </div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <table class="info-table">
        <tr>
            <td>
                <div class="section-label">Billed To:</div>
                <div class="entity-name"><?= esc($client['name'] ?? 'Client Name') ?></div>
                <?php if (!empty($client['contact'])): ?><div class="entity-detail"><?= esc($client['contact']) ?></div><?php endif; ?>
                <?php if (!empty($client['address'])): ?><div class="entity-detail"><?= esc($client['address']) ?></div><?php endif; ?>
                <?php if (!empty($client['city_state'])): ?><div class="entity-detail"><?= esc($client['city_state']) ?></div><?php endif; ?>
                <?php if (!empty($client['email'])): ?><div class="entity-detail"><?= esc($client['email']) ?></div><?php endif; ?>
            </td>
            <td style="text-align: right;">
                <div class="section-label">Issued By:</div>
                <div class="entity-name"><?= esc($company['name'] ?? 'Company Name') ?></div>
                <?php if (!empty($company['address'])): ?><div class="entity-detail"><?= esc($company['address']) ?></div><?php endif; ?>
                <?php if (!empty($company['city_state'])): ?><div class="entity-detail"><?= esc($company['city_state']) ?></div><?php endif; ?>
                <?php if (!empty($company['tax_id'])): ?><div class="entity-detail">Tax ID: <?= esc($company['tax_id']) ?></div><?php endif; ?>
                <?php if (!empty($company['email'])): ?><div class="entity-detail"><?= esc($company['email']) ?></div><?php endif; ?>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%;">#</th>
                <th style="width: 54%;">Item & Description</th>
                <th class="text-center" style="width: 10%;">Qty</th>
                <th class="text-right" style="width: 15%;">Unit Price</th>
                <th class="text-right" style="width: 15%;">Total</th>
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
                    <div class="item-name"><?= esc($item['name'] ?? 'Item') ?></div>
                    <?php if (!empty($item['desc'])): ?><div class="item-desc"><?= esc($item['desc']) ?></div><?php endif; ?>
                </td>
                <td class="text-center"><?= esc($item['qty'] ?? 1) ?></td>
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
                <div class="notes-card">
                    <div class="notes-title">Payment Info & Notes</div>
                    <p><?= esc($paymentNotes ?? 'Thank you for your business. Please make payments according to agreed terms.') ?></p>
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
                        <td class="calc-label grand-total-label">Total:</td>
                        <td class="calc-value grand-total-value"><?= esc($currency ?? '$') ?><?= number_format($grandTotal, 2) ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="footer">
        Generated by Jengo PDF Engine &bull; All Rights Reserved.
    </div>

</body>
</html>
