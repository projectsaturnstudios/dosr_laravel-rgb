<?php

namespace DeptOfScrapyardRobotics\LaravelRGB\Support\Facades;

use DeptOfScrapyardRobotics\LaravelRGB\RGBLighting;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \DeptOfScrapyardRobotics\LaravelRGB\RGBDispatcher|null channel(string $channel)
 * @method static \PhpdaFruit\NeoPixels\PixelChannel|null getPixelChannel(string $channel_name)
 */
class LightingSetup extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RGBLighting::class;
    }
}
