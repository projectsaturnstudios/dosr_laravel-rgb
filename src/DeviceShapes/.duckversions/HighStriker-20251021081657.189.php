<?php

namespace DeptOfScrapyardRobotics\LaravelRGB\DeviceShapes;

use Phpdafruit\NeoPixels\PixelChannel;
use PhpdaFruit\NeoPixels\Enums\SPIDevice;
use PhpdaFruit\NeoPixels\Enums\NeoPixelType;
use PhpdaFruit\NeoPixels\DeviceShapes\RGBStrip;
use DeptOfScrapyardRobotics\LaravelRGB\AnimatablePixelBus;
use DeptOfScrapyardRobotics\LaravelRGB\Enums\BusAnimation;

class HighStriker extends AnimatablePixelBus
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
            'rail' => new RGBStrip($rail_dots, $rail_device->value, $rail_type),
            'bell' => new PixelChannel($bell_dots, $bell_device->value, $bell_type)
        ]);
    }

    /**
     * Quick strike with configurable strength
     *
     * @param string $strength 'weak', 'medium', 'strong', or 'random'
     * @param int $duration_ms Animation duration
     * @return static
     */
    public function strike(string $strength = 'random', int $duration_ms = 5000): static
    {
        $velocities = [
            'weak' => ['min' => 30, 'max' => 60],
            'medium' => ['min' => 60, 'max' => 100],
            'strong' => ['min' => 100, 'max' => 150],
            'random' => ['min' => 30, 'max' => 150],
        ];

        $range = $velocities[$strength] ?? $velocities['random'];

        return $this->animate(
            BusAnimation::HIGH_STRIKER_PHYSICS,
            $duration_ms,
            [
                'min_velocity' => $range['min'],
                'max_velocity' => $range['max']
            ]
        );
    }

    /**
     * Get the rail channel
     *
     * @return PixelChannel
     */
    public function getRail(): PixelChannel
    {
        return $this->getChannel('rail');
    }

    /**
     * Get the bell channel
     *
     * @return PixelChannel
     */
    public function getBell(): PixelChannel
    {
        return $this->getChannel('bell');
    }

    /**
     * Run success celebration animation
     *
     * @param int $duration_ms Animation duration
     * @return static
     */
    public function celebrateSuccess(int $duration_ms = 3000): static
    {
        return $this->animate(BusAnimation::HIGH_STRIKER_SUCCESS, $duration_ms);
    }

    /**
     * Run encouraging fail animation
     *
     * @param int $duration_ms Animation duration
     * @return static
     */
    public function encourageFail(int $duration_ms = 2000): static
    {
        return $this->animate(BusAnimation::HIGH_STRIKER_FAIL, $duration_ms);
    }
}
