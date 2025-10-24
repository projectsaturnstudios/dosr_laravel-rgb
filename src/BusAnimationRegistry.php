<?php

namespace DeptOfScrapyardRobotics\LaravelRGB;

use DeptOfScrapyardRobotics\LaravelRGB\Enums\BusAnimation;

/**
 * BusAnimationRegistry
 * 
 * Central registry for registering all bus-level animations
 */
class BusAnimationRegistry
{
    /**
     * Register all built-in bus animations
     * 
     * @return int Number of animations registered
     */
    public static function registerAll(): int
    {
        $registrations = [
            // Carnival/Game Effects
            BusAnimation::HIGH_STRIKER_PHYSICS => \DeptOfScrapyardRobotics\LaravelRGB\Animations\Bus\HighStrikerPhysicsVisualization::class,
            BusAnimation::HIGH_STRIKER_SUCCESS => \DeptOfScrapyardRobotics\LaravelRGB\Animations\Bus\HighStrikerSuccessVisualization::class,
            BusAnimation::HIGH_STRIKER_FAIL => \DeptOfScrapyardRobotics\LaravelRGB\Animations\Bus\HighStrikerFailVisualization::class,
        ];

        BusAnimationFactory::registerBatch($registrations);

        return count($registrations);
    }

    /**
     * Auto-discover and register visualizations from the Bus directory
     * 
     * @return int Number of animations registered
     */
    public static function autoDiscover(): int
    {
        $directory = __DIR__ . '/Animations/Bus';
        $namespace = 'DeptOfScrapyardRobotics\\LaravelRGB\\Animations\\Bus';
        
        return BusAnimationFactory::autoDiscover($directory, $namespace);
    }

    /**
     * Initialize the bus animation system
     * Uses auto-discovery by default
     * 
     * @param bool $useAutoDiscovery If true, auto-discover; if false, use manual registration
     * @return int Number of animations registered
     */
    public static function initialize(bool $useAutoDiscovery = true): int
    {
        if ($useAutoDiscovery) {
            return static::autoDiscover();
        }
        
        return static::registerAll();
    }
}


