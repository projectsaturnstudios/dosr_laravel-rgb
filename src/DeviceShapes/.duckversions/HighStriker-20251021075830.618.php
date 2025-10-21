<?php

namespace PhpdaFruit\NeoPixels\DeviceShapes;

use PhpdaFruit\NeoPixels\Enums\NeoPixelType;
use PhpdaFruit\NeoPixels\Enums\SPIDevice;
use PhpdaFruit\NeoPixels\PixelBus;
use Phpdafruit\NeoPixels\PixelChannel;

class HighStriker extends PixelBus
{
    public function __construct(
        int $rail_dots = 15,
        int $bell_dots = 2,
        SPIDevice $rail_device = SPIDevice::SPI_0_0,
        SPIDevice $bell_device = SPIDevice::SPI_1_0,
        NeoPixelType $rail_type = NeoPixelType::GRB,
        NeoPixelType $bell_type = NeoPixelType::GRB
    ) {
        parent::__construct([
            'rail' => new RGBStrip($rail_dots, $rail_device, $rail_type),
            'bell' => new PixelChannel($bell_dots, $bell_device, $bell_type)
        ]);
    }
}
