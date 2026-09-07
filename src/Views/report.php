<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= esc($title ?? 'Report') ?></title>
    <?php
        /** @var \Jengo\Pdf\Schema\ReportTheme $theme */
        $theme = $theme ?? \Jengo\Pdf\Schema\ReportTheme::make($themeColor ?? '#3182ce');
        $brand = $brand ?? [];
    ?>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: <?= esc($theme->font) ?>;
            color: #1e293b;
            background: #ffffff;
            font-size: 9pt;
            line-height: 1.4;
            padding: 10px;
        }
        .header-table {
            width: 100%;
            margin-bottom: 16px;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
        }
        .brand-logo {
            max-height: 38px;
            margin-bottom: 4px;
        }
        .brand-name {
            font-size: 13pt;
            font-weight: 700;
            color: <?= esc($theme->secondary) ?>;
            letter-spacing: -0.3px;
        }
        .brand-tagline {
            font-size: 8pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 1px;
        }
        .brand-contact {
            font-size: 7.5pt;
            color: #94a3b8;
            margin-top: 2px;
            line-height: 1.3;
        }
        .report-title-section {
            text-align: right;
        }
        .report-badge {
            display: inline-block;
            background-color: <?= esc($theme->zebra) ?>;
            color: <?= esc($theme->primary) ?>;
            border: 1px solid <?= esc($theme->border) ?>;
            font-size: 7.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 2px 8px;
            border-radius: 4px;
            margin-bottom: 4px;
        }
        .title {
            font-size: 16pt;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 2px;
            letter-spacing: -0.4px;
        }
        .subtitle {
            font-size: 9pt;
            color: #64748b;
            margin-bottom: 4px;
        }
        .meta-info {
            font-size: 7.5pt;
            color: #94a3b8;
        }
        .divider {
            height: 2px;
            background-color: <?= esc($theme->primary) ?>;
            margin-bottom: 16px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        thead {
            display: table-header-group;
        }
        tr {
            page-break-inside: avoid;
        }
        th {
            background-color: <?= esc($theme->primary) ?>;
            color: <?= esc($theme->headerText) ?>;
            font-weight: 600;
            text-align: left;
            padding: 7px 9px;
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid <?= esc($theme->primary) ?>;
        }
        td {
            padding: 7px 9px;
            border-bottom: 1px solid <?= esc($theme->border) ?>;
            font-size: 8.5pt;
            color: #334155;
        }
        tbody tr:nth-child(even) {
            background-color: <?= esc($theme->zebra) ?>;
        }
        tfoot tr {
            background-color: <?= esc($theme->zebra) ?>;
            font-weight: 700;
            border-top: 2px solid <?= esc($theme->primary) ?>;
        }
        tfoot td {
            padding: 8px 9px;
            border-bottom: none;
            color: #0f172a;
            font-size: 8.5pt;
        }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 7.5pt;
            color: <?= esc($theme->footerText) ?>;
            border-top: 1px solid <?= esc($theme->border) ?>;
            padding-top: 8px;
        }
        <?= $theme->customCss ?? '' ?>
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 50%;">
                <?php if (!empty($brand['logo'])): ?>
                    <img src="<?= esc($brand['logo']) ?>" alt="Logo" class="brand-logo"><br>
                <?php endif; ?>
                <?php if (!empty($brand['name'])): ?>
                    <div class="brand-name"><?= esc($brand['name']) ?></div>
                <?php endif; ?>
                <?php if (!empty($brand['tagline'])): ?>
                    <div class="brand-tagline"><?= esc($brand['tagline']) ?></div>
                <?php endif; ?>
                <?php if (!empty($brand['email']) || !empty($brand['phone'])): ?>
                    <div class="brand-contact">
                        <?= esc(implode(' • ', array_filter([$brand['email'] ?? null, $brand['phone'] ?? null, $brand['address'] ?? null]))) ?>
                    </div>
                <?php endif; ?>
            </td>
            <td style="width: 50%;" class="report-title-section">
                <span class="report-badge">Official Schema Report</span>
                <h1 class="title"><?= esc($title) ?></h1>
                <?php if (!empty($subtitle)): ?>
                    <div class="subtitle"><?= esc($subtitle) ?></div>
                <?php endif; ?>
                <div class="meta-info">
                    Generated: <?= date($dateFormat ?? 'M d, Y H:i') ?> | Total Records: <?= count($rows) ?>
                </div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <table class="data-table">
        <thead>
            <tr>
                <?php foreach ($columns as $col): ?>
                    <th class="text-<?= esc($col->align) ?>" <?= $col->width ? 'style="width: ' . esc($col->width) . '"' : '' ?>>
                        <?= esc($col->label) ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="<?= count($columns) ?>" class="text-center" style="padding: 24px; color: #94a3b8;">
                        No records found.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php foreach ($columns as $col): ?>
                            <td class="text-<?= esc($col->align) ?>">
                                <?= $col->formatValue($row[$col->key] ?? null, $row) ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <?php if (!empty($aggregates) && !empty($rows)): ?>
            <tfoot>
                <tr>
                    <?php foreach ($columns as $index => $col): ?>
                        <td class="text-<?= esc($col->align) ?>">
                            <?php if (isset($aggregates[$col->key])): ?>
                                <?= esc($aggregates[$col->key]['label'] ?? '') ?> <?= $aggregates[$col->key]['value'] ?? '' ?>
                            <?php elseif ($index === 0): ?>
                                Total Summary
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>

    <div class="footer">
        <?php if (!empty($footerText)): ?>
            <div><?= esc($footerText) ?></div>
        <?php elseif (!empty($brand['footer_text'])): ?>
            <div><?= esc($brand['footer_text']) ?></div>
        <?php elseif (!empty($brand['name'])): ?>
            <div><?= esc($brand['name']) ?> &bull; Generated Report</div>
        <?php endif; ?>
        <?php if (!empty($showPoweredBy)): ?>
            <div style="margin-top: 3px; font-size: 7pt; color: #94a3b8;">Generated by <strong>Jengo PDF Reporting Engine</strong></div>
        <?php endif; ?>
    </div>
</body>
</html>
