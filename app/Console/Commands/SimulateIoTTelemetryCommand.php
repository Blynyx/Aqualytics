<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\Device;
use App\Services\ReadingIngestionService;
use App\Services\TelemetrySampleGenerator;
use Illuminate\Console\Command;

class SimulateIoTTelemetryCommand extends Command
{
    protected $signature = 'aqualytics:simulate
                            {--device= : UID del dispositivo registrado}
                            {--count=10 : Cantidad de lecturas a generar}
                            {--interval=2 : Segundos entre lecturas (0 para pruebas)}
                            {--mode=normal : normal, anomaly o mixed}
                            {--anomaly-rate=10 : Porcentaje de anomalías en modo mixed (0-100)}';

    protected $description = 'Simula telemetría IoT usando la misma ingestión que el ESP32 real';

    public function handle(ReadingIngestionService $ingestion): int
    {
        $deviceUid = trim((string) $this->option('device'));

        if ($deviceUid === '') {
            $this->error('Debe indicar --device=UID. El simulador no crea dispositivos.');

            return self::FAILURE;
        }

        $device = Device::query()
            ->with('pond.threshold')
            ->where('device_uid', $deviceUid)
            ->first();

        if ($device === null) {
            $this->error("No existe un dispositivo con UID [{$deviceUid}]. El simulador no crea dispositivos.");

            return self::FAILURE;
        }

        $count = filter_var($this->option('count'), FILTER_VALIDATE_INT);
        if ($count === false || $count < 1) {
            $this->error('--count debe ser un entero positivo.');

            return self::FAILURE;
        }

        $interval = filter_var($this->option('interval'), FILTER_VALIDATE_INT);
        if ($interval === false || $interval < 0) {
            $this->error('--interval debe ser un entero mayor o igual a 0.');

            return self::FAILURE;
        }

        $mode = strtolower((string) $this->option('mode'));
        if (! in_array($mode, [
            TelemetrySampleGenerator::MODE_NORMAL,
            TelemetrySampleGenerator::MODE_ANOMALY,
            TelemetrySampleGenerator::MODE_MIXED,
        ], true)) {
            $this->error('--mode debe ser normal, anomaly o mixed.');

            return self::FAILURE;
        }

        $anomalyRate = filter_var($this->option('anomaly-rate'), FILTER_VALIDATE_INT);
        if ($anomalyRate === false || $anomalyRate < 0 || $anomalyRate > 100) {
            $this->error('--anomaly-rate debe ser un entero entre 0 y 100.');

            return self::FAILURE;
        }

        $generator = new TelemetrySampleGenerator($device, $mode, $anomalyRate);

        $normal = 0;
        $anomalous = 0;
        $alertsGenerated = 0;

        for ($index = 1; $index <= $count; $index++) {
            $result = $ingestion->ingest($device, $generator->next());
            $hasAnomaly = $result->hasAnomaly();
            $alertCount = $result->alerts->count();

            if ($hasAnomaly) {
                $anomalous++;
            } else {
                $normal++;
            }

            $alertsGenerated += $alertCount;

            $this->line("[{$index}/{$count}] {$device->device_uid}");
            $this->line(sprintf('Temp: %.1f °C', (float) $result->reading->temperature));
            $this->line(sprintf('pH: %.2f', (float) $result->reading->ph));
            $this->line(sprintf('Turbidez: %.1f', (float) $result->reading->turbidity));
            $this->line(sprintf('Nivel: %.1f', (float) $result->reading->water_level));
            $this->line('Estado: '.($hasAnomaly ? 'ANOMALY' : 'NORMAL'));

            foreach ($result->alerts as $alert) {
                $this->line('Alerta generada: '.$this->alertLabel($alert));
            }

            $this->newLine();

            if ($interval > 0 && $index < $count) {
                sleep($interval);
            }
        }

        $this->info("Lecturas generadas: {$count}");
        $this->line("Normales: {$normal}");
        $this->line("Anómalas: {$anomalous}");
        $this->line("Alertas generadas: {$alertsGenerated}");
        $this->line("Dispositivo: {$device->device_uid}");

        return self::SUCCESS;
    }

    private function alertLabel(Alert $alert): string
    {
        return match ($alert->parameter) {
            'temperature' => 'Temperatura fuera de rango',
            'ph' => 'pH fuera de rango',
            'turbidity' => 'Turbidez fuera de rango',
            'water_level' => 'Nivel de agua fuera de rango',
            default => (string) ($alert->message ?: 'Parámetro fuera de rango'),
        };
    }
}
