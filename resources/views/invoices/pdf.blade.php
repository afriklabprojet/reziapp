<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            padding: 40px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #059669;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-title h1 {
            font-size: 24px;
            color: #059669;
            margin-bottom: 5px;
        }

        .invoice-number {
            font-size: 14px;
            color: #666;
        }

        .parties {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }

        .party {
            width: 45%;
        }

        .party-title {
            font-size: 10px;
            font-weight: bold;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .party-name {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .party-details {
            font-size: 11px;
            color: #666;
        }

        .invoice-details {
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .invoice-details table {
            width: 100%;
        }

        .invoice-details td {
            padding: 5px 10px;
        }

        .invoice-details .label {
            color: #666;
            font-size: 11px;
        }

        .invoice-details .value {
            font-weight: bold;
            text-align: right;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .items-table th {
            background-color: #059669;
            color: white;
            padding: 12px 15px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }

        .items-table th:last-child,
        .items-table td:last-child {
            text-align: right;
        }

        .items-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        .items-table tbody tr:last-child td {
            border-bottom: none;
        }

        .totals {
            float: right;
            width: 300px;
        }

        .totals table {
            width: 100%;
        }

        .totals td {
            padding: 8px 0;
        }

        .totals .label {
            color: #666;
        }

        .totals .value {
            text-align: right;
            font-weight: bold;
        }

        .totals .total-row {
            border-top: 2px solid #059669;
        }

        .totals .total-row td {
            padding-top: 15px;
            font-size: 16px;
            color: #059669;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-paid {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-sent {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-draft {
            background-color: #f3f4f6;
            color: #4b5563;
        }

        .status-overdue {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .notes {
            margin-top: 40px;
            padding: 20px;
            background-color: #f8fafc;
            border-radius: 8px;
        }

        .notes-title {
            font-size: 11px;
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .notes-content {
            font-size: 11px;
            color: #666;
            white-space: pre-line;
        }

        .footer {
            position: fixed;
            bottom: 40px;
            left: 40px;
            right: 40px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>

<body>
    <!-- En-tête -->
    <table style="width: 100%; margin-bottom: 40px;">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <div class="logo">REZI</div>
                <div style="color: #666; font-size: 11px; margin-top: 5px;">
                    Votre résidence meublée idéale
                </div>
            </td>
            <td style="width: 50%; text-align: right; vertical-align: top;">
                <h1 style="font-size: 24px; color: #059669; margin: 0;">FACTURE</h1>
                <div style="color: #666; font-size: 14px; margin-top: 5px;">{{ $invoice->invoice_number }}</div>
                <div style="margin-top: 15px;">
                    <span class="status-badge status-{{ $invoice->status }}">
                        {{ $invoice->status_label }}
                    </span>
                </div>
            </td>
        </tr>
    </table>

    <!-- Parties -->
    <table style="width: 100%; margin-bottom: 40px;">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <div class="party-title">Émetteur</div>
                <div class="party-name">{{ $invoice->seller_name }}</div>
                <div class="party-details">
                    {{ $invoice->seller_address }}<br>
                    {{ $invoice->seller_email }}<br>
                    {{ $invoice->seller_phone }}<br>
                    @if ($invoice->seller_tax_id)
                        N° Fiscal: {{ $invoice->seller_tax_id }}
                    @endif
                </div>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <div class="party-title">Facturer à</div>
                <div class="party-name">{{ $invoice->client_name }}</div>
                <div class="party-details">
                    @if ($invoice->client_address)
                        {{ $invoice->client_address }}<br>
                    @endif
                    {{ $invoice->client_email }}<br>
                    @if ($invoice->client_phone)
                        {{ $invoice->client_phone }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- Détails de la facture -->
    <div class="invoice-details">
        <table>
            <tr>
                <td class="label">Date de facture</td>
                <td class="value">{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                <td class="label">Date d'échéance</td>
                <td class="value">{{ $invoice->due_date?->format('d/m/Y') ?? '-' }}</td>
            </tr>
            @if ($invoice->booking)
                <tr>
                    <td class="label">Réservation</td>
                    <td class="value">{{ $invoice->booking->reference }}</td>
                    <td class="label">Période</td>
                    <td class="value">
                        {{ $invoice->booking->check_in->format('d/m/Y') }} -
                        {{ $invoice->booking->check_out->format('d/m/Y') }}
                    </td>
                </tr>
            @endif
        </table>
    </div>

    <!-- Lignes de facture -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50%;">Description</th>
                <th style="width: 15%;">Quantité</th>
                <th style="width: 17%;">Prix unitaire</th>
                <th style="width: 18%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->line_items ?? [] as $item)
                <tr>
                    <td>
                        {{ $item['description'] }}
                        @if (!empty($item['reference']))
                            <br><span style="color: #999; font-size: 10px;">Réf: {{ $item['reference'] }}</span>
                        @endif
                    </td>
                    <td>{{ $item['quantity'] ?? 1 }}</td>
                    <td>{{ number_format($item['unit_price'] ?? 0, 0, ',', ' ') }} {{ $invoice->currency }}</td>
                    <td>{{ number_format($item['total'] ?? ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0), 0, ',', ' ') }}
                        {{ $invoice->currency }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totaux -->
    <div class="clearfix">
        <div class="totals">
            <table>
                <tr>
                    <td class="label">Sous-total HT</td>
                    <td class="value">{{ number_format($invoice->subtotal, 0, ',', ' ') }} {{ $invoice->currency }}
                    </td>
                </tr>
                @if ($invoice->tax_amount > 0)
                    <tr>
                        <td class="label">TVA ({{ $invoice->tax_rate }}%)</td>
                        <td class="value">{{ number_format($invoice->tax_amount, 0, ',', ' ') }}
                            {{ $invoice->currency }}</td>
                    </tr>
                @endif
                @if ($invoice->discount_amount > 0)
                    <tr>
                        <td class="label">Remise</td>
                        <td class="value" style="color: #059669;">
                            -{{ number_format($invoice->discount_amount, 0, ',', ' ') }} {{ $invoice->currency }}</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td class="label">Total TTC</td>
                    <td class="value">{{ number_format($invoice->total, 0, ',', ' ') }} {{ $invoice->currency }}</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Notes -->
    @if ($invoice->notes || $invoice->terms)
        <div class="notes" style="margin-top: 80px;">
            @if ($invoice->notes)
                <div class="notes-title">Notes</div>
                <div class="notes-content">{{ $invoice->notes }}</div>
            @endif

            @if ($invoice->terms)
                <div class="notes-title" style="margin-top: 15px;">Conditions</div>
                <div class="notes-content">{{ $invoice->terms }}</div>
            @endif
        </div>
    @endif

    <!-- Pied de page -->
    <div class="footer">
        <p>
            <strong>{{ config('rezi.company.name') }}</strong> -
            {{ config('rezi.company.address', 'Abidjan, Côte d\'Ivoire') }}<br>
            {{ config('rezi.company.email') }} | {{ config('rezi.company.website') }}<br>
            Merci de votre confiance !
        </p>
    </div>
</body>

</html>
