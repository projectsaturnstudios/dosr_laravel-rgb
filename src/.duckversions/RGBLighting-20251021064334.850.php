<?php

namespace DeptOfScrapyardRobotics\LaravelRGB;

use PhpdaFruit\NeoPixels\Enums\NeoPixelType;
use PhpdaFruit\NeoPixels\PixelBus;
use PhpdaFruit\NeoPixels\PixelChannel;
use PhpdaFruit\NeoPixels\DeviceShapes\RGBStrip;
use PhpdaFruit\NeoPixels\DeviceShapes\DoubleDots;
use PhpdaFruit\NeoPixels\DeviceShapes\SingleDiode;

class RGBLighting
{
    public function __construct(
        protected readonly PixelBus $pixel_bus
    ) {

    }

    public function channel(string $channel): ?RGBDispatcher
    {
        if(is_null($this->pixel_bus->getChannel($channel))) return null;

        return new RGBDispatcher($this->pixel_bus->getChannel($channel));
    }

    public function getPixelChannel(string $channel_name): ?PixelChannel
    {
        return $this->pixel_bus->getChannel($channel_name);
    }

    public static function boot(): void
    {
        app()->singleton(static::class, function () {
            $devices = config('rgb-lighting.devices', []);
            $bus = new PixelBus();
            foreach($devices as $device_name => $device_config) {
                switch($device_config['shape'])
                {
                    case 'rgb-strip':
                        $device = new RGBStrip(
                            $device_config['length'] ?? 8,
                            $device_config['device_path'] ?? '/dev/spidev0.0',
                            NeoPixelType::from(strtoupper($device_config['neopixel_type']) ?? 'RGB')
                        );
                        break;
                    case 'double-dots':
                        $device = new DoubleDots(
                            $device_config['device_path'] ?? '/dev/spidev0.0',
                            NeoPixelType::from(strtoupper($device_config['neopixel_type']) ?? 'RGB')
                        );
                        break;
                    case 'single-diode':
                    default:
                        $device = new SingleDiode(
                            $device_config['device_path'] ?? '/dev/spidev0.0',
                            NeoPixelType::from(strtoupper($device_config['neopixel_type']) ?? 'RGB')
                        );
                }
                $bus = $bus->addPixelChannel($device_name, $device);
            }

            return new static($bus);
        });
    }
}
