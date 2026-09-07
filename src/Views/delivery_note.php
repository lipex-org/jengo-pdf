<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delivery Note <?= esc($deliveryNumber ?? 'DN-001') ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: <?= esc($fontFamily ?? 'DejaVu Sans, Helvetica, Arial, sans-serif') ?>;
            font-size: 11px;
            color: #1e293b;
            background: #ffffff;
            padding: 12px 15px;
            line-height: 1.5;
        }
        .header-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .brand-title { font-size: 22px; font-weight: bold; color: <?= esc($primaryColor ?? '#0284c7') ?>; letter-spacing: -0.5px; }
        .brand-subtitle { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-top: 2px; }
        .doc-title { font-size: 24px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 1px; text-align: right; }
        .doc-meta { font-size: 10px; color: #64748b; text-align: right; margin-top: 3px; }
        .divider { height: 2px; background-color: <?= esc($primaryColor ?? '#0284c7') ?>; margin-bottom: 20px; }
        .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .info-table td { width: 50%; vertical-align: top; }
        .section-label { font-size: 9px; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
        .entity-name { font-size: 13px; font-weight: bold; color: #0f172a; margin-bottom: 3px; }
        .entity-detail { font-size: 10px; color: #475569; line-height: 1.4; }
        .logistics-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 20px;
        }
        .logistics-grid { width: 100%; border-collapse: collapse; }
        .logistics-grid td { padding: 4px 6px; font-size: 10px; }
        .log-key { color: #64748b; }
        .log-val { color: #0f172a; font-weight: 600; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th {
            background-color: <?= esc($primaryColor ?? '#0284c7') ?>;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            text-align: left;
        }
        .items-table th.text-center, .items-table td.text-center { text-align: center; }
        .items-table td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; font-size: 10px; color: #334155; }
        .items-table tr:nth-child(even) { background-color: #f8fafc; }
        .item-sku { font-family: monospace; font-size: 9px; color: #64748b; }
        .item-name { font-weight: bold; color: #0f172a; font-size: 11px; }
        .signature-section { width: 100%; margin-top: 35px; border-collapse: collapse; }
        .signature-section td { width: 50%; vertical-align: bottom; }
        .sign-box { border: 1px dashed #cbd5e1; border-radius: 6px; padding: 12px; margin-right: 10px; min-height: 80px; }
        .sign-title { font-weight: bold; color: #0f172a; font-size: 9px; text-transform: uppercase; margin-bottom: 30px; }
        .sign-sub { font-size: 8px; color: #64748b; border-top: 1px solid #cbd5e1; padding-top: 4px; }
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
                <div class="brand-title"><?= esc($company['name'] ?? 'Logistics Provider') ?></div>
                <?php if (!empty($company['tagline'])): ?>
                    <div class="brand-subtitle"><?= esc($company['tagline']) ?></div>
                <?php endif; ?>
            </td>
            <td>
                <div class="doc-title">DELIVERY NOTE</div>
                <div class="doc-meta">DN #: <strong><?= esc($deliveryNumber ?? 'DN-001') ?></strong></div>
                <div class="doc-meta">Date: <?= esc($dispatchDate ?? date($dateFormat ?? 'M d, Y')) ?></div>
                <div class="doc-meta">PO Ref: <?= esc($poNumber ?? 'PO-001') ?></div>
                <div style="margin-top: 4px; text-align: right;">
                    <?= pdf_barcode($deliveryNumber ?? 'DN-001', height: 24, width: 1, showText: false) ?>
                </div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <table class="info-table">
        <tr>
            <td>
                <div class="section-label">Ship To / Consignee:</div>
                <div class="entity-name"><?= esc($recipient['name'] ?? 'Receiving Client') ?></div>
                <?php if (!empty($recipient['contact'])): ?><div class="entity-detail">Contact: <?= esc($recipient['contact']) ?></div><?php endif; ?>
                <?php if (!empty($recipient['address'])): ?><div class="entity-detail"><?= esc($recipient['address']) ?></div><?php endif; ?>
                <?php if (!empty($recipient['phone'])): ?><div class="entity-detail">Phone: <?= esc($recipient['phone']) ?></div><?php endif; ?>
            </td>
            <td style="text-align: right;">
                <div class="section-label">Shipped From:</div>
                <div class="entity-name"><?= esc($company['name'] ?? 'Dispatch Warehouse') ?></div>
                <?php if (!empty($company['address'])): ?><div class="entity-detail"><?= esc($company['address']) ?></div><?php endif; ?>
                <?php if (!empty($company['phone'])): ?><div class="entity-detail">Dispatch Dept: <?= esc($company['phone']) ?></div><?php endif; ?>
            </td>
        </tr>
    </table>

    <div class="logistics-card">
        <table class="logistics-grid">
            <tr>
                <td class="log-key">Carrier / Courier:</td>
                <td class="log-val"><?= esc($carrier ?? 'Express Freight Line') ?></td>
                <td class="log-key">Tracking / Waybill:</td>
                <td class="log-val"><?= esc($trackingNumber ?? 'TRK-001') ?></td>
            </tr>
            <tr>
                <td class="log-key">Vehicle Reg:</td>
                <td class="log-val"><?= esc($vehicleNumber ?? 'KBZ 892M') ?></td>
                <td class="log-key">Package Count & Weight:</td>
                <td class="log-val"><?= esc($totalPackages ?? '1 Package') ?> &bull; <?= esc($totalWeight ?? '10 kg') ?></td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%;">#</th>
                <th style="width: 18%;">Item / SKU</th>
                <th style="width: 46%;">Description</th>
                <th class="text-center" style="width: 15%;">Ordered Qty</th>
                <th class="text-center" style="width: 15%;">Shipped Qty</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $items = $items ?? [];
            foreach ($items as $idx => $item):
            ?>
            <tr>
                <td><?= $idx + 1 ?></td>
                <td><span class="item-sku"><?= esc($item['sku'] ?? 'SKU-00' . ($idx+1)) ?></span></td>
                <td>
                    <div class="item-name"><?= esc($item['name'] ?? 'Product') ?></div>
                    <?php if (!empty($item['desc'])): ?><div style="font-size: 9px; color: #64748b;"><?= esc($item['desc']) ?></div><?php endif; ?>
                </td>
                <td class="text-center"><?= esc($item['ordered_qty'] ?? 1) ?> <?= esc($item['unit'] ?? 'units') ?></td>
                <td class="text-center" style="font-weight: bold; color: <?= esc($primaryColor ?? '#0284c7') ?>;"><?= esc($item['shipped_qty'] ?? 1) ?> <?= esc($item['unit'] ?? 'units') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table class="signature-section">
        <tr>
            <td>
                <div class="sign-box">
                    <div class="sign-title">Dispatched By (Warehouse / Driver)</div>
                    <div class="sign-sub">Signature & Date: ____________________</div>
                </div>
            </td>
            <td>
                <div class="sign-box" style="margin-right: 0; margin-left: 10px;">
                    <div class="sign-title">Received in Good Order & Condition</div>
                    <div class="sign-sub">Receiver Name, Signature & Stamp: ____________________</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        <?php if (!empty($footerText)): ?>
            <div><?= esc($footerText) ?></div>
        <?php else: ?>
            <div>Generated by Jengo PDF Engine &bull; Official Dispatch Manifest</div>
        <?php endif; ?>
        <?php if (!empty($showPoweredBy)): ?>
            <div style="margin-top: 3px; font-size: 7.5px; color: #cbd5e1;">Generated by Jengo PDF Engine</div>
        <?php endif; ?>
    </div>

</body>
</html>
