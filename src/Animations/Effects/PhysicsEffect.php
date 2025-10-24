<?php

namespace DeptOfScrapyardRobotics\LaravelRGB\Animations\Effects;

/**
 * PhysicsEffect Trait
 * 
 * Provides realistic physics calculations for animations
 * including gravity, trajectory, momentum, and bouncing
 */
trait PhysicsEffect
{
    /**
     * Calculate position based on initial velocity, gravity, and time
     * Uses kinematic equation: s = v₀t + ½at²
     * 
     * @param float $velocity Initial velocity (upward positive)
     * @param float $gravity Gravity constant (positive, pulled downward)
     * @param float $time Time elapsed
     * @return float Position (0 = starting point)
     */
    protected function calculateTrajectory(float $velocity, float $gravity, float $time): float
    {
        return ($velocity * $time) - (0.5 * $gravity * $time * $time);
    }

    /**
     * Calculate impact force from velocity
     * F = mv (simplified, mass assumed constant)
     * 
     * @param float $velocity Impact velocity
     * @return float Impact force (arbitrary units)
     */
    protected function calculateImpactForce(float $velocity): float
    {
        return abs($velocity);
    }

    /**
     * Apply gravity to current velocity
     * v = v₀ - at
     * 
     * @param float $velocity Current velocity
     * @param float $gravity Gravity constant
     * @param float $deltaTime Time step
     * @return float New velocity
     */
    protected function applyGravity(float $velocity, float $gravity, float $deltaTime): float
    {
        return $velocity - ($gravity * $deltaTime);
    }

    /**
     * Calculate bounce velocity after impact
     * 
     * @param float $velocity Velocity before impact (should be negative when falling)
     * @param float $restitution Coefficient of restitution (0 = no bounce, 1 = perfect bounce)
     * @return float Velocity after bounce (positive = upward)
     */
    protected function calculateBounce(float $velocity, float $restitution): float
    {
        return -$velocity * $restitution;
    }

    /**
     * Check if position has reached target within tolerance
     * 
     * @param float $position Current position
     * @param float $target Target position
     * @param float $tolerance Acceptable distance from target
     * @return bool True if within tolerance
     */
    protected function hasReachedTarget(float $position, float $target, float $tolerance = 0.1): bool
    {
        return abs($position - $target) <= $tolerance;
    }

    /**
     * Calculate momentum (mass × velocity)
     * 
     * @param float $velocity Current velocity
     * @param float $mass Object mass
     * @return float Momentum
     */
    protected function calculateMomentum(float $velocity, float $mass = 1.0): float
    {
        return $mass * $velocity;
    }

    /**
     * Check if energy has depleted below threshold
     * 
     * @param float $energy Current energy level
     * @param float $threshold Minimum energy threshold
     * @return bool True if depleted
     */
    protected function isDepleted(float $energy, float $threshold = 0.1): bool
    {
        return abs($energy) < $threshold;
    }

    /**
     * Calculate kinetic energy (½mv²)
     * 
     * @param float $velocity Current velocity
     * @param float $mass Object mass
     * @return float Kinetic energy
     */
    protected function calculateKineticEnergy(float $velocity, float $mass = 1.0): float
    {
        return 0.5 * $mass * $velocity * $velocity;
    }

    /**
     * Convert position to pixel index on strip
     * 
     * @param float $position Position in physics space (0.0 to 1.0)
     * @param int $pixelCount Number of pixels
     * @return int Pixel index
     */
    protected function positionToPixel(float $position, int $pixelCount): int
    {
        $position = max(0.0, min(1.0, $position));
        return (int)floor($position * ($pixelCount - 1));
    }

    /**
     * Calculate time to reach peak height
     * t = v₀/g
     * 
     * @param float $velocity Initial velocity
     * @param float $gravity Gravity constant
     * @return float Time to peak
     */
    protected function timeToApex(float $velocity, float $gravity): float
    {
        if ($gravity <= 0) {
            return 0.0;
        }
        return $velocity / $gravity;
    }

    /**
     * Calculate maximum height reached
     * h = v₀²/(2g)
     * 
     * @param float $velocity Initial velocity
     * @param float $gravity Gravity constant
     * @return float Maximum height
     */
    protected function maxHeight(float $velocity, float $gravity): float
    {
        if ($gravity <= 0) {
            return 0.0;
        }
        return ($velocity * $velocity) / (2 * $gravity);
    }
}


