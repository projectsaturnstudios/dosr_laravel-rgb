<?php

namespace DeptOfScrapyardRobotics\LaravelRGB\Console\Commands;

use Illuminate\Console\Command;
use DeptOfScrapyardRobotics\LaravelRGB\Facades\LightingSetup;
use PhpdaFruit\NeoPixels\Enums\Animation;
use PhpdaFruit\NeoPixels\AnimationRegistry;
use PhpdaFruit\NeoPixels\PixelChannel;

class PlayAnimationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rgb:animate 
                            {animation : The animation to play (e.g., rainbow, meteor_shower)}
                            {device=all : Device name from config or "all" to run on all devices}
                            {--duration= : Duration in milliseconds (optional, runs forever if not set)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Play an animation on RGB LED devices';

    /**
     * Whether shutdown has been initiated
     */
    protected bool $shouldShutdown = false;

    /**
     * Devices being used
     */
    protected array $devices = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Register signal handlers for graceful shutdown
        $this->registerSignalHandlers();

        // Initialize animation registry
        AnimationRegistry::initialize();

        // Get animation argument
        $animationName = $this->argument('animation');
        
        // Try to find the animation
        $animation = $this->findAnimation($animationName);
        
        if (!$animation) {
            $this->error("❌ Animation '{$animationName}' not found!");
            $this->suggestAnimations($animationName);
            return 1;
        }

        // Get device(s)
        $deviceArg = $this->argument('device');
        $this->devices = $this->getDevices($deviceArg);

        if (empty($this->devices)) {
            $this->error("❌ No devices found!");
            return 1;
        }

        // Get duration
        $duration = $this->option('duration');
        $runForever = $duration === null;

        // Display info
        $this->info("🎨 Playing animation: " . $animation->getName());
        $this->info("📺 Device(s): " . implode(', ', array_keys($this->devices)));
        $this->info("⏱️  Duration: " . ($runForever ? "∞ (press Ctrl+C to stop)" : "{$duration}ms"));
        $this->newLine();

        // Run the animation
        try {
            if ($runForever) {
                $this->runForever($animation);
            } else {
                $this->runWithDuration($animation, (int)$duration);
            }
        } catch (\Exception $e) {
            $this->error("Error running animation: " . $e->getMessage());
            $this->gracefulShutdown();
            return 1;
        }

        // Normal completion
        $this->info("✨ Animation complete!");
        $this->gracefulShutdown();

        return 0;
    }

    /**
     * Register signal handlers for graceful shutdown
     */
    protected function registerSignalHandlers(): void
    {
        if (function_exists('pcntl_signal')) {
            // Handle SIGINT (Ctrl+C)
            pcntl_signal(SIGINT, function () {
                $this->shouldShutdown = true;
                $this->newLine();
                $this->warn("🛑 Shutdown signal received...");
            });

            // Handle SIGTERM
            pcntl_signal(SIGTERM, function () {
                $this->shouldShutdown = true;
                $this->newLine();
                $this->warn("🛑 Termination signal received...");
            });

            // Enable async signals
            pcntl_async_signals(true);
        }
    }

    /**
     * Run animation forever (until interrupted)
     */
    protected function runForever(Animation $animation): void
    {
        $iteration = 0;

        while (!$this->shouldShutdown) {
            $iteration++;
            $this->comment("🔄 Iteration #{$iteration}...");

            foreach ($this->devices as $name => $channel) {
                if ($this->shouldShutdown) {
                    break 2;
                }

                try {
                    $channel->animate($animation, 5000); // 5 second cycles
                } catch (\Exception $e) {
                    $this->error("Error on device '{$name}': " . $e->getMessage());
                }
            }

            // Check for shutdown signal
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
        }
    }

    /**
     * Run animation for a specific duration
     */
    protected function runWithDuration(Animation $animation, int $duration_ms): void
    {
        $startTime = microtime(true);
        $endTime = $startTime + ($duration_ms / 1000);

        $this->withProgressBar($this->devices, function ($channel, $name) use ($animation, $duration_ms, $endTime) {
            if ($this->shouldShutdown) {
                return;
            }

            // Calculate remaining time
            $remaining = (int)(($endTime - microtime(true)) * 1000);
            if ($remaining <= 0) {
                return;
            }

            $actualDuration = min($duration_ms, $remaining);

            try {
                $channel->animate($animation, $actualDuration);
            } catch (\Exception $e) {
                $this->error("Error on device '{$name}': " . $e->getMessage());
            }
        });

        $this->newLine();
    }

    /**
     * Graceful shutdown sequence
     */
    protected function gracefulShutdown(): void
    {
        if (empty($this->devices)) {
            return;
        }

        $this->newLine();
        $this->info("🔴 Initiating graceful shutdown...");

        // Phase 1: Turn all devices solid red
        $this->comment("   Setting all devices to red...");
        foreach ($this->devices as $name => $channel) {
            try {
                $channel->fill(0xFF0000)->show();
            } catch (\Exception $e) {
                $this->error("   Error on '{$name}': " . $e->getMessage());
            }
        }

        usleep(400000); // 400ms

        // Phase 2: Turn off devices one by one
        $deviceNames = array_keys($this->devices);
        $totalDevices = count($deviceNames);

        foreach ($deviceNames as $index => $name) {
            $this->comment("   Shutting down '{$name}' [" . ($index + 1) . "/{$totalDevices}]...");
            
            try {
                $this->devices[$name]->clear()->show();
            } catch (\Exception $e) {
                $this->error("   Error on '{$name}': " . $e->getMessage());
            }

            // Don't wait after the last device
            if ($index < $totalDevices - 1) {
                usleep(400000); // 400ms between each
            }
        }

        $this->info("✅ All devices shut down cleanly.");
    }

    /**
     * Find animation by name (case-insensitive)
     */
    protected function findAnimation(string $name): ?Animation
    {
        $name = strtoupper(str_replace(['-', ' '], '_', $name));

        foreach (Animation::cases() as $animation) {
            if (strtoupper($animation->value) === $name) {
                return $animation;
            }
            if ($animation->name === $name) {
                return $animation;
            }
        }

        return null;
    }

    /**
     * Get devices based on argument
     */
    protected function getDevices(string $deviceArg): array
    {
        $devices = [];

        if ($deviceArg === 'all') {
            // Get all configured devices
            $config = config('rgb-lighting.devices', []);
            
            foreach ($config as $name => $deviceConfig) {
                if (!is_array($deviceConfig) || !isset($deviceConfig['shape'])) {
                    continue;
                }

                try {
                    $channel = LightingSetup::getPixelChannel($name);
                    if ($channel instanceof PixelChannel) {
                        $devices[$name] = $channel;
                    }
                } catch (\Exception $e) {
                    $this->warn("⚠️  Could not load device '{$name}': " . $e->getMessage());
                }
            }
        } else {
            // Get specific device
            try {
                $channel = LightingSetup::getPixelChannel($deviceArg);
                if ($channel instanceof PixelChannel) {
                    $devices[$deviceArg] = $channel;
                } else {
                    $this->error("❌ Device '{$deviceArg}' is not a PixelChannel");
                }
            } catch (\Exception $e) {
                $this->error("❌ Device '{$deviceArg}' not found: " . $e->getMessage());
            }
        }

        return $devices;
    }

    /**
     * Suggest similar animations
     */
    protected function suggestAnimations(string $input): void
    {
        $this->newLine();
        $this->comment("Available animations (showing first 20):");

        $animations = array_slice(Animation::cases(), 0, 20);

        foreach ($animations as $animation) {
            $this->line("  • " . $animation->value . " (" . $animation->getName() . ")");
        }

        $totalCount = count(Animation::cases());
        if ($totalCount > 20) {
            $this->comment("  ... and " . ($totalCount - 20) . " more");
        }

        $this->newLine();
        $this->comment("💡 Tip: Animation names are case-insensitive. You can use dashes or underscores.");
        $this->comment("   Examples: meteor-shower, meteor_shower, METEOR_SHOWER");
    }
}

