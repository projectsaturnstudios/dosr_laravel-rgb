<?php

namespace DeptOfScrapyardRobotics\LaravelRGB\Contracts;

use DeptOfScrapyardRobotics\LaravelRGB\AnimatablePixelBus;
use DeptOfScrapyardRobotics\LaravelRGB\Enums\BusAnimation;

/**
 * BusAnimationVisualization Interface
 * 
 * Defines complete animation sequences that run across multiple LED channels
 * in an AnimatablePixelBus. These animations coordinate between channels.
 */
interface BusAnimationVisualization
{
    /**
     * Run the animation on a bus
     * 
     * @param AnimatablePixelBus $bus The LED bus to animate
     * @param int $duration_ms Duration in milliseconds
     * @param array $options Animation-specific options
     * @return void
     */
    public function run(AnimatablePixelBus $bus, int $duration_ms, array $options = []): void;

    /**
     * Get the animation type this visualization implements
     * 
     * @return BusAnimation
     */
    public function getAnimationType(): BusAnimation;

    /**
     * Get the animation name
     * 
     * @return string
     */
    public function getName(): string;

    /**
     * Get default options for this animation
     * 
     * @return array
     */
    public function getDefaultOptions(): array;

    /**
     * Check if this animation is compatible with the given bus
     * 
     * @param AnimatablePixelBus $bus
     * @return bool
     */
    public function isCompatible(AnimatablePixelBus $bus): bool;

    /**
     * Get required channel names for this animation
     * 
     * @return array<string> Array of required channel names
     */
    public function getRequiredChannels(): array;
}


