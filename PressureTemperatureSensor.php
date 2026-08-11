<?php

namespace Waveforms\Environment;

use GeneralPurposeIO\Core\MagicAliases\Circuit;
use Waveforms\Contracts\Environment\MeasuresBarometricPressure;
use Waveforms\Contracts\Environment\MeasuresTemperature;
use Waveforms\Contracts\Sensors\Enums\PressureUnit;
use Waveforms\Contracts\Sensors\Enums\TemperatureUnit;
use Waveforms\Contracts\Sensors\SensorException;
use Waveforms\PhysicalDevices\AbstractSensor;

/**
 * Combo climate IC — temperature + barometric pressure (BMP280, …).
 *
 * @property-read float $temperature
 * @property-read float $pressure
 */
class PressureTemperatureSensor extends AbstractSensor
{
    /** @param MeasuresTemperature&MeasuresBarometricPressure $sensor */
    public function __construct(
        protected MeasuresTemperature $sensor,
    ) {
        if (! $sensor instanceof MeasuresBarometricPressure) {
            throw new SensorException(
                static::class.' requires a circuit that Measures Temperature and Barometric Pressure.'
            );
        }
    }

    public function __get(string $name): float
    {
        return match ($name) {
            'temperature' => $this->temperature(),
            'pressure' => $this->pressure(),
            default => throw SensorException::invalidProperty($name, static::class),
        };
    }

    public function temperature(TemperatureUnit $unit = TemperatureUnit::CELSIUS): float
    {
        return $this->sensor->temperature($unit);
    }

    public function pressure(PressureUnit $unit = PressureUnit::HECTOPASCAL): float
    {
        /** @var MeasuresBarometricPressure $sensor */
        $sensor = $this->sensor;

        return $sensor->pressure($unit);
    }

    public static function circuit(string $driver): static
    {
        $circuit = Circuit::profile($driver);

        if ($circuit instanceof MeasuresTemperature && $circuit instanceof MeasuresBarometricPressure) {
            return new static($circuit);
        }

        throw new SensorException(
            "Circuit [{$driver}] does not Measure Temperature and Barometric Pressure."
        );
    }
}
