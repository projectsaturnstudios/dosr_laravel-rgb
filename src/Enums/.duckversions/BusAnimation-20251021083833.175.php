<?php

namespace DeptOfScrapyardRobotics\LaravelRGB\Enums;

/**
 * BusAnimation Enum
 * 
 * Multi-channel animations that require coordinated control
 * across multiple LED channels in an AnimatablePixelBus
 */
enum BusAnimation: string
{
    // ========================================
    // CARNIVAL/GAME EFFECTS
    // ========================================
    case HIGH_STRIKER_PHYSICS = 'high_striker_physics';
    case HIGH_STRIKER_SUCCESS = 'high_striker_success';
    case HIGH_STRIKER_FAIL = 'high_striker_fail';
    
    // ========================================
    // MULTI-CHANNEL SYNCHRONIZED EFFECTS
    // ========================================
    case SYNCHRONIZED_RAINBOW = 'synchronized_rainbow';
    case CHANNEL_CHASE = 'channel_chase';
    case ALTERNATING_PULSE = 'alternating_pulse';
    case WAVE_ACROSS_CHANNELS = 'wave_across_channels';
    case MIRRORED_ANIMATION = 'mirrored_animation';

    /**
     * Get animation display name
     */
    public function getName(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }

    /**
     * Get animation category
     */
    public function getCategory(): string
    {
        return match (true) {
            in_array($this, [
                self::HIGH_STRIKER_PHYSICS,
                self::HIGH_STRIKER_SUCCESS,
                self::HIGH_STRIKER_FAIL
            ]) => 'Carnival/Game',
            
            in_array($this, [
                self::SYNCHRONIZED_RAINBOW,
                self::CHANNEL_CHASE,
                self::ALTERNATING_PULSE,
                self::WAVE_ACROSS_CHANNELS,
                self::MIRRORED_ANIMATION
            ]) => 'Multi-Channel Synchronized',
            
            default => 'Other'
        };
    }

    /**
     * Check if animation requires a specific bus type
     */
    public function requiresSpecificBus(): ?string
    {
        return match ($this) {
            self::HIGH_STRIKER_PHYSICS,
            self::HIGH_STRIKER_SUCCESS,
            self::HIGH_STRIKER_FAIL => 'HighStriker',
            default => null
        };
    }

    /**
     * Get all animations by category
     */
    public static function getByCategory(string $category): array
    {
        return array_filter(
            self::cases(),
            fn($anim) => $anim->getCategory() === $category
        );
    }
}

