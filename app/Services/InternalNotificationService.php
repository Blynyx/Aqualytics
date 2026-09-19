<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Incident;
use App\Models\InternalNotification;
use App\Models\User;

class InternalNotificationService
{
    public function notifyAlertCreated(Alert $alert): void
    {
        $alert->loadMissing('pond.fishFarm');
        $fishFarm = $alert->pond?->fishFarm;

        if ($fishFarm === null) {
            return;
        }

        $recipients = $fishFarm->users()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERVISOR])
            ->get();

        $pondName = $alert->pond->name;
        $message = $alert->message.' en '.$pondName.'.';

        foreach ($recipients as $recipient) {
            $this->store(
                $recipient,
                InternalNotification::TYPE_ALERT_CREATED,
                InternalNotification::SOURCE_ALERT,
                $alert->id,
                'Nueva alerta hídrica',
                $message,
            );
        }
    }

    public function notifyAlertAssigned(Alert $alert, User $specialist): void
    {
        $alert->loadMissing('pond');
        $parameter = $this->parameterLabel($alert->parameter);
        $pondName = $alert->pond?->name ?? 'la unidad';

        $this->store(
            $specialist,
            InternalNotification::TYPE_ALERT_ASSIGNED,
            InternalNotification::SOURCE_ALERT,
            $alert->id,
            'Alerta asignada',
            'Se te asignó una alerta de '.$parameter.' en '.$pondName.'.',
        );
    }

    public function notifyIncidentAssigned(Incident $incident, User $specialist): void
    {
        $this->store(
            $specialist,
            InternalNotification::TYPE_INCIDENT_ASSIGNED,
            InternalNotification::SOURCE_INCIDENT,
            $incident->id,
            'Nueva incidencia asignada',
            'Se te asignó la incidencia: '.$incident->title.'.',
        );
    }

    private function store(
        User $recipient,
        string $type,
        string $sourceType,
        int $sourceId,
        string $title,
        string $message,
    ): void {
        InternalNotification::query()->create([
            'fish_farm_id' => $recipient->fish_farm_id,
            'user_id' => $recipient->id,
            'type' => $type,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'title' => $title,
            'message' => $message,
        ]);
    }

    private function parameterLabel(?string $parameter): string
    {
        return match ($parameter) {
            'temperature' => 'temperatura',
            'ph' => 'pH',
            'turbidity' => 'turbidez',
            'water_level' => 'nivel de agua',
            default => $parameter ?: 'calidad de agua',
        };
    }
}
