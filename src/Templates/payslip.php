<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslip - <?= esc($employee['name'] ?? 'Employee') ?> (<?= esc($payPeriod ?? date('F Y')) ?>)</title>
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
        .header-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .brand-title { font-size: 20px; font-weight: bold; color: #1e293b; letter-spacing: -0.5px; }
        .brand-subtitle { font-size: 10px; color: #64748b; margin-top: 2px; }
        .doc-title { font-size: 22px; font-weight: 800; color: #0284c7; text-transform: uppercase; letter-spacing: 1px; text-align: right; }
        .doc-meta { font-size: 10px; color: #64748b; text-align: right; margin-top: 3px; }
        .confidential-badge {
            display: inline-block;
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecdd3;
            font-weight: bold;
            font-size: 8.5px;
            padding: 2px 8px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }
        .divider { height: 2px; background-color: #0284c7; margin-bottom: 20px; }
        .employee-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }
        .emp-grid { width: 100%; border-collapse: collapse; }
        .emp-grid td { padding: 4px 6px; font-size: 10px; }
        .emp-key { color: #64748b; font-weight: 500; }
        .emp-val { color: #0f172a; font-weight: bold; }
        .earnings-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .earnings-table td { width: 50%; vertical-align: top; padding: 0 8px; }
        .breakdown-table { width: 100%; border-collapse: collapse; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; }
        .breakdown-table th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            text-align: left;
            border-bottom: 2px solid #cbd5e1;
        }
        .breakdown-table th.text-right { text-align: right; }
        .breakdown-table td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; font-size: 10px; color: #334155; }
        .breakdown-table tr:nth-child(even) { background-color: #fafbfd; }
        .subtotal-row { background-color: #f8fafc !important; font-weight: bold; }
        .subtotal-row td { border-top: 1px solid #cbd5e1; color: #0f172a; font-weight: bold; }
        .net-pay-box {
            background: #ecfdf5;
            border: 2px solid #10b981;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 20px;
        }
        .net-pay-grid { width: 100%; border-collapse: collapse; }
        .net-pay-grid td { vertical-align: middle; }
        .net-label { font-size: 11px; font-weight: bold; color: #047857; text-transform: uppercase; letter-spacing: 1px; }
        .net-sub { font-size: 9px; color: #065f46; margin-top: 2px; }
        .net-amount { font-size: 24px; font-weight: 800; color: #064e3b; text-align: right; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 8px; color: #94a3b8; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td>
                <div class="brand-title"><?= esc($company['name'] ?? 'Corporate Enterprise Ltd') ?></div>
                <div class="brand-subtitle"><?= esc($company['address'] ?? 'Human Resources & Payroll Dept') ?></div>
            </td>
            <td>
                <div class="doc-title">PAYSLIP</div>
                <div class="doc-meta">Period: <strong><?= esc($payPeriod ?? date('F Y')) ?></strong></div>
                <div class="doc-meta">Pay Date: <?= esc($payDate ?? date('M d, Y')) ?></div>
                <div style="text-align: right;">
                    <span class="confidential-badge">Confidential</span>
                </div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="employee-card">
        <table class="emp-grid">
            <tr>
                <td class="emp-key">Employee Name:</td>
                <td class="emp-val"><?= esc($employee['name'] ?? 'John Doe') ?></td>
                <td class="emp-key">Employee ID:</td>
                <td class="emp-val"><?= esc($employee['id'] ?? 'EMP-0842') ?></td>
            </tr>
            <tr>
                <td class="emp-key">Designation:</td>
                <td class="emp-val"><?= esc($employee['designation'] ?? 'Senior Software Engineer') ?></td>
                <td class="emp-key">Department:</td>
                <td class="emp-val"><?= esc($employee['department'] ?? 'Engineering') ?></td>
            </tr>
            <tr>
                <td class="emp-key">Bank & Account:</td>
                <td class="emp-val"><?= esc($employee['bank_account'] ?? '•••• •••• 4912') ?></td>
                <td class="emp-key">Tax / PIN #:</td>
                <td class="emp-val"><?= esc($employee['tax_id'] ?? 'A019284901X') ?></td>
            </tr>
        </table>
    </div>

    <?php
        $earnings = $earnings ?? [
            ['title' => 'Basic Salary', 'amount' => 5000.00],
            ['title' => 'House Allowance', 'amount' => 1200.00],
            ['title' => 'Transport Allowance', 'amount' => 400.00],
            ['title' => 'Performance Bonus', 'amount' => 600.00],
        ];
        $deductions = $deductions ?? [
            ['title' => 'PAYE (Income Tax)', 'amount' => 1450.00],
            ['title' => 'Health Insurance (SHIF)', 'amount' => 195.00],
            ['title' => 'National Pension (NSSF)', 'amount' => 200.00],
            ['title' => 'Staff Welfare Fund', 'amount' => 50.00],
        ];

        $totalEarnings = array_sum(array_column($earnings, 'amount'));
        $totalDeductions = array_sum(array_column($deductions, 'amount'));
        $netPay = $totalEarnings - $totalDeductions;
    ?>

    <table class="earnings-table">
        <tr>
            <td style="padding-left: 0;">
                <table class="breakdown-table">
                    <thead>
                        <tr>
                            <th>Earnings</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($earnings as $item): ?>
                        <tr>
                            <td><?= esc($item['title']) ?></td>
                            <td style="text-align: right; font-weight: 500;"><?= esc($currency ?? '$') ?><?= number_format((float) $item['amount'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="subtotal-row">
                            <td>Total Gross Earnings</td>
                            <td style="text-align: right; color: #0284c7;"><?= esc($currency ?? '$') ?><?= number_format($totalEarnings, 2) ?></td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td style="padding-right: 0;">
                <table class="breakdown-table">
                    <thead>
                        <tr>
                            <th>Deductions</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deductions as $item): ?>
                        <tr>
                            <td><?= esc($item['title']) ?></td>
                            <td style="text-align: right; font-weight: 500; color: #dc2626;">-<?= esc($currency ?? '$') ?><?= number_format((float) $item['amount'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="subtotal-row">
                            <td>Total Deductions</td>
                            <td style="text-align: right; color: #dc2626;">-<?= esc($currency ?? '$') ?><?= number_format($totalDeductions, 2) ?></td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <div class="net-pay-box">
        <table class="net-pay-grid">
            <tr>
                <td>
                    <div class="net-label">Net Salary Payable</div>
                    <div class="net-sub">Disbursed directly to specified bank account</div>
                </td>
                <td class="net-amount">
                    <?= esc($currency ?? '$') ?><?= number_format($netPay, 2) ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Generated electronically via Jengo PDF Payroll Engine &bull; This is a computer-generated payslip and requires no physical signature.
    </div>

</body>
</html>
