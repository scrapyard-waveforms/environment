<?php

namespace Waveforms\Environment;

use GeneralPurposeIO\Core\MagicAliases\Circuit;
use Waveforms\Contracts\Environment\MeasuresRelativeHumidity;
use Waveforms\Contracts\Environment\MeasuresTemperature;
use Waveforms\Contracts\Sensors\Enums\HumidityUnit;
use Waveforms\Contracts\Sensors\Enums\TemperatureUnit;
use Waveforms\Contracts\Sensors\SensorException;
use Waveforms\PhysicalDevices\AbstractSensor;

/**
 * Combo climate IC — temperature + relative humidity (AHT10/20/30, …).
 *
 * @property-read float $temperature
 * @property-read float $humidity
 * @property-read float $relative_humidity
 */
class HumidityTemperatureSensor extends AbstractSensor
{
    /** @param MeasuresTemperature&MeasuresRelativeHumidity $sensor */
    public function __construct(
        protected MeasuresTemperature $sensor,
    ) {
        if (! $sensor instanceof MeasuresRelativeHumidity) {
            throw new SensorException(
                static::class.' requires a circuit that Measures Temperature and Relative Humidity.'
            );
        }
    }

    public function __get(string $name): float
    {
        return match ($name) {
            'temperature' => $this->temperature(),
            'humidity', 'relative_humidity' => $this->humidity(),
            default => throw SensorException::invalidProperty($name, static::class),
        };
    }

    public function temperature(TemperatureUnit $unit = TemperatureUnit::CELSIUS): float
    {
        return $this->sensor->temperature($unit);
    }

    public function humidity(HumidityUnit $unit = HumidityUnit::PERCENT): float
    {
        /** @var MeasuresRelativeHumidity $sensor */
        $sensor = $this->sensor;

        return $sensor->humidity($unit);
    }

    public static function circuit(string $driver): static
    {
        $circuit = Circuit::profile($driver);

        if ($circuit instanceof MeasuresTemperature && $circuit instanceof MeasuresRelativeHumidity) {
            return new static($circuit);
        }

        throw new SensorException(
            "Circuit [{$driver}] does not Measure Temperature and Relative Humidity."
        );
    }
}
