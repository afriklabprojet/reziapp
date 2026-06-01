<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\DisputeActions;
use App\Models\Concerns\DisputePresentation;
use App\Models\Concerns\DisputeRelationsAndScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dispute extends Model
{
    use DisputeActions;
    use DisputePresentation;
    use DisputeRelationsAndScopes;
    use HasFactory;

    protected $fillable = [
        'reference',
        'booking_id',
        'opened_by',
        'against_user_id',
        'category',
        'priority',
        'title',
        'description',
        'evidence_files',
        'claimed_amount',
        'claim_justification',
        'status',
        'response',
        'response_evidence',
        'responded_at',
        'resolution_type',
        'resolution_details',
        'resolution_amount',
        'resolved_at',
        'assigned_to',
        'assigned_at',
        'response_deadline',
        'resolution_deadline',
    ];

    protected $casts = [
        'evidence_files' => 'array',
        'response_evidence' => 'array',
        'responded_at' => 'datetime',
        'resolved_at' => 'datetime',
        'assigned_at' => 'datetime',
        'response_deadline' => 'datetime',
        'resolution_deadline' => 'datetime',
        'claimed_amount' => 'decimal:2',
        'resolution_amount' => 'decimal:2',
    ];

}
