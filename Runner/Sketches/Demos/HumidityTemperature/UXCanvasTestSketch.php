<?php

namespace Waveforms\Environment\Runner\Sketches\Demos\HumidityTemperature;

use Fabricate\Contracts\Sketches\Attributes\Sketch as SketchAttribute;
use Fabricate\Contracts\Sketches\SketchLoopResult;
use Fabricate\Sketches\Sketch;
use ScrapyardIO\Tubes\Panels\MonochromePanel;
use ScrapyardIO\UX\Core\Scene;
use ScrapyardIO\UX\Geometry\Size;
use ScrapyardIO\UX\Support\Theme;
use Symfony\Component\Console\Command\Command;
use Throwable;
use Waveforms\Environment\Runner\Sketches\Demos\Assets\ClimateHud;
use Waveforms\Environment\Runner\Sketches\Demos\Concerns\OpensDefaultTubesCanvas;
use Waveforms\Environment\Runner\Sketches\Demos\Concerns\ResolvesHumidityTemperatureCircuit;

/**
 * HumidityTemperatureSensor on a UX Scene (binds over {@see CanvasTestSketch}).
 *
 * Same slug: humidity-temperature-canvas-demo. Alias: humidity-temperature-ux-canvas-demo.
 * MonochromePanel rejected — use humidity-temperature-oled-demo.
 */
#[SketchAttribute('humidity-temperature-canvas-demo')]
class UXCanvasTestSketch extends Sketch
{
    use ResolvesHumidityTemperatureCircuit;
    use OpensDefaultTubesCanvas;

    protected string $description = 'Climate temp + humidity via UX Scene on tubes.defaults.canvas (Ctrl-C to stop)';

    protected bool $announced = false;

    protected int $lastSampleNs = 0;

    protected ?Scene $scene = null;

    protected ?ClimateHud $hud = null;

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
                "UX canvas demo rejects MonochromePanel [{$this->canvasProfile}]. "
                .'Use humidity-temperature-oled-demo instead.'
            );
            $this->closeDefaultTubesCanvas();
            $this->closeHumidityTemperature();

            return;
        }

        $this->hud = new ClimateHud(0.0, 40.0);
        $this->scene = (new Scene)
            ->attach($this->canvas)
            ->setRoot($this->hud)
            ->setClearColor(Theme::color('surface'));

        $size = new Size($this->canvas->width(), $this->canvas->height());
        $this->hud->layout($size);
    }

    public function loop(): SketchLoopResult
    {
        if ($this->stopRequested || $this->defaultCanvasShouldStop()) {
            $this->info('Humidity/temperature UX canvas demo stopped.');

            return SketchLoopResult::STOP;
        }

        if (
            is_null($this->climate)
            || is_null($this->canvas)
            || is_null($this->scene)
            || is_null($this->hud)
            || $this->canvas instanceof MonochromePanel
        ) {
            return SketchLoopResult::STOP;
        }

        if (! $this->announced) {
            $this->info(
                "Climate UX canvas via HumidityTemperatureSensor::circuit('{$this->circuitProfile}') → canvas [{$this->canvasProfile}]"
            );
            $this->line('  UX Scene HUD — Ctrl-C to end.');
            $this->announced = true;
        }

        $now = hrtime(true);
        if ($this->lastSampleNs !== 0 && ($now - $this->lastSampleNs) < 300_000_000) {
            usleep(2_000);

            return SketchLoopResult::CONTINUE;
        }

        try {
            $this->hud->sync(
                $this->climate->temperature(),
                $this->climate->humidity(),
            );
            $renderer = $this->canvasRenderer();
            $fb = $this->canvas->framebuffer();
            $renderer->setFramebuffer($fb);
            $this->scene->paint($renderer);
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
        $this->scene = null;
        $this->hud = null;
        $this->closeDefaultTubesCanvas();
        $this->closeHumidityTemperature();
    }
}
