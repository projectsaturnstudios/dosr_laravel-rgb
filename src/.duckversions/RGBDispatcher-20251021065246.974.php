<?php

namespace DeptOfScrapyardRobotics\LaravelRGB;

use Phpdafruit\NeoPixels\PixelChannel;
use Illuminate\Support\Facades\Log;

class RGBDispatcher
{
    protected bool $logging_enabled = false;
    protected bool $use_async = false;

    public function __construct(
        protected readonly PixelChannel $channel
    ) {}

    /**
     * Magic method to proxy all calls to the underlying PixelChannel
     * This allows access to DeviceShape-specific methods (RGBStrip, DoubleDots, etc.)
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $method, array $arguments): mixed
    {
        if ($this->logging_enabled) {
            Log::debug("RGBDispatcher: Calling {$method}", [
                'arguments' => $arguments,
                'channel_type' => get_class($this->channel)
            ]);
        }

        if (!method_exists($this->channel, $method)) {
            throw new \BadMethodCallException(
                "Method {$method} does not exist on " . get_class($this->channel)
            );
        }

        $result = $this->channel->$method(...$arguments);

        // If the result is the channel itself (fluent interface), return dispatcher
        return $result === $this->channel ? $this : $result;
    }

    public function withAsync(): static
    {
        $this->use_async = true;
        return $this;
    }

    public function withoutAsync(): static
    {
        $this->use_async = false;
        return $this;
    }

    public function fire(): static
    {

        return $this;
    }

    /**
     * Get the underlying PixelChannel instance
     *
     * @return PixelChannel
     */
    public function channel(): PixelChannel
    {
        return $this->channel;
    }

    /**
     * Enable/disable logging for debugging
     *
     * @param bool $enabled
     * @return static
     */
    public function enableLogging(bool $enabled = true): static
    {
        $this->logging_enabled = $enabled;
        return $this;
    }

    /**
     * Quick color set with immediate show
     *
     * @param int $color
     * @param int|null $brightness
     * @return static
     */
    public function setColor(int $color, ?int $brightness = null): static
    {
        if ($brightness !== null) {
            $this->channel->setBrightness($brightness);
        }
        $this->channel->fill($color)->show();
        return $this;
    }

    /**
     * Turn off all LEDs
     *
     * @return static
     */
    public function off(): static
    {
        $this->channel->clear()->show();
        return $this;
    }

    /**
     * Quick status flash using common colors
     *
     * @param string $status 'success', 'error', 'warning', 'info', 'ready'
     * @param int $blinks
     * @return static
     */
    public function status(string $status, int $blinks = 2): static
    {
        $colors = [
            'success' => 0x00FF00,  // Green
            'error' => 0xFF0000,    // Red
            'warning' => 0xFFFF00,  // Yellow
            'info' => 0x0000FF,     // Blue
            'ready' => 0x00FFFF,    // Cyan
        ];

        $color = $colors[$status] ?? 0xFFFFFF;

        for ($i = 0; $i < $blinks; $i++) {
            $this->channel->fill($color)->show();
            usleep(150000);
            $this->channel->clear()->show();
            if ($i < $blinks - 1) {
                usleep(150000);
            }
        }

        return $this;
    }

    /**
     * Execute a callback with the channel, then automatically show()
     *
     * @param callable $callback
     * @return static
     */
    public function batch(callable $callback): static
    {
        $callback($this->channel);
        $this->channel->show();
        return $this;
    }

    /**
     * Safe execution - catches exceptions and logs them
     *
     * @param callable $callback
     * @param bool $clearOnError
     * @return static
     */
    public function safe(callable $callback, bool $clearOnError = true): static
    {
        try {
            $callback($this);
        } catch (\Throwable $e) {
            Log::error("RGBDispatcher error", [
                'error' => $e->getMessage(),
                'channel_type' => get_class($this->channel)
            ]);

            if ($clearOnError) {
                $this->off();
            }
        }

        return $this;
    }

    /**
     * Get current brightness level
     *
     * @return int
     */
    public function brightness(): int
    {
        return $this->channel->getBrightness();
    }

    /**
     * Get the number of pixels in this channel
     *
     * @return int
     */
    public function count(): int
    {
        return $this->channel->getPixelCount();
    }

    /**
     * Check if this is a specific device shape
     *
     * @param string $shape 'strip', 'single', 'double'
     * @return bool
     */
    public function isShape(string $shape): bool
    {
        $class = get_class($this->channel);

        return match($shape) {
            'strip' => str_contains($class, 'RGBStrip'),
            'single' => str_contains($class, 'SingleDiode'),
            'double' => str_contains($class, 'DoubleDots'),
            default => false,
        };
    }

    /**
     * Get the shape type as a string
     *
     * @return string
     */
    public function shape(): string
    {
        $class = get_class($this->channel);

        return match(true) {
            str_contains($class, 'RGBStrip') => 'strip',
            str_contains($class, 'SingleDiode') => 'single',
            str_contains($class, 'DoubleDots') => 'double',
            default => 'unknown',
        };
    }
}
