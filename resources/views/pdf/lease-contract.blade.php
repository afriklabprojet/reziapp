<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Contrat de bail – {{ $contract->reference }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 12px; line-height: 1.6; color: #0F0F0F; padding: 40px; }

        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; border-bottom: 3px solid #059669; padding-bottom: 20px; }
        .logo { font-size: 26px; font-weight: bold; color: #059669; letter-spacing: -1px; }
        .logo span { color: #f97316; }
        .doc-meta { text-align: right; }
        .doc-meta h1 { font-size: 18px; color: #0F0F0F; font-weight: bold; margin-bottom: 4px; }
        .doc-meta .ref { font-size: 11px; color: #6b7280; }
        .doc-meta .status { display: inline-block; margin-top: 6px; padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: bold; background: #d1fae5; color: #065f46; }

        .parties { display: flex; justify-content: space-between; margin: 30px 0; gap: 20px; }
        .party { flex: 1; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; }
        .party-role { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; font-weight: bold; margin-bottom: 8px; }
        .party-name { font-size: 14px; font-weight: bold; color: #111827; margin-bottom: 4px; }
        .party-detail { font-size: 11px; color: #6b7280; }

        h2 { font-size: 14px; font-weight: bold; color: #059669; border-bottom: 1px solid #d1fae5; padding-bottom: 6px; margin: 24px 0 12px; text-transform: uppercase; letter-spacing: 0.5px; }

        .info-grid { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; }
        .info-item { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px 14px; min-width: 45%; flex: 1; }
        .info-label { font-size: 10px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
        .info-value { font-size: 13px; font-weight: bold; color: #111827; }

        .financials { width: 100%; border-collapse: collapse; margin: 16px 0; }
        .financials th { background: #059669; color: white; padding: 10px 14px; font-size: 11px; text-align: left; }
        .financials td { padding: 10px 14px; border-bottom: 1px solid #f3f4f6; }
        .financials tr:last-child td { border-bottom: none; font-weight: bold; background: #f0fdf4; }
        .financials .amount { text-align: right; font-weight: bold; }

        .clause { margin-bottom: 16px; }
        .clause-title { font-size: 12px; font-weight: bold; color: #374151; margin-bottom: 4px; }
        .clause-text { font-size: 11px; color: #4b5563; line-height: 1.7; }

        .signatures { display: flex; justify-content: space-between; margin-top: 40px; gap: 30px; }
        .sig-box { flex: 1; border-top: 2px solid #d1d5db; padding-top: 12px; }
        .sig-role { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; margin-bottom: 6px; }
        .sig-name { font-size: 13px; font-weight: bold; color: #111827; }
        .sig-date { font-size: 11px; color: #6b7280; margin-top: 4px; }
        .sig-signed { color: #059669; font-size: 11px; font-weight: bold; margin-top: 2px; }
        .sig-pending { color: #d97706; font-size: 11px; font-style: italic; margin-top: 2px; }

        .footer { margin-top: 50px; border-top: 1px solid #e5e7eb; padding-top: 14px; font-size: 10px; color: #9ca3af; text-align: center; }

        .amenities-list { display: flex; flex-wrap: wrap; gap: 8px; }
        .amenity-tag { background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 4px; padding: 3px 10px; font-size: 10px; color: #065f46; }

        .alert-box { background: #fef3c7; border: 1px solid #fcd34d; border-radius: 6px; padding: 12px; margin: 16px 0; font-size: 11px; color: #92400e; }
    </style>
</head>
<body>

    {{-- En-tête --}}
    <div class="header">
        <div>
            <div class="logo">RE<span>Z</span>I</div>
            <div style="font-size: 10px; color: #6b7280; margin-top: 4px;">Plateforme immobilière Abidjan</div>
        </div>
        <div class="doc-meta">
            <h1>CONTRAT DE BAIL</h1>
            <div class="ref">Référence : {{ $contract->reference }}</div>
            <div class="status">{{ strtoupper($contract->status_label) }}</div>
        </div>
    </div>

    {{-- Parties --}}
    <div class="parties">
        <div class="party">
            <div class="party-role">Bailleur (Propriétaire)</div>
            <div class="party-name">{{ $contract->owner->name }}</div>
            <div class="party-detail">{{ $contract->owner->email }}</div>
            @if($contract->owner->phone)
                <div class="party-detail">{{ $contract->owner->phone }}</div>
            @endif
        </div>
        <div class="party">
            <div class="party-role">Locataire</div>
            <div class="party-name">{{ $contract->tenant->name }}</div>
            <div class="party-detail">{{ $contract->tenant->email }}</div>
            @if($contract->tenant->phone)
                <div class="party-detail">{{ $contract->tenant->phone }}</div>
            @endif
        </div>
    </div>

    {{-- Bien loué --}}
    <h2>1. Bien loué</h2>
    <div class="info-grid">
        <div class="info-item">
            <div class="info-label">Résidence</div>
            <div class="info-value">{{ $contract->residence->title }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Adresse</div>
            <div class="info-value">{{ $contract->residence->address }}, {{ $contract->residence->commune }}</div>
        </div>
        @if($contract->residence->surface_area)
        <div class="info-item">
            <div class="info-label">Surface</div>
            <div class="info-value">{{ $contract->residence->surface_area }} m²</div>
        </div>
        @endif
        @if($contract->residence->bedrooms)
        <div class="info-item">
            <div class="info-label">Chambres</div>
            <div class="info-value">{{ $contract->residence->bedrooms }}</div>
        </div>
        @endif
    </div>

    {{-- Durée du bail --}}
    <h2>2. Durée du bail</h2>
    <div class="info-grid">
        <div class="info-item">
            <div class="info-label">Type de bail</div>
            <div class="info-value">
                @switch($contract->lease_type)
                    @case('short_term') Location court terme @break
                    @case('monthly') Location mensuelle @break
                    @case('fixed_term') Location durée déterminée @break
                    @default {{ $contract->lease_type }}
                @endswitch
            </div>
        </div>
        <div class="info-item">
            <div class="info-label">Date d'entrée</div>
            <div class="info-value">{{ $contract->start_date->format('d/m/Y') }}</div>
        </div>
        @if($contract->end_date)
        <div class="info-item">
            <div class="info-label">Date de fin</div>
            <div class="info-value">{{ $contract->end_date->format('d/m/Y') }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Durée</div>
            <div class="info-value">{{ $contract->duration_in_months }} mois</div>
        </div>
        @endif
    </div>

    {{-- Conditions financières --}}
    <h2>3. Conditions financières</h2>
    <table class="financials">
        <tr>
            <th>Désignation</th>
            <th style="text-align:right;">Montant (FCFA)</th>
        </tr>
        <tr>
            <td>{{ $contract->lease_type === 'short_term' ? 'Tarif par nuit' : 'Montant mensuel de location' }}</td>
            <td class="amount">{{ number_format($contract->monthly_rent, 0, ',', ' ') }} FCFA</td>
        </tr>
        @if($contract->charges_amount)
        <tr>
            <td>{{ $contract->lease_type === 'short_term' ? 'Frais de ménage' : 'Charges mensuelles' }}</td>
            <td class="amount">{{ number_format($contract->charges_amount, 0, ',', ' ') }} FCFA</td>
        </tr>
        <tr>
            <td><strong>{{ $contract->lease_type === 'short_term' ? 'Total par nuit' : 'Total mensuel' }}</strong></td>
            <td class="amount"><strong>{{ number_format($contract->monthly_rent + $contract->charges_amount, 0, ',', ' ') }} FCFA</strong></td>
        </tr>
        @endif
        <tr>
            <td>Dépôt de garantie</td>
            <td class="amount">{{ number_format($contract->deposit_amount, 0, ',', ' ') }} FCFA</td>
        </tr>
    </table>

    @if($contract->payment_day)
    <div class="clause">
        <div class="clause-text">Le montant de location est dû et payable le <strong>{{ $contract->payment_day }}</strong> de chaque mois.</div>
    </div>
    @endif

    {{-- Clauses particulières --}}
    @php $sectionNum = 4; @endphp
    @if($contract->included_services && count($contract->included_services))
    <h2>{{ $sectionNum }}. Services inclus</h2>
    <div class="clause">
        <div class="clause-text">Les services suivants sont inclus dans la location : <strong>{{ implode(', ', $contract->included_services) }}</strong>.</div>
    </div>
    @php $sectionNum++; @endphp
    @endif

    @if($contract->special_clauses)
    <h2>{{ $sectionNum }}. Clauses particulières</h2>
    <div class="clause">
        <div class="clause-text">{{ $contract->special_clauses }}</div>
    </div>
    @php $sectionNum++; @endphp
    @endif

    {{-- Clauses légales --}}
    <h2>{{ $sectionNum }}. Dispositions légales</h2>

    <div class="clause">
        <div class="clause-title">Obligations du bailleur</div>
        <div class="clause-text">Le bailleur s'engage à délivrer le logement en bon état d'usage et de réparation, à assurer la jouissance paisible du logement, et à entretenir les équipements mentionnés au contrat.</div>
    </div>

    <div class="clause">
        <div class="clause-title">Obligations du locataire</div>
        <div class="clause-text">Le locataire s'engage à payer le montant de location et les charges aux termes convenus, à user paisiblement des locaux, à ne pas transformer le logement sans l'accord du bailleur, et à restituer le logement en bon état.</div>
    </div>

    <div class="clause">
        <div class="clause-title">Dépôt de garantie</div>
        <div class="clause-text">Le dépôt de garantie sera restitué dans un délai maximum de 30 jours après la restitution des clés, déduction faite, le cas échéant, des sommes justifiées pour couvrir les dégradations constatées lors de l'état des lieux de sortie.</div>
    </div>

    <div class="clause">
        <div class="clause-title">Résiliation</div>
        <div class="clause-text">Chaque partie peut résilier le présent contrat avec un préavis d'un mois pour les locations meublées. La résiliation doit être notifiée par écrit.</div>
    </div>

    <div class="alert-box">
        Ce contrat est soumis aux lois et règlements en vigueur en République de Côte d'Ivoire. En cas de litige, les parties conviennent de rechercher une solution à l'amiable avant tout recours judiciaire.
    </div>

    {{-- Signatures --}}
    <h2>Signatures</h2>
    <div class="signatures">
        <div class="sig-box">
            <div class="sig-role">Bailleur</div>
            <div class="sig-name">{{ $contract->owner->name }}</div>
            @if($contract->owner_signed_at)
                <div class="sig-signed">✓ Signé le {{ $contract->owner_signed_at->format('d/m/Y à H:i') }}</div>
            @else
                <div class="sig-pending">En attente de signature</div>
            @endif
        </div>
        <div class="sig-box">
            <div class="sig-role">Locataire</div>
            <div class="sig-name">{{ $contract->tenant->name }}</div>
            @if($contract->tenant_signed_at)
                <div class="sig-signed">✓ Signé le {{ $contract->tenant_signed_at->format('d/m/Y à H:i') }}</div>
            @else
                <div class="sig-pending">En attente de signature</div>
            @endif
        </div>
    </div>

    {{-- Pied de page --}}
    <div class="footer">
        Document généré par Rezi Studio Meublé Faya – Plateforme immobilière Abidjan | {{ now()->format('d/m/Y à H:i') }}<br>
        Référence : {{ $contract->reference }} | Ce document constitue un contrat légalement contraignant.
    </div>

</body>
</html>
