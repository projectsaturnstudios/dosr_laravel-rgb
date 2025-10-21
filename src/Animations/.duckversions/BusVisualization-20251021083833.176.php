<?php

namespace DeptOfScrapyardRobotics\LaravelRGB\Animations;

use DeptOfScrapyardRobotics\LaravelRGB\Contracts\BusAnimationVisualization;
use DeptOfScrapyardRobotics\LaravelRGB\AnimatablePixelBus;

/**
 * Abstract BusVisualization Base Class
 * 
 * Provides common functionality for bus-level animation visualizations
 */
abstract class BusVisualization implements BusAnimationVisualization
{
    /**
     * Get the animation name (derived from class name by default)
     */
    public function getName(): string
    {
        $className = class_basename(static::class);
        return str_replace('Visualization', '', $className);
    }

    /**
     * Get default options (can be overridden by child classes)
     */
    public function getDefaultOptions(): array
    {
        return [];
    }

    /**
     * Check if animation is compatible (default: check required channels exist)
     * Override for specific bus type requirements
     */
    public function isCompatible(AnimatablePixelBus $bus): bool
    {
        $requiredChannels = $this->getRequiredChannels();
        $busChannels = array_keys($bus->channels());
        
        foreach ($requiredChannels as $required) {
            if (!in_array($required, $busChannels)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get required channels (can be overridden by child classes)
     * Default: no specific requirements
     */
    public function getRequiredChannels(): array
    {
        return [];
    }
}

