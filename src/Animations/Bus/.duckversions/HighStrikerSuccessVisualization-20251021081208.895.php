<?php

namespace DeptOfScrapyardRobotics\LaravelRGB\Animations\Bus;

use DeptOfScrapyardRobotics\LaravelRGB\Animations\BusVisualization;
use DeptOfScrapyardRobotics\LaravelRGB\AnimatablePixelBus;
use DeptOfScrapyardRobotics\LaravelRGB\Enums\BusAnimation;
use PhpdaFruit\NeoPixels\Animations\Effects\FadeEffect;
use PhpdaFruit\NeoPixels\Animations\Effects\RandomEffect;

/**
 * HighStrikerSuccessVisualization
 * 
 * Victory celebration animation for high striker
 * - Bell flashes rapidly
 * - Rail fills with victory colors from bottom to top
 * - Sparkle effect
 * - Celebration pattern
 */
class HighStrikerSuccessVisualization extends BusVisualization
{
    use FadeEffect, RandomEffect;

    public function getAnimationType(): BusAnimation
    {
        return BusAnimation::HIGH_STRIKER_SUCCESS;
    }

    public function getDefaultOptions(): array
    {
        return [
            'victory_colors' => [0xFFD700, 0xFF4500, 0xFF1493], // Gold, Orange-Red, Hot Pink
            'bell_flash_speed_ms' => 100,
            'bell_flashes' => 6,
            'fill_speed_ms' => 50,
            'sparkle_duration_ms' => 1000,
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
        
        // Phase 1: Bell flashes
        for ($i = 0; $i < $opts['bell_flashes']; $i++) {
            $color = $opts['victory_colors'][array_rand($opts['victory_colors'])];
            $bell->fill($color)->show();
            usleep($opts['bell_flash_speed_ms'] * 1000);
            
            $bell->clear()->show();
            usleep($opts['bell_flash_speed_ms'] * 1000);
        }
        
        // Phase 2: Victory fill from bottom to top
        $colorIndex = 0;
        for ($pixel = 0; $pixel < $railCount; $pixel++) {
            $color = $opts['victory_colors'][$colorIndex % count($opts['victory_colors'])];
            $rail->setPixelColorHex($pixel, $color);
            $rail->show();
            
            // Bell pulses in sync
            $bellColor = $this->dimColor($color, 0.8);
            $bell->fill($bellColor)->show();
            
            usleep($opts['fill_speed_ms'] * 1000);
            $colorIndex++;
        }
        
        // Phase 3: Sparkle celebration
        $iterations = (int)($opts['sparkle_duration_ms'] / 50);
        for ($i = 0; $i < $iterations; $i++) {
            // Random sparkles on rail
            for ($s = 0; $s < 3; $s++) {
                $pixel = rand(0, $railCount - 1);
                $color = $this->randomColorFromPalette($opts['victory_colors']);
                $rail->setPixelColorHex($pixel, $color);
            }
            
            // Bell alternates between colors
            $bellColor = $opts['victory_colors'][$i % count($opts['victory_colors'])];
            $bell->fill($bellColor)->show();
            
            $rail->show();
            usleep(50000);
            
            // Dim existing pixels
            for ($p = 0; $p < $railCount; $p++) {
                $current = $rail->getPixelColor($p);
                if ($current > 0) {
                    $rail->setPixelColorHex($p, $this->dimColor($current, 0.85));
                }
            }
        }
        
        // Clear everything
        $rail->clear()->show();
        $bell->clear()->show();
    }
}

