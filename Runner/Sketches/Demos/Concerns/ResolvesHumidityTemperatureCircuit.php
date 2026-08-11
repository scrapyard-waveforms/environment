<?php

namespace Waveforms\Environment\Runner\Sketches\Demos\Concerns;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Throwable;
use Waveforms\Environment\HumidityTemperatureSensor;

/**
 * Require a circuits.php profile argument and open {@see HumidityTemperatureSensor}.
 *
 * @mixin \Fabricate\Sketches\Sketch
 */
trait ResolvesHumidityTemperatureCircuit
{
    protected ?string $circuitProfile = null;

    protected ?HumidityTemperatureSensor $climate = null;

    protected bool $stopRequested = false;

    protected function configureHumidityTemperatureProfileArgument(Command $command): void
    {
        $command->addArgument(
            'profile',
            InputArgument::REQUIRED,
            'circuits.php profile name (ic must Measure Temperature and Relative Humidity)',
        );
    }

    protected function installStopHandlers(): void
    {
        if (! extension_loaded('pcntl')) {
            return;
        }

        pcntl_async_signals(true);
        $stop = function (): void {
            $this->stopRequested = true;
        };
        pcntl_signal(SIGINT, $stop);
        pcntl_signal(SIGTERM, $stop);
    }

    /**
     * @return bool false when the sketch should quit (errors already printed)
     */
    protected function bootHumidityTemperature(): bool
    {
        $requested = $this->argument('profile');
        if (! is_string($requested) || trim($requested) === '') {
            $this->error('Profile argument is required.');

            return false;
        }

        $this->circuitProfile = trim($requested);

        try {
            $this->climate = HumidityTemperatureSensor::circuit($this->circuitProfile);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            $this->climate = null;
            $this->circuitProfile = null;

            return false;
        }

        return true;
    }

    protected function closeHumidityTemperature(): void
    {
        $this->climate = null;
        $this->circuitProfile = null;
    }
}
