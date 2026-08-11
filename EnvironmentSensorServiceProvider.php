<?php

namespace Waveforms\Environment;

use Fabricate\Contracts\Sketches\SketchRegistry;
use Fabricate\NutsAndBolts\ServiceProvider;
use Waveforms\Environment\Runner\Sketches\Demos\Assets\HumidityTemperatureDemoSketch;
use Waveforms\Environment\Runner\Sketches\Demos\HumidityTemperature\CanvasTestSketch;
use Waveforms\Environment\Runner\Sketches\Demos\HumidityTemperature\OLEDTestSketch;
use Waveforms\Environment\Runner\Sketches\Demos\HumidityTemperature\UXCanvasTestSketch;

class EnvironmentSensorServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->registerDemoSketches();
    }

    protected function registerDemoSketches(): void
    {
        if (! $this->container->bound(SketchRegistry::class)) {
            return;
        }

        // Soft tubes dependency.
        if (! class_exists(\ScrapyardIO\Tubes\Core\MagicAliases\Panel::class)) {
            return;
        }

        /** @var SketchRegistry $registry */
        $registry = $this->container->make(SketchRegistry::class);

        if (! $registry->has(HumidityTemperatureDemoSketch::OLED->value)) {
            $registry->registerConvention(HumidityTemperatureDemoSketch::OLED->value, OLEDTestSketch::class);
        }

        if (! $registry->has(HumidityTemperatureDemoSketch::CANVAS->value)) {
            $registry->registerConvention(HumidityTemperatureDemoSketch::CANVAS->value, CanvasTestSketch::class);
        }

        if (class_exists(\ScrapyardIO\UX\Core\Scene::class)) {
            $registry->replace(UXCanvasTestSketch::class);

            if (! $registry->has(HumidityTemperatureDemoSketch::UX_ALIAS->value)) {
                $registry->registerConvention(
                    HumidityTemperatureDemoSketch::UX_ALIAS->value,
                    UXCanvasTestSketch::class,
                );
            }
        }
    }
}
