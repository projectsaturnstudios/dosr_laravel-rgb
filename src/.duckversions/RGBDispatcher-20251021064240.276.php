<?php

namespace DeptOfScrapyardRobotics\LaravelRGB;

use Phpdafruit\NeoPixels\PixelChannel;

class RGBDispatcher
{
    public function __construct(
        protected readonly PixelChannel $channel
    ) {}
}
