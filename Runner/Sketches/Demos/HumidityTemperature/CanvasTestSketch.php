<?php

namespace Waveforms\Environment\Runner\Sketches\Demos\HumidityTemperature;

use Fabricate\Contracts\Sketches\Attributes\Sketch as SketchAttribute;
use Fabricate\Contracts\Sketches\SketchLoopResult;
use Fabricate\Sketches\Sketch;
use ScrapyardIO\Tubes\Panels\MonochromePanel;
use Symfony\Component\Console\Command\Command;
use Throwable;
use Waveforms\Environment\Runner\Sketches\Demos\Concerns\OpensDefaultTubesCanvas;
use Waveforms\Environment\Runner\Sketches\Demos\Concerns\PaintsTubesClimateHud;
use Waveforms\Environment\Runner\Sketches\Demos\Concerns\ResolvesHumidityTemperatureCircuit;

/**
 * HumidityTemperatureSensor on tubes.defaults.canvas (window or non-mono panel).
 *
 *   ./runner humidity-temperature-canvas-demo aht20
 *
 * When scrapyard-io/ux is installed, {@see UXCanvasTestSketch} replaces this slug.
 * MonochromePanel is rejected — use humidity-temperature-oled-demo instead.
 */
#[SketchAttribute('humidity-temperature-canvas-demo')]
class CanvasTestSketch extends Sketch
{
    use ResolvesHumidityTemperatureCircuit;
    use OpensDefaultTubesCanvas;
    use PaintsTubesClimateHud;

    protected string $description = 'Climate temp + humidity on tubes.defaults.canvas (Ctrl-C to stop)';

    protected bool $announced = false;

    protected int $lastSampleNs = 0;

    public function configureCommand(Command $command): void
    {
        $this->configureHumidityTemperatureProfileArgument($command);
    }

    public function boot(): void
    {
        $this->installStopHandlers();

        if (! $this->bootHumidityTemperature()) {
            return;
        }

        if (! $this->bootDefaultTubesCanvas()) {
            return;
        }

        if ($this->canvas instanceof MonochromePanel) {
            $this->error(
                "Canvas demo rejects MonochromePanel [{$this->canvasProfile}]. "
                .'Use humidity-temperature-oled-demo instead.'
            );
            $this->closeDefaultTubesCanvas();
            $this->closeHumidityTemperature();
        }
    }

    public function loop(): SketchLoopResult
    {
        if ($this->stopRequested || $this->defaultCanvasShouldStop()) {
            $this->info('Humidity/temperature canvas demo stopped.');

            return SketchLoopResult::STOP;
        }

        if (is_null($this->climate) || is_null($this->canvas) || $this->canvas instanceof MonochromePanel) {
            return SketchLoopResult::STOP;
        }

        if (! $this->announced) {
            $this->info(
                "Climate canvas via HumidityTemperatureSensor::circuit('{$this->circuitProfile}') → canvas [{$this->canvasProfile}]"
            );
            $this->line('  Temp C + humidity %RH — Ctrl-C to end.');
            $this->announced = true;
        }

        $now = hrtime(true);
        if ($this->lastSampleNs !== 0 && ($now - $this->lastSampleNs) < 300_000_000) {
            usleep(2_000);

            return SketchLoopResult::CONTINUE;
        }

        try {
            $c = $this->climate->temperature();
            $rh = $this->climate->humidity();
            $renderer = $this->canvasRenderer();
            $this->paintClimateHud($renderer, $this->canvas, $c, $rh);
            $this->canvas->present();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return SketchLoopResult::STOP;
        }

        $this->lastSampleNs = $now;

        return SketchLoopResult::CONTINUE;
    }

    public function shutdown(): void
    {
        $this->closeDefaultTubesCanvas();
        $this->closeHumidityTemperature();
    }
}
