<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $data['title'] ?? 'Rapport de performance' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
        }

        .container {
            padding: 30px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #10b981;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #10b981;
            margin-bottom: 5px;
        }

        .title {
            font-size: 18px;
            color: #333;
            margin-top: 10px;
        }

        .subtitle {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .section {
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #10b981;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e5e7eb;
        }

        .info-box {
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .info-label {
            color: #666;
        }

        .info-value {
            font-weight: bold;
            color: #333;
        }

        .kpi-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .kpi-box {
            display: table-cell;
            width: 25%;
            padding: 15px;
            text-align: center;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
        }

        .kpi-value {
            font-size: 24px;
            font-weight: bold;
            color: #10b981;
            margin-bottom: 5px;
        }

        .kpi-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background-color: #f9fafb;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            color: #666;
        }

        td {
            font-size: 11px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total-row {
            font-weight: bold;
            background-color: #f0fdf4;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #999;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }

        .badge-green {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge-yellow {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-gray {
            background-color: #f3f4f6;
            color: #4b5563;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- En-tête -->
        <div class="header">
            <div class="logo">ReziApp</div>
            <div class="title">{{ $data['title'] ?? 'Rapport de performance' }}</div>
            <div class="subtitle">
                Période: {{ $data['period']['start'] ?? '' }} - {{ $data['period']['end'] ?? '' }}
            </div>
        </div>

        <!-- Informations propriétaire -->
        <div class="section">
            <div class="section-title">Informations du compte</div>
            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Propriétaire:</span>
                    <span class="info-value">{{ $data['owner']['name'] ?? '' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">{{ $data['owner']['email'] ?? '' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date du rapport:</span>
                    <span class="info-value">{{ now()->format('d/m/Y H:i') }}</span>
                </div>
            </div>
        </div>

        <!-- KPIs -->
        <div class="section">
            <div class="section-title">Indicateurs clés de performance</div>
            <div class="kpi-grid">
                <div class="kpi-box">
                    <div class="kpi-value">{{ number_format($stats['revenue']['confirmed'] ?? 0, 0, ',', ' ') }}</div>
                    <div class="kpi-label">Revenus (FCFA)</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-value">{{ $occupancy['rate'] ?? 0 }}%</div>
                    <div class="kpi-label">Taux d'occupation</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-value">{{ number_format($stats['overview']['total_views'] ?? 0, 0, ',', ' ') }}
                    </div>
                    <div class="kpi-label">Vues totales</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-value">{{ $stats['conversion']['overall'] ?? 0 }}%</div>
                    <div class="kpi-label">Taux de conversion</div>
                </div>
            </div>
        </div>

        <!-- Résumé -->
        @if ($type === 'summary' && isset($data['summary']))
            <div class="section">
                <div class="section-title">Résumé de la période</div>
                <table>
                    <thead>
                        <tr>
                            <th>Métrique</th>
                            <th class="text-right">Valeur</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data['summary'] as $item)
                            <tr>
                                <td>{{ $item['metric'] }}</td>
                                <td class="text-right">{{ $item['value'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Détail par résidence -->
        @if ($type === 'detailed' && isset($data['rows']))
            <div class="section">
                <div class="section-title">Performance par résidence</div>
                <table>
                    <thead>
                        <tr>
                            <th>Résidence</th>
                            <th>Commune</th>
                            <th class="text-center">Vues</th>
                            <th class="text-center">Contacts</th>
                            <th class="text-center">Résa.</th>
                            <th class="text-right">Revenus</th>
                            <th class="text-center">Conv.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data['rows'] as $row)
                            <tr>
                                <td>{{ Str::limit($row['residence'], 25) }}</td>
                                <td>{{ $row['commune'] }}</td>
                                <td class="text-center">{{ number_format($row['views'], 0, ',', ' ') }}</td>
                                <td class="text-center">{{ $row['contacts'] }}</td>
                                <td class="text-center">{{ $row['bookings'] }}</td>
                                <td class="text-right">{{ number_format($row['revenue'], 0, ',', ' ') }}</td>
                                <td class="text-center">
                                    <span
                                        class="badge {{ floatval($row['conversion_rate']) >= 5 ? 'badge-green' : (floatval($row['conversion_rate']) >= 2 ? 'badge-yellow' : 'badge-gray') }}">
                                        {{ $row['conversion_rate'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                        <tr class="total-row">
                            <td colspan="2">TOTAL</td>
                            <td class="text-center">{{ number_format($data['totals']['views'], 0, ',', ' ') }}</td>
                            <td class="text-center">{{ $data['totals']['contacts'] }}</td>
                            <td class="text-center">{{ $data['totals']['bookings'] }}</td>
                            <td class="text-right">{{ number_format($data['totals']['revenue'], 0, ',', ' ') }}</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Contacts par statut -->
        <div class="section">
            <div class="section-title">Répartition des contacts</div>
            <table>
                <thead>
                    <tr>
                        <th>Statut</th>
                        <th class="text-center">Nombre</th>
                        <th class="text-right">Pourcentage</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $contactsTotal = array_sum($stats['contacts']['by_status'] ?? []);
                    @endphp
                    @foreach ($stats['contacts']['by_status'] ?? [] as $status => $count)
                        <tr>
                            <td>
                                @switch($status)
                                    @case('pending')
                                        En attente
                                    @break

                                    @case('viewed')
                                        Vus
                                    @break

                                    @case('responded')
                                        Répondus
                                    @break

                                    @case('closed')
                                        Fermés
                                    @break

                                    @default
                                        {{ ucfirst($status) }}
                                @endswitch
                            </td>
                            <td class="text-center">{{ $count }}</td>
                            <td class="text-right">
                                {{ $contactsTotal > 0 ? round(($count / $contactsTotal) * 100, 1) : 0 }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Top résidences -->
        @if (!empty($stats['top_residences']))
            <div class="section">
                <div class="section-title">Top 5 résidences</div>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Résidence</th>
                            <th class="text-center">Vues</th>
                            <th class="text-right">Revenus (FCFA)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($stats['top_residences'] as $index => $residence)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ Str::limit($residence['name'], 35) }}</td>
                                <td class="text-center">{{ number_format($residence['views'], 0, ',', ' ') }}</td>
                                <td class="text-right">{{ number_format($residence['revenue'], 0, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Pied de page -->
        <div class="footer">
            <p>Ce rapport a été généré automatiquement par ReziApp le {{ now()->format('d/m/Y à H:i') }}</p>
            <p>© {{ date('Y') }} ReziApp - Plateforme de location de résidences meublées</p>
        </div>
    </div>
</body>

</html>
