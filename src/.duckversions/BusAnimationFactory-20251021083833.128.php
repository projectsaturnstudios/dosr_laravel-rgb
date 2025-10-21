<?php

namespace DeptOfScrapyardRobotics\LaravelRGB;

use DeptOfScrapyardRobotics\LaravelRGB\Enums\BusAnimation;
use DeptOfScrapyardRobotics\LaravelRGB\Contracts\BusAnimationVisualization;
use DeptOfScrapyardRobotics\LaravelRGB\Exceptions\BusAnimationNotFoundException;

/**
 * BusAnimationFactory
 * 
 * Factory for creating and managing bus-level animation visualizations
 * Maps BusAnimation enum cases to concrete visualization classes
 */
class BusAnimationFactory
{
    /**
     * Registry mapping BusAnimation enum to visualization class names
     * 
     * @var array<string, class-string<BusAnimationVisualization>>
     */
    protected static array $registry = [];

    /**
     * Cache of instantiated visualizations
     * 
     * @var array<string, BusAnimationVisualization>
     */
    protected static array $instances = [];

    /**
     * Register a bus animation visualization
     * 
     * @param BusAnimation $animation
     * @param class-string<BusAnimationVisualization> $visualizationClass
     * @return void
     */
    public static function register(BusAnimation $animation, string $visualizationClass): void
    {
        if (!is_subclass_of($visualizationClass, BusAnimationVisualization::class)) {
            throw new \InvalidArgumentException(
                "Visualization class must implement BusAnimationVisualization interface"
            );
        }

        static::$registry[$animation->value] = $visualizationClass;
    }

    /**
     * Register multiple animations at once
     * 
     * @param array<BusAnimation, class-string<BusAnimationVisualization>> $mappings
     * @return void
     */
    public static function registerBatch(array $mappings): void
    {
        foreach ($mappings as $animation => $visualizationClass) {
            static::register($animation, $visualizationClass);
        }
    }

    /**
     * Create or retrieve a visualization for the given animation
     * 
     * @param BusAnimation $animation
     * @return BusAnimationVisualization
     * @throws BusAnimationNotFoundException
     */
    public static function create(BusAnimation $animation): BusAnimationVisualization
    {
        // Return cached instance if exists
        if (isset(static::$instances[$animation->value])) {
            return static::$instances[$animation->value];
        }

        // Check if animation is registered
        if (!isset(static::$registry[$animation->value])) {
            throw new BusAnimationNotFoundException(
                "No visualization registered for bus animation: {$animation->getName()}"
            );
        }

        // Instantiate and cache
        $visualizationClass = static::$registry[$animation->value];
        $instance = new $visualizationClass();

        static::$instances[$animation->value] = $instance;

        return $instance;
    }

    /**
     * Check if an animation has a registered visualization
     * 
     * @param BusAnimation $animation
     * @return bool
     */
    public static function has(BusAnimation $animation): bool
    {
        return isset(static::$registry[$animation->value]);
    }

    /**
     * Get all registered animations
     * 
     * @return array<BusAnimation>
     */
    public static function getRegistered(): array
    {
        return array_map(
            fn($value) => BusAnimation::from($value),
            array_keys(static::$registry)
        );
    }

    /**
     * Clear all registrations and cached instances
     * 
     * @return void
     */
    public static function clear(): void
    {
        static::$registry = [];
        static::$instances = [];
    }

    /**
     * Auto-discover and register all visualizations in a directory
     * 
     * @param string $directory
     * @param string $namespace
     * @return int Number of registered animations
     */
    public static function autoDiscover(string $directory, string $namespace): int
    {
        $count = 0;

        if (!is_dir($directory)) {
            return $count;
        }

        $files = glob($directory . '/*.php');

        foreach ($files as $file) {
            $className = $namespace . '\\' . basename($file, '.php');

            if (!class_exists($className)) {
                continue;
            }

            if (!is_subclass_of($className, BusAnimationVisualization::class)) {
                continue;
            }

            try {
                $instance = new $className();
                $animation = $instance->getAnimationType();
                static::register($animation, $className);
                $count++;
            } catch (\Throwable $e) {
                // Skip classes that can't be instantiated or don't have proper animation type
                continue;
            }
        }

        return $count;
    }
}

