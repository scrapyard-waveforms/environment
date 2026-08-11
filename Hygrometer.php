<?php

namespace Waveforms\Environment;

use GeneralPurposeIO\Core\MagicAliases\Circuit;
use Waveforms\Contracts\Environment\MeasuresRelativeHumidity;
use Waveforms\Contracts\Sensors\Enums\HumidityUnit;
use Waveforms\Contracts\Sensors\SensorException;
use Waveforms\PhysicalDevices\AbstractSensor;

/**
 * @property-read float $humidity
 * @property-read float $relative_humidity
 */
class Hygrometer extends AbstractSensor
{
    public function __construct(
        protected MeasuresRelativeHumidity $sensor,
    ) {}

    public function __get(string $name): float
    {
        return match ($name) {
            'humidity', 'relative_humidity' => $this->humidity(),
            default => throw SensorException::invalidProperty($name, static::class),
        };
    }

    public function humidity(HumidityUnit $unit = HumidityUnit::PERCENT): float
    {
        return $this->sensor->humidity($unit);
    }

    public static function circuit(string $driver): static
    {
        $circuit = Circuit::profile($driver);

        if ($circuit instanceof MeasuresRelativeHumidity) {
            return new static($circuit);
        }

        throw new SensorException("Circuit [{$driver}] does not Measure Relative Humidity.");
    }
}
