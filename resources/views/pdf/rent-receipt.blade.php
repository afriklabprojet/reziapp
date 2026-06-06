<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu de location – {{ $receipt->reference }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 12px; line-height: 1.6; color: #0F0F0F; padding: 50px; }

        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; }
        .logo { font-size: 28px; font-weight: bold; color: #059669; }
        .logo span { color: #f97316; }
        .logo-sub { font-size: 10px; color: #9ca3af; margin-top: 4px; }
        .doc-title { text-align: right; }
        .doc-title h1 { font-size: 22px; font-weight: bold; color: #111827; letter-spacing: 2px; }
        .doc-title .ref { font-size: 11px; color: #6b7280; margin-top: 4px; }

        .divider { border: none; border-top: 3px solid #059669; margin: 0 0 30px; }

        .statement-box { background: #f0fdf4; border: 2px solid #86efac; border-radius: 10px; padding: 24px; margin: 30px 0; text-align: center; }
        .statement-box h2 { font-size: 16px; color: #166534; margin-bottom: 8px; }
        .statement-box .amount-big { font-size: 32px; font-weight: bold; color: #059669; margin: 10px 0; }
        .statement-box .period { font-size: 14px; color: #374151; }

        .parties { display: flex; justify-content: space-between; gap: 30px; margin: 30px 0; }
        .party { flex: 1; }
        .party-role { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; font-weight: bold; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; margin-bottom: 10px; }
        .party-name { font-size: 14px; font-weight: bold; color: #111827; margin-bottom: 4px; }
        .party-detail { font-size: 11px; color: #6b7280; }

        .details-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .details-table th { background: #059669; color: white; padding: 10px 16px; font-size: 11px; text-align: left; }
        .details-table td { padding: 10px 16px; border-bottom: 1px solid #f3f4f6; }
        .details-table tr:last-child td { font-weight: bold; background: #f0fdf4; border-bottom: none; }
        .details-table .amount { text-align: right; font-weight: bold; }

        .property-info { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin: 20px 0; }
        .property-info .prop-title { font-size: 13px; font-weight: bold; color: #111827; margin-bottom: 6px; }
        .property-info .prop-detail { font-size: 11px; color: #6b7280; }

        .declaration { font-size: 12px; color: #374151; line-height: 1.8; margin: 24px 0; padding: 16px; background: #fafafa; border-left: 4px solid #059669; }

        .footer-sigs { display: flex; justify-content: space-between; margin-top: 50px; gap: 40px; }
        .sig-block { flex: 1; border-top: 2px solid #d1d5db; padding-top: 10px; }
        .sig-label { font-size: 10px; text-transform: uppercase; color: #9ca3af; letter-spacing: 0.5px; margin-bottom: 6px; }
        .sig-name { font-size: 13px; font-weight: bold; color: #111827; }
        .sig-date { font-size: 11px; color: #059669; margin-top: 4px; }

        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 14px; }

        .stamp { text-align: center; margin: 20px 0; }
        .stamp-inner { display: inline-block; border: 3px solid #059669; border-radius: 50%; width: 80px; height: 80px; line-height: 80px; font-size: 11px; font-weight: bold; color: #059669; text-transform: uppercase; }
    </style>
</head>
<body>

    {{-- En-tête --}}
    <div class="header">
        <div>
            <div class="logo">RE<span>Z</span>I</div>
            <div class="logo-sub">Plateforme immobilière Abidjan</div>
        </div>
        <div class="doc-title">
            <h1>REÇU DE LOCATION</h1>
            <div class="ref">Référence : {{ $receipt->reference }}</div>
            <div class="ref">Émise le : {{ $receipt->created_at->format('d/m/Y') }}</div>
        </div>
    </div>
    <hr class="divider">

    {{-- Déclaration centrale --}}
    <div class="statement-box">
        <h2>Je soussigné(e), {{ $receipt->owner->name }}, propriétaire</h2>
        <div class="period">déclare avoir reçu de <strong>{{ $receipt->tenant->name }}</strong></div>
        <div class="amount-big">{{ number_format($receipt->total_amount, 0, ',', ' ') }} FCFA</div>
        <div class="period">au titre de la location du logement ci-dessous désigné</div>
        <div class="period" style="margin-top: 8px; font-weight: bold;">
            pour la période du {{ $receipt->period_start->format('d/m/Y') }} au {{ $receipt->period_end->format('d/m/Y') }}
        </div>
    </div>

    {{-- Parties --}}
    <div class="parties">
        <div class="party">
            <div class="party-role">Bailleur</div>
            <div class="party-name">{{ $receipt->owner->name }}</div>
            <div class="party-detail">{{ $receipt->owner->email }}</div>
            @if($receipt->owner->phone)
                <div class="party-detail">{{ $receipt->owner->phone }}</div>
            @endif
        </div>
        <div class="party">
            <div class="party-role">Locataire</div>
            <div class="party-name">{{ $receipt->tenant->name }}</div>
            <div class="party-detail">{{ $receipt->tenant->email }}</div>
            @if($receipt->tenant->phone)
                <div class="party-detail">{{ $receipt->tenant->phone }}</div>
            @endif
        </div>
    </div>

    {{-- Bien loué --}}
    <div class="property-info">
        <div class="prop-title">Logement concerné</div>
        <div class="prop-detail">{{ $receipt->residence->title }}</div>
        <div class="prop-detail">{{ $receipt->residence->address }}, {{ $receipt->residence->commune }}, Abidjan</div>
    </div>

    {{-- Détail des sommes --}}
    <table class="details-table">
        <tr>
            <th>Désignation</th>
            <th style="text-align:right;">Montant (FCFA)</th>
        </tr>
        <tr>
            <td>Montant principal de location</td>
            <td class="amount">{{ number_format($receipt->rent_amount, 0, ',', ' ') }} FCFA</td>
        </tr>
        @if($receipt->charges_amount > 0)
        <tr>
            <td>Charges locatives</td>
            <td class="amount">{{ number_format($receipt->charges_amount, 0, ',', ' ') }} FCFA</td>
        </tr>
        @if($receipt->charges_detail)
            @foreach($receipt->charges_detail as $charge)
            <tr>
                <td style="padding-left: 30px; color: #6b7280; font-size: 11px;">- {{ $charge['label'] ?? 'Charge' }}</td>
                <td class="amount" style="color: #6b7280; font-size: 11px;">{{ number_format($charge['amount'] ?? 0, 0, ',', ' ') }} FCFA</td>
            </tr>
            @endforeach
        @endif
        @endif
        <tr>
            <td>TOTAL PERÇU</td>
            <td class="amount">{{ number_format($receipt->total_amount, 0, ',', ' ') }} FCFA</td>
        </tr>
    </table>

    @if($receipt->payment_method)
    <p style="font-size: 11px; color: #6b7280; margin: 8px 0;">
        Mode de règlement : <strong>{{ $receipt->payment_method }}</strong>
        @if($receipt->payment_reference) — Réf. {{ $receipt->payment_reference }} @endif
    </p>
    @endif

    {{-- Déclaration légale --}}
    <div class="declaration">
        Et donne quittance au locataire {{ $receipt->tenant->name }} pour la somme de
        <strong>{{ number_format($receipt->total_amount, 0, ',', ' ') }} francs CFA</strong>
        représentant le montant de location et les charges du {{ $receipt->period_start->format('d/m/Y') }} au {{ $receipt->period_end->format('d/m/Y') }},
        sous réserve de tous mes autres droits.
    </div>

    {{-- Signatures --}}
    <div class="footer-sigs">
        <div class="sig-block">
            <div class="sig-label">Signature du bailleur</div>
            <div class="sig-name">{{ $receipt->owner->name }}</div>
            <div class="sig-date">{{ now()->format('d/m/Y') }}</div>
        </div>
        <div class="stamp">
            <div class="stamp-inner">ReziApp</div>
        </div>
        <div class="sig-block" style="text-align: right;">
            <div class="sig-label">Cachet électronique</div>
            <div class="sig-name" style="font-size: 11px; color: #6b7280;">Document généré automatiquement</div>
            <div class="sig-date">{{ $receipt->reference }}</div>
        </div>
    </div>

    {{-- Pied de page --}}
    <div class="footer">
        ReziApp – Plateforme immobilière Abidjan | Document généré le {{ now()->format('d/m/Y à H:i') }}<br>
        Ce document fait foi de paiement de la location pour la période indiquée.
    </div>

</body>
</html>
