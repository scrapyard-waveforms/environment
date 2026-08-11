<?php

namespace Waveforms\Environment\Runner\Sketches\Demos\Assets;

/**
 * Workshop sketch slugs for chip-agnostic HumidityTemperatureSensor demos.
 */
enum HumidityTemperatureDemoSketch: string
{
    case OLED = 'humidity-temperature-oled-demo';
    case CANVAS = 'humidity-temperature-canvas-demo';
    case UX_ALIAS = 'humidity-temperature-ux-canvas-demo';
}
