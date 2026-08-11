<?php

namespace Waveforms\Environment\Runner\Sketches\Demos\Concerns;

use ScrapyardIO\Tubes\Canvas\Canvas;
use ScrapyardIO\Tubes\Rendering\Renderer2D;

/**
 * Dual climate stage — temperature (°C) + relative humidity (%).
 */
trait PaintsTubesClimateHud
{
    /** Temperature bar span low (°C). */
    protected float $tempMinC = 0.0;

    /** Temperature bar span high (°C). */
    protected float $tempMaxC = 40.0;

    protected function paintClimateHud(
        Renderer2D $renderer,
        Canvas $canvas,
        float $celsius,
        float $humidityPercent,
    ): void {
        $w = max(1, $canvas->width());
        $h = max(1, $canvas->height());
        $bg = 0x0A0C10FF;
        $fg = 0xF2F5F8FF;
        $muted = 0x8B93A1FF;
        $tempAccent = 0xFFB020FF;
        $humAccent = 0x3DDC97FF;
        $track = 0x1E2430FF;

        $fb = $canvas->framebuffer();
        $renderer->setFramebuffer($fb);
        $renderer->fill($bg);
        $renderer->setFont(null)->setTextWrap(false);

        if ($h < 100 || $w < 160) {
            $this->paintCompactClimateHud(
                $renderer,
                $w,
                $h,
                $celsius,
                $humidityPercent,
                $fg,
                $bg,
                $muted,
                $tempAccent,
                $humAccent,
                $track,
            );

            return;
        }

        $this->paintStageClimateHud(
            $renderer,
            $w,
            $h,
            $celsius,
            $humidityPercent,
            $fg,
            $bg,
            $muted,
            $tempAccent,
            $humAccent,
            $track,
        );
    }

    protected function paintStageClimateHud(
        Renderer2D $renderer,
        int $w,
        int $h,
        float $celsius,
        float $humidityPercent,
        int $fg,
        int $bg,
        int $muted,
        int $tempAccent,
        int $humAccent,
        int $track,
    ): void {
        $marginX = max(16, (int) round($w * 0.05));
        $marginY = max(12, (int) round($h * 0.04));
        $innerW = max(1, $w - (2 * $marginX));
        $usable = max(1, $h - (2 * $marginY));

        $titleH = (int) round($usable * 0.10);
        $half = (int) floor(($usable - $titleH) / 2);

        $y = $marginY;
        $this->paintFittedCentered($renderer, 'CLIMATE', $marginX, $y, $innerW, $titleH, $muted, $bg, 1, 4);
        $y += $titleH;

        $this->paintClimateChannel(
            $renderer,
            $marginX,
            $y,
            $innerW,
            $half,
            'TEMPERATURE',
            sprintf('%.1f', $celsius),
            'C',
            $this->tempPercent($celsius),
            $fg,
            $bg,
            $muted,
            $tempAccent,
            $track,
        );
        $y += $half;

        $this->paintClimateChannel(
            $renderer,
            $marginX,
            $y,
            $innerW,
            $half,
            'HUMIDITY',
            sprintf('%.1f', $humidityPercent),
            '%RH',
            $this->humidityPercent($humidityPercent),
            $fg,
            $bg,
            $muted,
            $humAccent,
            $track,
        );

        $renderer->setFont(null);
    }

    protected function paintCompactClimateHud(
        Renderer2D $renderer,
        int $w,
        int $h,
        float $celsius,
        float $humidityPercent,
        int $fg,
        int $bg,
        int $muted,
        int $tempAccent,
        int $humAccent,
        int $track,
    ): void {
        $pad = 2;
        $titleH = max(8, (int) ($h * 0.16));
        $this->paintFittedCentered($renderer, 'CLIMATE', $pad, $pad, $w - (2 * $pad), $titleH, $muted, $bg, 1, 2);

        $rowTop = $pad + $titleH;
        $rowH = max(14, (int) floor(($h - $rowTop - $pad) / 2));

        $this->paintClimateChannel(
            $renderer,
            $pad,
            $rowTop,
            $w - (2 * $pad),
            $rowH,
            'TEMP',
            sprintf('%.1fC', $celsius),
            '',
            $this->tempPercent($celsius),
            $fg,
            $bg,
            $muted,
            $tempAccent,
            $track,
        );

        $this->paintClimateChannel(
            $renderer,
            $pad,
            $rowTop + $rowH,
            $w - (2 * $pad),
            $rowH,
            'HUM',
            sprintf('%.0f%%', $humidityPercent),
            '',
            $this->humidityPercent($humidityPercent),
            $fg,
            $bg,
            $muted,
            $humAccent,
            $track,
        );

        $renderer->setFont(null);
    }

    protected function paintClimateChannel(
        Renderer2D $renderer,
        int $x,
        int $y,
        int $w,
        int $h,
        string $label,
        string $value,
        string $unit,
        float $pct,
        int $fg,
        int $bg,
        int $muted,
        int $accent,
        int $track,
    ): void {
        $labelH = max(8, (int) round($h * 0.22));
        $valueH = max(12, (int) round($h * 0.42));
        $unitH = $unit === '' ? 0 : max(8, (int) round($h * 0.16));
        $barH = max(4, min(14, (int) round($h * 0.14)));

        $cursor = $y;
        $this->paintFittedLeft($renderer, $label, $x, $cursor, $w, $labelH, $muted, $bg, 1, 3);
        $cursor += $labelH;

        $this->paintFittedCentered($renderer, $value, $x, $cursor, $w, $valueH, $fg, $bg, 1, 8);
        $cursor += $valueH;

        if ($unit !== '') {
            $this->paintFittedCentered($renderer, $unit, $x, $cursor, $w, $unitH, $accent, $bg, 1, 3);
            $cursor += $unitH;
        }

        $barY = min($y + $h - $barH - 1, max($cursor, $y + $h - $barH - 1));
        $barMaxW = max(1, $w);
        $barW = max(1, (int) round($barMaxW * $pct));
        $renderer->fillRect($x, $barY, $barMaxW, $barH, $track);
        $renderer->fillRect($x, $barY, $barW, $barH, $accent);
    }

    protected function tempPercent(float $celsius): float
    {
        $span = max(0.0001, $this->tempMaxC - $this->tempMinC);

        return max(0.0, min(1.0, ($celsius - $this->tempMinC) / $span));
    }

    protected function humidityPercent(float $humidityPercent): float
    {
        return max(0.0, min(1.0, $humidityPercent / 100.0));
    }

    protected function paintFittedCentered(
        Renderer2D $renderer,
        string $text,
        int $boxX,
        int $boxY,
        int $boxW,
        int $boxH,
        int $fg,
        int $bg,
        int $minSize,
        int $maxSize,
    ): void {
        if ($text === '' || $boxW < 1 || $boxH < 1) {
            return;
        }

        $size = $this->fitTextSize($renderer, $text, $boxW, $boxH, $minSize, $maxSize);
        [$textW, $textH] = $this->measureText($renderer, $text, $size);
        $drawX = $boxX + max(0, (int) (($boxW - $textW) / 2));
        $drawY = $boxY + max(0, (int) (($boxH - $textH) / 2));

        $renderer->setTextSize($size)
            ->setTextColor($fg, $bg)
            ->setCursor($drawX, $drawY)
            ->println($text);
    }

    protected function paintFittedLeft(
        Renderer2D $renderer,
        string $text,
        int $boxX,
        int $boxY,
        int $boxW,
        int $boxH,
        int $fg,
        int $bg,
        int $minSize,
        int $maxSize,
    ): void {
        if ($text === '' || $boxW < 1 || $boxH < 1) {
            return;
        }

        $size = $this->fitTextSize($renderer, $text, $boxW, $boxH, $minSize, $maxSize);
        [, $textH] = $this->measureText($renderer, $text, $size);
        $drawY = $boxY + max(0, (int) (($boxH - $textH) / 2));

        $renderer->setTextSize($size)
            ->setTextColor($fg, $bg)
            ->setCursor($boxX, $drawY)
            ->println($text);
    }

    protected function fitTextSize(
        Renderer2D $renderer,
        string $text,
        int $maxW,
        int $maxH,
        int $min,
        int $max,
    ): int {
        $min = max(1, $min);
        $max = max($min, $max);
        $lo = $min;
        $hi = $max;
        $best = $min;

        while ($lo <= $hi) {
            $mid = (int) (($lo + $hi) / 2);
            [$tw, $th] = $this->measureText($renderer, $text, $mid);
            if ($tw <= $maxW && $th <= $maxH) {
                $best = $mid;
                $lo = $mid + 1;
            } else {
                $hi = $mid - 1;
            }
        }

        return $best;
    }

    /**
     * @return array{0: int, 1: int}
     */
    protected function measureText(Renderer2D $renderer, string $text, int $size): array
    {
        $renderer->setTextSize($size)->setTextWrap(false);

        return [
            max(1, strlen($text) * 6 * $size),
            max(1, 8 * $size),
        ];
    }
}
