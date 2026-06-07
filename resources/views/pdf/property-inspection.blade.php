<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>État des lieux – {{ $inspection->reference }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; line-height: 1.5; color: #0F0F0F; padding: 35px; }

        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; border-bottom: 3px solid #3b82f6; padding-bottom: 16px; }
        .logo { font-size: 24px; font-weight: bold; color: #059669; }
        .logo span { color: #f97316; }
        .doc-meta { text-align: right; }
        .doc-meta h1 { font-size: 16px; font-weight: bold; color: #111827; }
        .doc-meta .type-badge { display: inline-block; margin-top: 5px; padding: 3px 12px; border-radius: 20px; font-size: 10px; font-weight: bold; }
        .type-check_in { background: #dbeafe; color: #1e40af; }
        .type-check_out { background: #fce7f3; color: #9d174d; }
        .type-periodic { background: #fef9c3; color: #854d0e; }
        .doc-meta .ref { font-size: 10px; color: #6b7280; margin-top: 4px; }

        .info-row { display: flex; gap: 12px; margin: 14px 0; }
        .info-box { flex: 1; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px 14px; }
        .info-box-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; margin-bottom: 3px; }
        .info-box-value { font-size: 12px; font-weight: bold; color: #111827; }

        h2 { font-size: 12px; font-weight: bold; color: #3b82f6; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e0f2fe; padding-bottom: 5px; margin: 20px 0 10px; }

        .parties { display: flex; gap: 16px; margin: 14px 0; }
        .party { flex: 1; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; }
        .party-role { font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; margin-bottom: 5px; }
        .party-name { font-size: 12px; font-weight: bold; color: #111827; }
        .party-detail { font-size: 10px; color: #6b7280; }

        /* Tableau des éléments par pièce */
        .room-section { margin-bottom: 20px; page-break-inside: avoid; }
        .room-title { background: #3b82f6; color: white; padding: 8px 12px; font-size: 12px; font-weight: bold; border-radius: 4px 4px 0 0; }
        .items-table { width: 100%; border-collapse: collapse; }
        .items-table th { background: #eff6ff; color: #1e40af; padding: 7px 10px; font-size: 10px; text-align: left; border-bottom: 1px solid #dbeafe; }
        .items-table td { padding: 7px 10px; border-bottom: 1px solid #f3f4f6; font-size: 10px; vertical-align: top; }
        .items-table tr:last-child td { border-bottom: none; }

        .condition-badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 9px; font-weight: bold; }
        .cond-new { background: #d1fae5; color: #065f46; }
        .cond-good { background: #dbeafe; color: #1e40af; }
        .cond-fair { background: #fef9c3; color: #854d0e; }
        .cond-damaged { background: #fee2e2; color: #991b1b; }
        .cond-na { background: #f3f4f6; color: #6b7280; }

        .repair-cost { font-weight: bold; color: #dc2626; font-size: 10px; }

        /* Compteurs et relevés */
        .meters-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .meters-table th { background: #f3f4f6; padding: 7px 12px; font-size: 10px; text-align: left; }
        .meters-table td { padding: 7px 12px; border-bottom: 1px solid #f3f4f6; font-size: 10px; }

        .summary-box { background: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; padding: 14px; margin: 16px 0; }
        .summary-box h3 { font-size: 12px; color: #92400e; margin-bottom: 8px; }
        .summary-total { font-size: 16px; font-weight: bold; color: #dc2626; }

        .signatures-section { display: flex; gap: 24px; margin-top: 30px; }
        .sig-area { flex: 1; border: 1px solid #d1d5db; border-radius: 6px; padding: 14px; }
        .sig-role-title { font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; margin-bottom: 6px; }
        .sig-person { font-size: 12px; font-weight: bold; color: #111827; }
        .sig-status-signed { color: #059669; font-size: 10px; margin-top: 4px; font-weight: bold; }
        .sig-status-pending { color: #d97706; font-size: 10px; margin-top: 4px; font-style: italic; }
        .sig-space { height: 40px; border-bottom: 1px dashed #d1d5db; margin: 10px 0 4px; }

        .observations { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; margin: 12px 0; font-size: 11px; color: #374151; line-height: 1.7; }

        .footer { margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 12px; text-align: center; font-size: 9px; color: #9ca3af; }

        .keys-grid { display: flex; flex-wrap: wrap; gap: 10px; margin: 8px 0; }
        .key-item { background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 4px; padding: 4px 10px; font-size: 10px; }
    </style>
</head>
<body>

    {{-- En-tête --}}
    <div class="header">
        <div>
            <div class="logo">RE<span>Z</span>I</div>
            <div style="font-size: 10px; color: #9ca3af; margin-top: 3px;">Plateforme immobilière Abidjan</div>
        </div>
        <div class="doc-meta">
            <h1>ÉTAT DES LIEUX</h1>
            <span class="type-badge type-{{ $inspection->type }}">
                @switch($inspection->type)
                    @case('check_in') ➜ ENTRÉE @break
                    @case('check_out') ← SORTIE @break
                    @case('periodic') ○ PÉRIODIQUE @break
                @endswitch
            </span>
            <div class="ref">Réf. : {{ $inspection->reference }}</div>
            <div class="ref">Date : {{ $inspection->inspection_date->format('d/m/Y') }}</div>
        </div>
    </div>

    {{-- Informations générales --}}
    <div class="info-row">
        <div class="info-box">
            <div class="info-box-label">Résidence</div>
            <div class="info-box-value">{{ $inspection->residence->title }}</div>
        </div>
        <div class="info-box">
            <div class="info-box-label">Adresse</div>
            <div class="info-box-value">{{ $inspection->residence->address }}, {{ $inspection->residence->commune }}</div>
        </div>
        <div class="info-box">
            <div class="info-box-label">Statut</div>
            <div class="info-box-value">{{ ucfirst($inspection->status) }}</div>
        </div>
    </div>

    {{-- Parties --}}
    <h2>Parties présentes</h2>
    <div class="parties">
        <div class="party">
            <div class="party-role">Propriétaire / Bailleur</div>
            <div class="party-name">{{ $inspection->owner->name }}</div>
            <div class="party-detail">{{ $inspection->owner->email }}</div>
        </div>
        @if($inspection->tenant)
        <div class="party">
            <div class="party-role">Locataire</div>
            <div class="party-name">{{ $inspection->tenant->name }}</div>
            <div class="party-detail">{{ $inspection->tenant->email }}</div>
        </div>
        @endif
        @if($inspection->agent_name)
        <div class="party">
            <div class="party-role">Mandataire / Agent</div>
            <div class="party-name">{{ $inspection->agent_name }}</div>
        </div>
        @endif
    </div>

    {{-- Relevés de compteurs --}}
    @if($inspection->electricity_index !== null || $inspection->water_index !== null || $inspection->gas_index !== null)
    <h2>Relevés de compteurs</h2>
    <table class="meters-table">
        <tr>
            <th>Type</th>
            <th>Index relevé</th>
            <th>Unité</th>
        </tr>
        @if($inspection->electricity_index !== null)
        <tr>
            <td>⚡ Électricité</td>
            <td>{{ number_format($inspection->electricity_index, 2) }}</td>
            <td>kWh</td>
        </tr>
        @endif
        @if($inspection->water_index !== null)
        <tr>
            <td>💧 Eau</td>
            <td>{{ number_format($inspection->water_index, 2) }}</td>
            <td>m³</td>
        </tr>
        @endif
        @if($inspection->gas_index !== null)
        <tr>
            <td>🔥 Gaz</td>
            <td>{{ number_format($inspection->gas_index, 2) }}</td>
            <td>m³</td>
        </tr>
        @endif
    </table>
    @endif

    {{-- Remise des clés --}}
    @if($inspection->keys_handed_over)
    <h2>Remise des clés</h2>
    <div class="keys-grid">
        @if($inspection->keys_count)
            <div class="key-item">🔑 {{ $inspection->keys_count }} clé(s) principale(s)</div>
        @endif
        @if($inspection->keys_detail && count($inspection->keys_detail) > 0)
            @foreach($inspection->keys_detail as $key)
                <div class="key-item">{{ $key }}</div>
            @endforeach
        @endif
    </div>
    @endif

    {{-- État des pièces --}}
    <h2>État des pièces et équipements</h2>

    @php $itemsByRoom = $inspection->itemsByRoom(); @endphp

    @if($itemsByRoom->isEmpty())
        <p style="font-size: 11px; color: #9ca3af; font-style: italic;">Aucun élément enregistré.</p>
    @else
        @foreach($itemsByRoom as $room => $items)
        <div class="room-section">
            <div class="room-title">{{ $room }}</div>
            <table class="items-table">
                <tr>
                    <th style="width: 30%;">Élément</th>
                    <th style="width: 20%;">État</th>
                    <th style="width: 35%;">Observations</th>
                    <th style="width: 15%;">Coût estimé</th>
                </tr>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item->element }}</td>
                    <td>
                        <span class="condition-badge cond-{{ $item->condition ?? 'na' }}">
                            @switch($item->condition)
                                @case('new') Neuf @break
                                @case('good') Bon @break
                                @case('fair') Passable @break
                                @case('damaged') Abîmé @break
                                @default N/A
                            @endswitch
                        </span>
                    </td>
                    <td>{{ $item->notes ?? '—' }}</td>
                    <td>
                        @if($item->repair_estimate)
                            <span class="repair-cost">{{ number_format($item->repair_estimate, 0, ',', ' ') }} FCFA</span>
                        @else
                            —
                        @endif
                    </td>
                </tr>
                @endforeach
            </table>
        </div>
        @endforeach
    @endif

    {{-- Résumé des coûts de réparation --}}
    @php
        $totalRepairCost = $inspection->items->whereNotNull('repair_estimate')->sum('repair_estimate');
        $damagedCount = $inspection->items->where('condition', 'damaged')->count();
    @endphp
    @if($totalRepairCost > 0)
    <div class="summary-box">
        <h3>⚠️ Récapitulatif des dégradations</h3>
        <p>Éléments abîmés : <strong>{{ $damagedCount }}</strong></p>
        <p style="margin-top: 8px;">Coût total estimé de remise en état : <span class="summary-total">{{ number_format($totalRepairCost, 0, ',', ' ') }} FCFA</span></p>
    </div>
    @endif

    {{-- Observations générales --}}
    @if($inspection->general_observations)
    <h2>Observations générales</h2>
    <div class="observations">{{ $inspection->general_observations }}</div>
    @endif

    {{-- Signatures --}}
    <h2>Signatures et approbations</h2>
    <div class="signatures-section">
        <div class="sig-area">
            <div class="sig-role-title">Propriétaire / Bailleur</div>
            <div class="sig-person">{{ $inspection->owner->name }}</div>
            @if($inspection->owner_signed_at)
                <div class="sig-status-signed">✓ Signé le {{ $inspection->owner_signed_at->format('d/m/Y à H:i') }}</div>
            @else
                <div class="sig-space"></div>
                <div class="sig-status-pending">Signature à apposer</div>
            @endif
        </div>

        @if($inspection->tenant)
        <div class="sig-area">
            <div class="sig-role-title">Locataire</div>
            <div class="sig-person">{{ $inspection->tenant->name }}</div>
            @if($inspection->tenant_signed_at)
                <div class="sig-status-signed">✓ Signé le {{ $inspection->tenant_signed_at->format('d/m/Y à H:i') }}</div>
            @else
                <div class="sig-space"></div>
                <div class="sig-status-pending">Signature à apposer</div>
            @endif
        </div>
        @endif

        @if($inspection->agent_name)
        <div class="sig-area">
            <div class="sig-role-title">Mandataire</div>
            <div class="sig-person">{{ $inspection->agent_name }}</div>
            <div class="sig-space"></div>
        </div>
        @endif
    </div>

    {{-- Pied de page --}}
    <div class="footer">
        Rezi Studio Meublé Faya – Plateforme immobilière Abidjan | Document généré le {{ now()->format('d/m/Y à H:i') }}<br>
        Référence : {{ $inspection->reference }} | Ce document fait foi d'état des lieux et engage les deux parties.
    </div>

</body>
</html>
