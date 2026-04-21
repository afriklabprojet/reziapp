<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AdminActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'action',
        'model_type',
        'model_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // Actions possibles
    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_DELETED = 'deleted';
    public const ACTION_APPROVED = 'approved';
    public const ACTION_REJECTED = 'rejected';
    public const ACTION_SUSPENDED = 'suspended';
    public const ACTION_RESTORED = 'restored';
    public const ACTION_EXPORTED = 'exported';
    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_SETTINGS_CHANGED = 'settings_changed';
    public const ACTION_BULK_ACTION = 'bulk_action';
    public const ACTION_NOTIFICATION_SENT = 'notification_sent';

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function model(): MorphTo
    {
        return $this->morphTo('model');
    }

    /**
     * Log une action admin
     */
    public static function log(
        string $action,
        string $description,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): self {
        return self::create([
            'admin_id' => Auth::id(),
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Scope pour filtrer par action
     */
    public function scopeOfAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope pour filtrer par type de modèle
     */
    public function scopeOfModelType($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    /**
     * Scope pour filtrer par admin
     */
    public function scopeByAdmin($query, int $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    /**
     * Actions disponibles avec labels FR
     */
    public static function getActionLabels(): array
    {
        return [
            self::ACTION_CREATED => 'Création',
            self::ACTION_UPDATED => 'Modification',
            self::ACTION_DELETED => 'Suppression',
            self::ACTION_APPROVED => 'Approbation',
            self::ACTION_REJECTED => 'Rejet',
            self::ACTION_SUSPENDED => 'Suspension',
            self::ACTION_RESTORED => 'Restauration',
            self::ACTION_EXPORTED => 'Export',
            self::ACTION_LOGIN => 'Connexion',
            self::ACTION_LOGOUT => 'Déconnexion',
            self::ACTION_SETTINGS_CHANGED => 'Paramètres modifiés',
            self::ACTION_BULK_ACTION => 'Action groupée',
            self::ACTION_NOTIFICATION_SENT => 'Notification envoyée',
        ];
    }

    /**
     * Couleur pour l'action
     */
    public function getActionColor(): string
    {
        return match ($this->action) {
            self::ACTION_CREATED => 'success',
            self::ACTION_APPROVED => 'success',
            self::ACTION_RESTORED => 'success',
            self::ACTION_DELETED => 'danger',
            self::ACTION_REJECTED => 'danger',
            self::ACTION_SUSPENDED => 'warning',
            self::ACTION_UPDATED => 'info',
            self::ACTION_SETTINGS_CHANGED => 'warning',
            default => 'gray',
        };
    }

    /**
     * Icône pour l'action
     */
    public function getActionIcon(): string
    {
        return match ($this->action) {
            self::ACTION_CREATED => 'heroicon-o-plus-circle',
            self::ACTION_UPDATED => 'heroicon-o-pencil-square',
            self::ACTION_DELETED => 'heroicon-o-trash',
            self::ACTION_APPROVED => 'heroicon-o-check-circle',
            self::ACTION_REJECTED => 'heroicon-o-x-circle',
            self::ACTION_SUSPENDED => 'heroicon-o-no-symbol',
            self::ACTION_RESTORED => 'heroicon-o-arrow-path',
            self::ACTION_EXPORTED => 'heroicon-o-arrow-down-tray',
            self::ACTION_LOGIN => 'heroicon-o-arrow-right-on-rectangle',
            self::ACTION_LOGOUT => 'heroicon-o-arrow-left-on-rectangle',
            self::ACTION_SETTINGS_CHANGED => 'heroicon-o-cog-6-tooth',
            self::ACTION_BULK_ACTION => 'heroicon-o-squares-2x2',
            self::ACTION_NOTIFICATION_SENT => 'heroicon-o-bell',
            default => 'heroicon-o-information-circle',
        };
    }
}
