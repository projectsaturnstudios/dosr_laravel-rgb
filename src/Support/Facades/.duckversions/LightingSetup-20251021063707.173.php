<?php

namespace DeptOfScrapyardRobotics\LaravelRGB\Support\Facades;

use DeptOfScrapyardRobotics\LaravelRGB\RGBLighting;
use Illuminate\Support\Facades\Facade;

class LightingSetup extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RGBLighting::class;
    }
}
