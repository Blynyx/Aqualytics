<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalNotification extends Model
{
    public const TYPE_ALERT_CREATED = 'alert_created';

    public const TYPE_ALERT_ASSIGNED = 'alert_assigned';

    public const TYPE_INCIDENT_ASSIGNED = 'incident_assigned';

    public const SOURCE_ALERT = 'alert';

    public const SOURCE_INCIDENT = 'incident';

    protected $fillable = [
        'fish_farm_id',
        'user_id',
        'type',
        'source_type',
        'source_id',
        'title',
        'message',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function fishFarm(): BelongsTo
    {
        return $this->belongsTo(FishFarm::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->latest();
    }

    public function sourceUrlFor(User $viewer): ?string
    {
        if ($this->source_type === self::SOURCE_ALERT) {
            $alert = Alert::query()->with('pond')->find($this->source_id);

            if ($alert?->pond?->fish_farm_id !== $viewer->fish_farm_id) {
                return null;
            }

            return route('ponds.show', $alert->pond);
        }

        if ($this->source_type === self::SOURCE_INCIDENT) {
            $incident = Incident::query()->find($this->source_id);

            if ($incident === null || $incident->fish_farm_id !== $viewer->fish_farm_id) {
                return null;
            }

            if ($viewer->role === User::ROLE_SPECIALIST && ! $incident->isAssignedTo($viewer)) {
                return null;
            }

            return route('incidents.show', $incident);
        }

        return null;
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_ALERT_CREATED => 'Nueva alerta',
            self::TYPE_ALERT_ASSIGNED => 'Alerta asignada',
            self::TYPE_INCIDENT_ASSIGNED => 'Incidencia asignada',
            default => $this->type,
        };
    }
}
