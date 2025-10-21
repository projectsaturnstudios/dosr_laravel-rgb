<?php

namespace DeptOfScrapyardRobotics\LaravelRGB;

use PhpdaFruit\NeoPixels\PixelBus;

abstract class AnimatablePixelBus extends PixelBus
{
    public function withAsync(): static
    {
        return $this;
    }

    public function withoutAsync(): static
    {
        return $this;
    }

    public function fire(): static
    {
        return $this;
    }

    public function wait(): static
    {
        return $this;
    }

    public function animate(): static
    {

    }
}
