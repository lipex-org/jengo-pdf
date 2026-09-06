<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= esc($title ?? 'Report') ?></title>
    <?php
        /** @var \Jengo\Pdf\Schema\ReportTheme $theme */
        $theme = $theme ?? \Jengo\Pdf\Schema\ReportTheme::make($themeColor ?? '#3182ce');
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
            font-size: 9.5pt;
            line-height: 1.5;
            padding: 15px;
        }
        .header {
            margin-bottom: 24px;
            border-bottom: 2px solid <?= esc($theme->primary) ?>;
            padding-bottom: 12px;
        }
        .title {
            font-size: 18pt;
            font-weight: 700;
            color: <?= esc($theme->secondary) ?>;
            margin-bottom: 4px;
            letter-spacing: -0.5px;
        }
        .subtitle {
            font-size: 10pt;
            color: #64748b;
        }
        .meta {
            margin-top: 6px;
            font-size: 8pt;
            color: #94a3b8;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
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
            padding: 8px 10px;
            font-size: 8.5pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid <?= esc($theme->primary) ?>;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid <?= esc($theme->border) ?>;
            font-size: 9pt;
            color: #334155;
        }
        tbody tr:nth-child(even) {
            background-color: <?= esc($theme->zebra) ?>;
        }
        tfoot tr {
            background-color: #f8fafc;
            font-weight: 700;
            border-top: 2px solid <?= esc($theme->primary) ?>;
        }
        tfoot td {
            padding: 10px;
            border-bottom: none;
            color: #0f172a;
        }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8pt;
            color: <?= esc($theme->footerText) ?>;
            border-top: 1px solid <?= esc($theme->border) ?>;
            padding-top: 10px;
        }
        <?= $theme->customCss ?? '' ?>
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title"><?= esc($title) ?></h1>
        <?php if (!empty($subtitle)): ?>
            <p class="subtitle"><?= esc($subtitle) ?></p>
        <?php endif; ?>
        <p class="meta">Generated on <?= date('Y-m-d H:i:s') ?> | Total Records: <?= count($rows) ?></p>
    </div>

    <table>
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
                                <?= esc($aggregates[$col->key]['label'] ?? '') ?> <?= $col->formatValue($aggregates[$col->key]['value'] ?? null) ?>
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
        <?php elseif (!empty($brand['name'])): ?>
            <div><?= esc($brand['name']) ?> &bull; Generated Report</div>
        <?php endif; ?>
        <?php if (!empty($showPoweredBy)): ?>
            <div style="margin-top: 3px; font-size: 7.5pt; color: #94a3b8;">Generated by <strong>Jengo PDF Reporting Engine</strong></div>
        <?php endif; ?>
    </div>
</body>
</html>
