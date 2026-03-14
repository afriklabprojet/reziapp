<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Récapitulatif fiscal {{ $year }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }

        .container {
            padding: 25px;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #10b981;
        }

        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #10b981;
        }

        .document-title {
            font-size: 20px;
            font-weight: bold;
            color: #1f2937;
            margin-top: 10px;
        }

        .document-subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-top: 5px;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #10b981;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #d1d5db;
        }

        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .info-row {
            display: table-row;
        }

        .info-label {
            display: table-cell;
            padding: 5px 0;
            color: #6b7280;
            width: 40%;
        }

        .info-value {
            display: table-cell;
            padding: 5px 0;
            font-weight: bold;
        }

        .summary-boxes {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .summary-box {
            display: table-cell;
            width: 25%;
            padding: 12px;
            text-align: center;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
        }

        .summary-box.highlight {
            background-color: #d1fae5;
            border-color: #10b981;
        }

        .summary-value {
            font-size: 18px;
            font-weight: bold;
            color: #1f2937;
        }

        .summary-box.highlight .summary-value {
            color: #065f46;
        }

        .summary-label {
            font-size: 9px;
            color: #6b7280;
            text-transform: uppercase;
            margin-top: 3px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 8px 10px;
            text-align: left;
            border: 1px solid #e5e7eb;
        }

        th {
            background-color: #f3f4f6;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            color: #6b7280;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total-row {
            font-weight: bold;
            background-color: #d1fae5;
        }

        .total-row td {
            border-top: 2px solid #10b981;
        }

        .amount-positive {
            color: #065f46;
        }

        .amount-tax {
            color: #b45309;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }

        .footer-note {
            font-size: 9px;
            color: #9ca3af;
            text-align: center;
            margin-bottom: 5px;
        }

        .legal-notice {
            background-color: #fef3c7;
            border: 1px solid #fcd34d;
            padding: 10px;
            font-size: 9px;
            color: #92400e;
            margin-top: 15px;
        }

        .legal-notice strong {
            display: block;
            margin-bottom: 3px;
        }

        .signature-area {
            margin-top: 30px;
            display: table;
            width: 100%;
        }

        .signature-box {
            display: table-cell;
            width: 50%;
            padding: 15px;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 5px;
            font-size: 9px;
            color: #6b7280;
        }

        .page-number {
            position: fixed;
            bottom: 10px;
            right: 25px;
            font-size: 9px;
            color: #9ca3af;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- En-tête officiel -->
        <div class="header">
            <div class="logo">REZI</div>
            <div class="document-title">RÉCAPITULATIF FISCAL ANNUEL</div>
            <div class="document-subtitle">Année {{ $year }}</div>
        </div>

        <!-- Informations du propriétaire -->
        <div class="section">
            <div class="section-title">Informations du déclarant</div>
            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">Nom complet :</span>
                    <span class="info-value">{{ $data['owner']['name'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Adresse email :</span>
                    <span class="info-value">{{ $data['owner']['email'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Téléphone :</span>
                    <span class="info-value">{{ $data['owner']['phone'] ?? 'Non renseigné' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Période fiscale :</span>
                    <span class="info-value">1er janvier {{ $year }} - 31 décembre {{ $year }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date d'édition :</span>
                    <span class="info-value">{{ $data['generated_at'] }}</span>
                </div>
            </div>
        </div>

        <!-- Résumé annuel -->
        <div class="section">
            <div class="section-title">Synthèse annuelle</div>
            <div class="summary-boxes">
                <div class="summary-box highlight">
                    <div class="summary-value">{{ number_format($data['fiscal']['gross_revenue'], 0, ',', ' ') }}</div>
                    <div class="summary-label">Revenus bruts (FCFA)</div>
                </div>
                <div class="summary-box">
                    <div class="summary-value">{{ $data['totals']['bookings'] }}</div>
                    <div class="summary-label">Réservations</div>
                </div>
                <div class="summary-box">
                    <div class="summary-value">{{ number_format($data['fiscal']['taxe_sejour_amount'], 0, ',', ' ') }}
                    </div>
                    <div class="summary-label">Taxe séjour (FCFA)</div>
                </div>
                <div class="summary-box highlight">
                    <div class="summary-value">{{ number_format($data['fiscal']['net_revenue'], 0, ',', ' ') }}</div>
                    <div class="summary-label">Revenus nets (FCFA)</div>
                </div>
            </div>
        </div>

        <!-- Détail mensuel -->
        <div class="section">
            <div class="section-title">Détail des revenus par mois</div>
            <table>
                <thead>
                    <tr>
                        <th>Mois</th>
                        <th class="text-center">Réservations</th>
                        <th class="text-right">Revenus bruts</th>
                        <th class="text-right">Taxe séjour (5%)</th>
                        <th class="text-right">Revenus nets</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data['monthly'] as $month)
                        <tr>
                            <td>{{ ucfirst($month['month']) }} {{ $year }}</td>
                            <td class="text-center">{{ $month['bookings'] }}</td>
                            <td class="text-right">{{ number_format($month['revenue'], 0, ',', ' ') }} F</td>
                            <td class="text-right amount-tax">
                                {{ number_format($month['revenue'] * 0.05, 0, ',', ' ') }} F</td>
                            <td class="text-right amount-positive">
                                {{ number_format($month['revenue'] * 0.95, 0, ',', ' ') }} F</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td><strong>TOTAL ANNUEL</strong></td>
                        <td class="text-center"><strong>{{ $data['totals']['bookings'] }}</strong></td>
                        <td class="text-right">
                            <strong>{{ number_format($data['fiscal']['gross_revenue'], 0, ',', ' ') }} F</strong></td>
                        <td class="text-right amount-tax">
                            <strong>{{ number_format($data['fiscal']['taxe_sejour_amount'], 0, ',', ' ') }} F</strong>
                        </td>
                        <td class="text-right amount-positive">
                            <strong>{{ number_format($data['fiscal']['net_revenue'], 0, ',', ' ') }} F</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mentions légales -->
        <div class="legal-notice">
            <strong>⚠️ Avertissement important</strong>
            Ce document est généré automatiquement par la plateforme REZI à titre informatif uniquement.
            Les montants indiqués sont des estimations basées sur les réservations enregistrées sur notre plateforme.
            La taxe de séjour est calculée à un taux indicatif de 5%. Pour vos déclarations fiscales officielles,
            veuillez consulter un expert-comptable agréé ou les services de la Direction Générale des Impôts
            de Côte d'Ivoire pour connaître les taux et obligations fiscales en vigueur.
        </div>

        <!-- Zone de signature -->
        <div class="signature-area">
            <div class="signature-box">
                <p>Fait à {{ config('rezi.company.city', 'Abidjan') }}, le {{ now()->format('d/m/Y') }}</p>
                <div class="signature-line">Signature du propriétaire</div>
            </div>
            <div class="signature-box">
                <p>Cachet REZI</p>
                <div class="signature-line">Pour la plateforme REZI</div>
            </div>
        </div>

        <!-- Pied de page -->
        <div class="footer">
            <p class="footer-note">Document généré automatiquement - Référence:
                REZI-FISCAL-{{ $year }}-{{ strtoupper(substr(md5($data['owner']['email'] . $year), 0, 8)) }}
            </p>
            <p class="footer-note">REZI - Plateforme de location de résidences meublées en Afrique de l'Ouest</p>
            <p class="footer-note">© {{ date('Y') }} REZI - Tous droits réservés</p>
        </div>
    </div>
</body>

</html>
