<?php

namespace DeptOfScrapyardRobotics\LaravelRGB\Animations\Bus;

use DeptOfScrapyardRobotics\LaravelRGB\Animations\BusVisualization;
use DeptOfScrapyardRobotics\LaravelRGB\Animations\Effects\PhysicsEffect;
use DeptOfScrapyardRobotics\LaravelRGB\AnimatablePixelBus;
use DeptOfScrapyardRobotics\LaravelRGB\Enums\BusAnimation;
use PhpdaFruit\NeoPixels\Animations\Effects\FadeEffect;

/**
 * HighStrikerPhysicsVisualization
 * 
 * Carnival high striker game with realistic physics simulation
 * - Random initial velocity (hammer impact)
 * - Gravity pulls puck down as it rises
 * - Bell rings if puck reaches top
 * - Bounces back down with energy loss
 * - Stops when momentum depleted
 */
class HighStrikerPhysicsVisualization extends BusVisualization
{
    use PhysicsEffect, FadeEffect;

    public function getAnimationType(): BusAnimation
    {
        return BusAnimation::HIGH_STRIKER_PHYSICS;
    }

    public function getDefaultOptions(): array
    {
        return [
            'min_velocity' => 50,          // Minimum random velocity
            'max_velocity' => 150,         // Maximum random velocity  
            'gravity' => 98.0,             // Gravity constant (higher = faster fall)
            'restitution' => 0.6,          // Bounce dampening (0.6 = 60% energy retained)
            'bell_threshold' => 0.9,       // How close to top counts as success (0.9 = 90%)
            'puck_color' => 0xFF0000,      // Red puck
            'trail_length' => 3,           // Puck trail effect
            'bell_color_success' => 0xFFD700, // Gold bell when hit
            'bell_color_fail' => 0x404040,    // Dim bell when missed
            'time_step' => 0.02,           // Physics time step (seconds)
            'pixel_scale' => 100.0,        // Scale factor for position->pixel conversion
        ];
    }

    public function getRequiredChannels(): array
    {
        return ['rail', 'bell'];
    }

    public function run(AnimatablePixelBus $bus, int $duration_ms, array $options = []): void
    {
        $opts = array_merge($this->getDefaultOptions(), $options);
        
        $channels = $bus->channels();
        $rail = $channels['rail'];
        $bell = $channels['bell'];
        
        $railCount = $rail->getPixelCount();
        $bellTarget = $railCount * $opts['bell_threshold'];
        
        // Random initial velocity (impact strength)
        $initialVelocity = (float)rand($opts['min_velocity'], $opts['max_velocity']);
        $velocity = $initialVelocity;
        $position = 0.0; // Starting at bottom
        
        $hitBell = false;
        $bounceCount = 0;
        $maxBounces = 5;
        
        $startTime = microtime(true);
        
        // Physics simulation loop
        while (true) {
            $elapsed = (microtime(true) - $startTime) * 1000;
            if ($elapsed >= $duration_ms) {
                break;
            }
            
            // Update physics
            $position += $velocity * $opts['time_step'];
            $velocity = $this->applyGravity($velocity, $opts['gravity'], $opts['time_step']);
            
            // Check if hit bell (reached top while moving up)
            if (!$hitBell && $position >= $bellTarget && $velocity > 0) {
                $hitBell = true;
                
                // Flash bell success
                $bell->fill($opts['bell_color_success'])->show();
            }
            
            // Check if hit ground (bounce)
            if ($position <= 0 && $velocity < 0) {
                $position = 0;
                $velocity = $this->calculateBounce($velocity, $opts['restitution']);
                $bounceCount++;
                
                // Stop if too many bounces or energy depleted
                if ($bounceCount >= $maxBounces || $this->isDepleted($velocity, 5.0)) {
                    break;
                }
            }
            
            // Convert position to pixel
            $pixelPos = $this->positionToPixel($position / $opts['pixel_scale'], $railCount);
            $pixelPos = max(0, min($railCount - 1, $pixelPos));
            
            // Clear and draw puck with trail
            $rail->clear();
            
            for ($t = 0; $t < $opts['trail_length']; $t++) {
                $trailPixel = $pixelPos - $t;
                if ($trailPixel >= 0 && $trailPixel < $railCount) {
                    $brightness = 1.0 - ($t / $opts['trail_length']);
                    $color = $this->dimColor($opts['puck_color'], $brightness);
                    $rail->setPixelColorHex($trailPixel, $color);
                }
            }
            
            $rail->show();
            
            // Small delay for animation smoothness
            usleep(20000); // 20ms = ~50fps
        }
        
        // Final state
        if ($hitBell) {
            // Success animation
            $bell->fill($opts['bell_color_success'])->show();
        } else {
            // Fail animation
            $bell->fill($opts['bell_color_fail'])->show();
        }
        
        $rail->clear()->show();
        usleep(500000); // Hold final state for 500ms
        
        $bell->clear()->show();
    }
}


