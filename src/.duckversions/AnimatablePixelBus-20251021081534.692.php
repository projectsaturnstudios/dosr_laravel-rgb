<?php

namespace DeptOfScrapyardRobotics\LaravelRGB;

use PhpdaFruit\NeoPixels\PixelBus;
use PhpdaFruit\NeoPixels\PixelChannel;
use DeptOfScrapyardRobotics\LaravelRGB\Enums\BusAnimation;
use Illuminate\Support\Facades\Concurrency;

abstract class AnimatablePixelBus extends PixelBus
{
    protected bool $use_async = false;
    protected array $operation_queue = [];

    /**
     * Enable async mode - operations will be queued until fire() is called
     *
     * @return static
     */
    public function withAsync(): static
    {
        $this->use_async = true;
        return $this;
    }

    /**
     * Disable async mode - operations execute immediately
     *
     * @return static
     */
    public function withoutAsync(): static
    {
        $this->use_async = false;
        return $this;
    }

    /**
     * Execute queued operations using Laravel Concurrency
     *
     * @return static
     */
    public function fire(): static
    {
        if (empty($this->operation_queue)) {
            return $this;
        }

        $queue = $this->operation_queue;
        $this->operation_queue = []; // Clear queue

        $driver = config('rgb-lighting.async_concurrency_driver', 'sync');
        $bus = $this; // Pass the entire bus object

        // Execute the queued operations
        Concurrency::driver($driver)->run([
            function() use ($bus, $queue) {
                foreach ($queue as $operation) {
                    if ($operation['type'] === 'wait') {
                        usleep($operation['duration'] * 1000);
                    } elseif ($operation['type'] === 'method') {
                        $channel = $operation['channel'] ?? null;
                        $method = $operation['method'];
                        $args = $operation['args'];
                        
                        if ($channel) {
                            // Execute on specific channel
                            $channelObj = $bus->getChannel($channel);
                            if ($channelObj) {
                                $channelObj->$method(...$args);
                            }
                        } else {
                            // Execute on all channels
                            foreach ($bus->channels() as $ch) {
                                $ch->$method(...$args);
                            }
                        }
                    } elseif ($operation['type'] === 'animation') {
                        $animation = $operation['animation'];
                        $duration = $operation['duration'];
                        $options = $operation['options'];
                        
                        // Get visualization from factory
                        $visualization = BusAnimationFactory::create($animation);
                        $visualization->run($bus, $duration, $options);
                    }
                }
            }
        ]);

        return $this;
    }

    /**
     * Wait/sleep for a duration (milliseconds)
     *
     * @param int $milliseconds
     * @return static
     */
    public function wait(int $milliseconds): static
    {
        if ($this->use_async) {
            $this->operation_queue[] = [
                'type' => 'wait',
                'duration' => $milliseconds
            ];
            return $this;
        }

        // Execute immediately
        usleep($milliseconds * 1000);
        return $this;
    }

    /**
     * Run a bus-level animation
     *
     * @param BusAnimation $animation
     * @param int $duration_ms
     * @param array $options
     * @return static
     */
    public function animate(BusAnimation $animation, int $duration_ms = 5000, array $options = []): static
    {
        if ($this->use_async) {
            $this->operation_queue[] = [
                'type' => 'animation',
                'animation' => $animation,
                'duration' => $duration_ms,
                'options' => $options
            ];
            return $this;
        }

        // Execute immediately
        $visualization = BusAnimationFactory::create($animation);
        
        if (!$visualization->isCompatible($this)) {
            throw new \RuntimeException(
                "Animation '{$animation->getName()}' is not compatible with this bus type"
            );
        }

        $mergedOptions = array_merge($visualization->getDefaultOptions(), $options);
        $visualization->run($this, $duration_ms, $mergedOptions);

        return $this;
    }

    /**
     * Magic method to proxy calls to channels
     * Can target specific channel or all channels
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $method, array $arguments): mixed
    {
        // Check if method exists on PixelChannel
        if (!method_exists(PixelChannel::class, $method)) {
            throw new \BadMethodCallException(
                "Method {$method} does not exist on PixelChannel"
            );
        }

        if ($this->use_async) {
            $this->operation_queue[] = [
                'type' => 'method',
                'channel' => null, // null means all channels
                'method' => $method,
                'args' => $arguments
            ];
            return $this;
        }

        // Execute immediately on all channels
        foreach ($this->pixel_sources as $channel) {
            $channel->$method(...$arguments);
        }

        return $this;
    }

    /**
     * Get all channels
     *
     * @return array<string, PixelChannel>
     */
    public function channels(): array
    {
        return $this->pixel_sources;
    }

    /**
     * Clear all channels
     *
     * @return static
     */
    public function clearAll(): static
    {
        if ($this->use_async) {
            $this->operation_queue[] = [
                'type' => 'method',
                'channel' => null,
                'method' => 'clear',
                'args' => []
            ];
            return $this;
        }

        foreach ($this->pixel_sources as $channel) {
            $channel->clear();
        }

        return $this;
    }

    /**
     * Show all channels
     *
     * @return static
     */
    public function showAll(): static
    {
        if ($this->use_async) {
            $this->operation_queue[] = [
                'type' => 'method',
                'channel' => null,
                'method' => 'show',
                'args' => []
            ];
            return $this;
        }

        foreach ($this->pixel_sources as $channel) {
            $channel->show();
        }

        return $this;
    }
}
