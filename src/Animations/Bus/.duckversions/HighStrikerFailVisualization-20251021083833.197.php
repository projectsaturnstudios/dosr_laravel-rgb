<?php

namespace DeptOfScrapyardRobotics\LaravelRGB\Animations\Bus;

use DeptOfScrapyardRobotics\LaravelRGB\Animations\BusVisualization;
use DeptOfScrapyardRobotics\LaravelRGB\AnimatablePixelBus;
use DeptOfScrapyardRobotics\LaravelRGB\Enums\BusAnimation;
use PhpdaFruit\NeoPixels\Animations\Effects\FadeEffect;

/**
 * HighStrikerFailVisualization
 * 
 * "Try again" encouragement animation for high striker
 * - Bell stays dim
 * - Rail shows "almost there" pattern
 * - Encouraging color sequence
 */
class HighStrikerFailVisualization extends BusVisualization
{
    use FadeEffect;

    public function getAnimationType(): BusAnimation
    {
        return BusAnimation::HIGH_STRIKER_FAIL;
    }

    public function getDefaultOptions(): array
    {
        return [
            'try_again_color' => 0xFFA500,  // Orange (encouraging, not harsh)
            'dim_bell_color' => 0x202020,   // Very dim
            'pulse_speed_ms' => 150,
            'pulses' => 3,
            'fade_duration_ms' => 800,
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
        
        // Bell stays dim throughout
        $bell->fill($opts['dim_bell_color'])->show();
        
        // Phase 1: "Almost there" pulses from bottom
        for ($pulse = 0; $pulse < $opts['pulses']; $pulse++) {
            // Fill to about 70% height to show "you were close"
            $targetHeight = (int)($railCount * 0.7);
            
            for ($pixel = 0; $pixel < $targetHeight; $pixel++) {
                $brightness = 1.0 - ($pixel / $targetHeight) * 0.3; // Dim towards top
                $color = $this->dimColor($opts['try_again_color'], $brightness);
                $rail->setPixelColorHex($pixel, $color);
                $rail->show();
                usleep(30000);
            }
            
            // Hold
            usleep($opts['pulse_speed_ms'] * 1000);
            
            // Fade out
            $steps = 10;
            for ($step = 0; $step < $steps; $step++) {
                $fadeFactor = 1.0 - ($step / $steps);
                for ($pixel = 0; $pixel < $targetHeight; $pixel++) {
                    $current = $rail->getPixelColor($pixel);
                    if ($current > 0) {
                        $rail->setPixelColorHex($pixel, $this->dimColor($current, 0.8));
                    }
                }
                $rail->show();
                usleep(($opts['fade_duration_ms'] / $steps) * 1000);
            }
            
            $rail->clear()->show();
            usleep(200000);
        }
        
        // Phase 2: Encouraging bottom-up sweep (showing potential)
        for ($i = 0; $i < 2; $i++) {
            for ($pixel = 0; $pixel < $railCount; $pixel++) {
                $rail->clear();
                
                // Light up pixels from bottom to current
                for ($p = 0; $p <= $pixel; $p++) {
                    $brightness = 0.5 + (0.5 * ($p / $railCount));
                    $color = $this->dimColor($opts['try_again_color'], $brightness);
                    $rail->setPixelColorHex($p, $color);
                }
                
                $rail->show();
                usleep(40000);
            }
        }
        
        // Clear everything
        $rail->clear()->show();
        $bell->clear()->show();
    }
}

