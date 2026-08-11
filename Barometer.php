<?php

namespace Waveforms\Environment;

use GeneralPurposeIO\Core\MagicAliases\Circuit;
use Waveforms\Contracts\Environment\MeasuresBarometricPressure;
use Waveforms\Contracts\Sensors\Enums\PressureUnit;
use Waveforms\Contracts\Sensors\SensorException;
use Waveforms\PhysicalDevices\AbstractSensor;

/**
 * @property-read float $pressure
 */
class Barometer extends AbstractSensor
{
    public function __construct(
        protected MeasuresBarometricPressure $sensor,
    ) {}

    public function __get(string $name): float
    {
        return match ($name) {
            'pressure' => $this->pressure(),
            default => throw SensorException::invalidProperty($name, static::class),
        };
    }

    public function pressure(PressureUnit $unit = PressureUnit::HECTOPASCAL): float
    {
        return $this->sensor->pressure($unit);
    }

    public static function circuit(string $driver): static
    {
        $circuit = Circuit::profile($driver);

        if ($circuit instanceof MeasuresBarometricPressure) {
            return new static($circuit);
        }

        throw new SensorException("Circuit [{$driver}] does not Measure Barometric Pressure.");
    }
}
