<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport Fiscal {{ $year }} — ReziApp</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; margin: 0; padding: 30px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #1f2937; padding-bottom: 15px; }
        .header h1 { font-size: 22px; color: #1f2937; margin: 0; }
        .header p { color: #6b7280; font-size: 11px; margin: 5px 0 0; }
        .section { margin-bottom: 25px; }
        .section h2 { font-size: 14px; color: #1f2937; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px; margin-bottom: 10px; }
        .summary-grid { display: table; width: 100%; margin-bottom: 20px; }
        .summary-item { display: table-cell; width: 25%; text-align: center; padding: 10px; border: 1px solid #e5e7eb; }
        .summary-item .label { font-size: 10px; color: #6b7280; text-transform: uppercase; font-weight: bold; }
        .summary-item .value { font-size: 18px; font-weight: bold; color: #1f2937; margin-top: 3px; }
        .summary-item .value.green { color: #16a34a; }
        .summary-item .value.red { color: #dc2626; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        table th { background: #f3f4f6; font-weight: bold; text-align: left; padding: 6px 8px; border-bottom: 2px solid #e5e7eb; font-size: 10px; text-transform: uppercase; color: #6b7280; }
        table td { padding: 5px 8px; border-bottom: 1px solid #f3f4f6; }
        table .text-right { text-align: right; }
        .tax-box { background: #f3f4f6; padding: 12px; margin-bottom: 10px; border-radius: 4px; }
        .tax-box strong { color: #1f2937; }
        .footer { text-align: center; font-size: 10px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 15px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>ReziApp — Rapport Fiscal {{ $year }}</h1>
        <p>Généré le {{ now()->format('d/m/Y à H:i') }} · République de Côte d'Ivoire</p>
    </div>

    {{-- Summary --}}
    <div class="summary-grid">
        <div class="summary-item">
            <div class="label">Revenus bruts</div>
            <div class="value green">{{ number_format($report['total_revenue'], 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="summary-item">
            <div class="label">Dépenses totales</div>
            <div class="value red">{{ number_format($report['total_expenses'], 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="summary-item">
            <div class="label">Résultat net</div>
            <div class="value {{ $report['net_income'] >= 0 ? 'green' : 'red' }}">{{ number_format($report['net_income'], 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="summary-item">
            <div class="label">Impôts estimés</div>
            <div class="value">{{ number_format($report['impot_foncier'] + $report['tva'], 0, ',', ' ') }} FCFA</div>
        </div>
    </div>

    {{-- Taxes --}}
    <div class="section">
        <h2>Fiscalité applicable</h2>
        <div class="tax-box">
            <strong>Impôt foncier (15% du revenu net) :</strong> {{ number_format($report['impot_foncier'], 0, ',', ' ') }} FCFA
        </div>
        <div class="tax-box">
            <strong>TVA (18% du chiffre d'affaires) :</strong> {{ number_format($report['tva'], 0, ',', ' ') }} FCFA
        </div>
    </div>

    {{-- Monthly --}}
    <div class="section">
        <h2>Répartition mensuelle</h2>
        <table>
            <thead>
                <tr>
                    <th>Mois</th>
                    <th class="text-right">Revenus</th>
                    <th class="text-right">Dépenses</th>
                    <th class="text-right">Net</th>
                </tr>
            </thead>
            <tbody>
                @php $months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre']; @endphp
                @foreach($report['by_month'] as $m => $data)
                @php $net = $data['revenue'] - $data['expenses']; @endphp
                <tr>
                    <td>{{ $months[$m - 1] ?? $m }}</td>
                    <td class="text-right">{{ number_format($data['revenue'], 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($data['expenses'], 0, ',', ' ') }}</td>
                    <td class="text-right" style="font-weight: bold; color: {{ $net >= 0 ? '#16a34a' : '#dc2626' }}">{{ number_format($net, 0, ',', ' ') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- By Residence --}}
    @if(!empty($report['by_residence']))
    <div class="section">
        <h2>Par résidence</h2>
        <table>
            <thead>
                <tr>
                    <th>Résidence</th>
                    <th class="text-right">Revenus</th>
                    <th class="text-right">Dépenses</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['by_residence'] as $res)
                <tr>
                    <td>{{ $res['name'] }}</td>
                    <td class="text-right">{{ number_format($res['revenue'], 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($res['expenses'], 0, ',', ' ') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Expenses by Category --}}
    @if(!empty($report['expenses_by_category']))
    <div class="section">
        <h2>Dépenses par catégorie</h2>
        <table>
            <thead>
                <tr>
                    <th>Catégorie</th>
                    <th class="text-right">Montant</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['expenses_by_category'] as $cat)
                <tr>
                    <td>{{ $cat['category'] }}</td>
                    <td class="text-right">{{ number_format($cat['total'], 0, ',', ' ') }} FCFA</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>Ce document est généré automatiquement par ReziApp — reziapp.ci</p>
        <p>Il ne constitue pas un document officiel. Consultez un expert-comptable pour vos obligations fiscales.</p>
    </div>
</body>
</html>
