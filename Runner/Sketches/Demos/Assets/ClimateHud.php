<?php

namespace Waveforms\Environment\Runner\Sketches\Demos\Assets;

use ScrapyardIO\UX\Components\Indicators\ProgressBar;
use ScrapyardIO\UX\Components\Text\Label;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Enums\Axis;
use ScrapyardIO\UX\Enums\TextAlign;
use ScrapyardIO\UX\Geometry\Size;
use ScrapyardIO\UX\Support\Theme;

/**
 * Full-canvas climate stage — temperature + relative humidity.
 */
class ClimateHud extends UIComponent
{
    protected Label $title;

    protected Label $tempLabel;

    protected Label $tempValue;

    protected Label $tempUnit;

    protected Label $humLabel;

    protected Label $humValue;

    protected Label $humUnit;

    protected ProgressBar $tempBar;

    protected ProgressBar $humBar;

    protected float $tempMinC;

    protected float $tempMaxC;

    public function __construct(float $tempMinC = 0.0, float $tempMaxC = 40.0)
    {
        parent::__construct('climate-hud');

        $this->tempMinC = $tempMinC;
        $this->tempMaxC = max($tempMinC + 0.0001, $tempMaxC);

        $this->title = Label::of('CLIMATE', Theme::color('muted'))->setAlign(TextAlign::CENTER);
        $this->tempLabel = Label::of('TEMPERATURE', Theme::color('muted'));
        $this->tempValue = Label::of('0.0', Theme::color('ink'))->setAlign(TextAlign::CENTER);
        $this->tempUnit = Label::of('C', Theme::color('warning'))->setAlign(TextAlign::CENTER);
        $this->humLabel = Label::of('HUMIDITY', Theme::color('muted'));
        $this->humValue = Label::of('0.0', Theme::color('ink'))->setAlign(TextAlign::CENTER);
        $this->humUnit = Label::of('%RH', Theme::color('accent'))->setAlign(TextAlign::CENTER);
        $this->tempBar = ProgressBar::of(0.0, Axis::HORIZONTAL);
        $this->humBar = ProgressBar::of(0.0, Axis::HORIZONTAL);
        $this->tempBar->setColors(Theme::color('warning'), Theme::color('track'));
        $this->humBar->setColors(Theme::color('accent'), Theme::color('track'));

        foreach ([
            $this->title,
            $this->tempLabel,
            $this->tempValue,
            $this->tempUnit,
            $this->humLabel,
            $this->humValue,
            $this->humUnit,
            $this->tempBar,
            $this->humBar,
        ] as $child) {
            $this->addChild($child);
        }
    }

    public function sync(float $celsius, float $humidityPercent): void
    {
        $this->tempValue->setText(sprintf('%.1f', $celsius));
        $this->humValue->setText(sprintf('%.1f', $humidityPercent));
        $this->tempBar->setValue($this->tempPercent($celsius));
        $this->humBar->setValue(max(0.0, min(1.0, $humidityPercent / 100.0)));
    }

    protected function tempPercent(float $celsius): float
    {
        $span = max(0.0001, $this->tempMaxC - $this->tempMinC);

        return max(0.0, min(1.0, ($celsius - $this->tempMinC) / $span));
    }

    public function layout(Size $available): void
    {
        $w = max(1, $available->width);
        $h = max(1, $available->height);
        $this->setSize($w, $h);

        $marginX = max(16, (int) round($w * 0.05));
        $marginY = max(12, (int) round($h * 0.04));
        $innerW = max(1, $w - (2 * $marginX));
        $usable = max(1, $h - (2 * $marginY));

        $titleH = (int) round($usable * 0.10);
        $half = (int) floor(($usable - $titleH) / 2);

        $y = $marginY;
        $this->fitLabel($this->title, $innerW, $titleH, 1, 4);
        $this->centerChild($this->title, $marginX, $y, $innerW, $titleH);
        $y += $titleH;

        $this->layoutChannel(
            $marginX,
            $y,
            $innerW,
            $half,
            $this->tempLabel,
            $this->tempValue,
            $this->tempUnit,
            $this->tempBar,
        );
        $y += $half;

        $this->layoutChannel(
            $marginX,
            $y,
            $innerW,
            $half,
            $this->humLabel,
            $this->humValue,
            $this->humUnit,
            $this->humBar,
        );
    }

    protected function layoutChannel(
        int $x,
        int $y,
        int $w,
        int $h,
        Label $label,
        Label $value,
        Label $unit,
        ProgressBar $bar,
    ): void {
        $labelH = max(8, (int) round($h * 0.22));
        $valueH = max(12, (int) round($h * 0.42));
        $unitH = max(8, (int) round($h * 0.16));
        $barH = max(8, min(18, (int) round($h * 0.14)));

        $cursor = $y;
        $this->fitLabel($label, $w, $labelH, 1, 3);
        $label->setPosition($x, $cursor + max(0, (int) (($labelH - $label->size()->height) / 2)));
        $cursor += $labelH;

        $this->fitLabel($value, $w, $valueH, 1, 8);
        $this->centerChild($value, $x, $cursor, $w, $valueH);
        $cursor += $valueH;

        $this->fitLabel($unit, $w, $unitH, 1, 3);
        $this->centerChild($unit, $x, $cursor, $w, $unitH);
        $cursor += $unitH;

        $barY = min($y + $h - $barH - 1, max($cursor, $y + $h - $barH - 1));
        $bar->setThickness($barH);
        $bar->setPosition($x, $barY);
        $bar->setSize($w, $barH);
        $bar->layout(new Size($w, $barH));
    }

    protected function fitLabel(Label $label, int $maxW, int $maxH, int $min, int $max): void
    {
        $best = $min;
        for ($size = $max; $size >= $min; $size--) {
            $label->setTextSize($size);
            $label->layout($label->size());
            if ($label->size()->width <= $maxW && $label->size()->height <= $maxH) {
                $best = $size;
                break;
            }
        }
        $label->setTextSize($best);
        $label->layout($label->size());
    }

    protected function centerChild(Label $label, int $boxX, int $boxY, int $boxW, int $boxH): void
    {
        $x = $boxX + max(0, (int) (($boxW - $label->size()->width) / 2));
        $y = $boxY + max(0, (int) (($boxH - $label->size()->height) / 2));
        $label->setPosition($x, $y);
    }

    protected function draw(PaintContext $ctx): void
    {
        // children paint
    }
}
