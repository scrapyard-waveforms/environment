<?php

namespace Waveforms\Environment;

use GeneralPurposeIO\Core\MagicAliases\Circuit;
use Waveforms\Contracts\Environment\MeasuresTemperature;
use Waveforms\Contracts\Sensors\Enums\TemperatureUnit;
use Waveforms\Contracts\Sensors\SensorException;
use Waveforms\PhysicalDevices\AbstractSensor;

/**
 * @property-read float $temperature
 */
class Thermometer extends AbstractSensor
{
    public function __construct(
        protected MeasuresTemperature $sensor,
    ) {}

    public function __get(string $name): float
    {
        return match ($name) {
            'temperature' => $this->temperature(),
            default => throw SensorException::invalidProperty($name, static::class),
        };
    }

    public function temperature(TemperatureUnit $unit = TemperatureUnit::CELSIUS): float
    {
        return $this->sensor->temperature($unit);
    }

    public static function circuit(string $driver): static
    {
        $circuit = Circuit::profile($driver);

        if ($circuit instanceof MeasuresTemperature) {
            return new static($circuit);
        }

        throw new SensorException("Circuit [{$driver}] does not Measure Temperature.");
    }
}
