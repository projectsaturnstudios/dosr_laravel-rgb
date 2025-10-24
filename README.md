# LaravelRGB

A Laravel wrapper for controlling WS281x/NeoPixel LED strips, fans, and devices on embedded Linux systems. Provides elegant Laravel integration with configuration, facades, async operations, and Artisan commands for the [neopixel-php](https://github.com/phpdafruit/neopixel-php) library.

## Features

- **Laravel Integration** - Service provider, facades, and configuration
- **Async Operations** - Non-blocking animations using Laravel Concurrency
- **Multi-Device Management** - Configure and control multiple LED devices
- **Device Shapes** - Pre-built device abstractions (strips, fans, custom shapes)
- **Bus Animations** - Multi-channel coordinated animations
- **Artisan Commands** - Command-line interface for playing animations
- **Type-Safe Configuration** - Laravel-style config files
- **All neopixel-php Features** - Full access to 35+ built-in animations

## Requirements

- **PHP 8.3+**
- **Laravel 11.x** (or 10.x)
- **[phpixel extension](https://github.com/projectsaturnstudios/phpdafruit_phpixel_extension)** - Low-level SPI interface
- **[neopixel-php](https://github.com/phpdafruit/neopixel-php)** - Core LED control library
- **Embedded Linux device** with SPI support (Raspberry Pi, Jetson Orin Nano, etc.)

## Installation

### 1. Install phpixel Extension

First, install the phpixel extension on your embedded device:

```bash
# Clone and build the extension
git clone https://github.com/projectsaturnstudios/phpdafruit_phpixel_extension.git
cd phpdafruit_phpixel_extension
phpize
./configure
make
sudo make install

# Enable the extension
echo "extension=phpixel.so" | sudo tee /etc/php/8.3/cli/conf.d/20-phpixel.ini

# Verify
php -m | grep phpixel
```

For detailed installation instructions, see the [phpixel repository](https://github.com/projectsaturnstudios/phpdafruit_phpixel_extension).

### 2. Install neopixel-php

```bash
composer require phpdafruit/neopixel-php
```

See the [neopixel-php documentation](https://github.com/phpdafruit/neopixel-php) for core library features.

### 3. Install LaravelRGB

```bash
composer require dept-of-scrapyard-robotics/laravel-rgb
```

### 4. Publish Configuration

```bash
php artisan vendor:publish --tag=rgb-lighting-config
```

This creates `config/rgb-lighting.php` where you can configure your LED devices.

## Configuration

Edit `config/rgb-lighting.php` to define your LED devices:

```php
return [
    // Concurrency driver for async operations
    'async_concurrency_driver' => 'fork', // 'fork', 'process', or 'sync'

    // Device definitions
    'devices' => [
        'strip' => [
            'shape' => \PhpdaFruit\NeoPixels\DeviceShapes\RGBStrip::class,
            'num_pixels' => 30,
            'spi_device' => 'SPI_0_0',  // Maps to /dev/spidev0.0
            'neopixel_type' => 'GRB',   // Color order
        ],

        'fan' => [
            'shape' => \PhpdaFruit\NeoPixels\DeviceShapes\DoubleDots::class,
            'spi_device' => 'SPI_1_0',  // Maps to /dev/spidev1.0
            'neopixel_type' => 'RGB',
        ],

        'status_led' => [
            'shape' => \PhpdaFruit\NeoPixels\DeviceShapes\SingleDiode::class,
            'spi_device' => 'SPI_0_1',
            'neopixel_type' => 'GRB',
        ],
    ],
];
```

**Note:** In most cases, you'll only need one or two devices. The above shows multiple devices for demonstration.

### Available Shapes

- `RGBStrip` - Linear LED strips (requires `num_pixels`)
- `SingleDiode` - Single LED (status indicators)
- `DoubleDots` - Two LEDs (PC fans, dual indicators)
- Custom shapes - Your own device shapes

### SPI Device Mapping

- `SPI_0_0` → `/dev/spidev0.0`
- `SPI_0_1` → `/dev/spidev0.1`
- `SPI_1_0` → `/dev/spidev1.0`
- `SPI_1_1` → `/dev/spidev1.1`
- And so on...

### Color Orders (neopixel_type)

- `GRB` - Most common (WS2812B)
- `RGB` - Alternative order
- `RGBW` - With white channel
- `GRBW` - GRB with white channel

## Usage

### Basic Usage with Facades

```php
use DeptOfScrapyardRobotics\LaravelRGB\Support\Facades\LightingSetup;

// Get a device channel
$strip = LightingSetup::getPixelChannel('strip');

// Control the strip
$strip->fill(0xFF0000)->show();  // Red
$strip->setBrightness(128)->show();  // 50% brightness
$strip->clear()->show();  // Turn off
```

### Using the Dispatcher (Fluent API)

The `RGBDispatcher` provides a fluent interface with async support:

```php
use DeptOfScrapyardRobotics\LaravelRGB\Support\Facades\LightingSetup;

// Get dispatcher for a device
$dispatcher = LightingSetup::channel('strip');

// Synchronous operations
$dispatcher->setColor(0x00FF00)  // Green
           ->wait(1000)          // Wait 1 second
           ->setColor(0x0000FF)  // Blue
           ->wait(1000)
           ->off();              // Turn off

// Async operations (non-blocking)
$dispatcher->withAsync()
           ->setColor(0xFF00FF)  // Magenta
           ->wait(2000)
           ->status('success')   // Green blink pattern
           ->wait(500)
           ->off()
           ->fire();  // Execute all queued operations
```

### Playing Animations

```php
use PhpdaFruit\NeoPixels\Enums\Animation;
use PhpdaFruit\NeoPixels\AnimationRegistry;

// Initialize animation system (do once)
AnimationRegistry::initialize();

// Get device and play animation
$strip = LightingSetup::getPixelChannel('strip');

// Play a 5-second rainbow animation
$strip->animate(Animation::RAINBOW, 5000);

// Play fire effect with custom options
$strip->animate(Animation::FIRE_FLICKER, 10000, [
    'intensity' => 0.9,
    'cooling' => 60
]);

// Meteor shower
$strip->animate(Animation::METEOR_SHOWER, 8000);
```

For a complete list of 35+ animations, see the [neopixel-php ANIMATIONS.md](https://github.com/phpdafruit/neopixel-php/blob/main/ANIMATIONS.md).

### Async Multi-Device Control

Control multiple devices concurrently using Laravel's Concurrency facade:

```php
use Illuminate\Support\Facades\Concurrency;
use DeptOfScrapyardRobotics\LaravelRGB\Support\Facades\LightingSetup;

$strip = LightingSetup::channel('strip');
$fan = LightingSetup::channel('fan');

// Both execute simultaneously
Concurrency::run([
    fn() => $strip->withAsync()
                  ->setColor(0xFF0000)
                  ->wait(1000)
                  ->setColor(0x00FF00)
                  ->fire(),
                  
    fn() => $fan->withAsync()
                ->spin(0x0000FF, 5, 200)
                ->wait(500)
                ->alternate(0xFFFF00, 10, 100)
                ->fire()
]);
```

### Artisan Commands

#### rgb:animate - Play Animations from CLI

Play any animation on any configured device:

```bash
# Play rainbow on strip for 5 seconds
php artisan rgb:animate rainbow strip --duration=5000

# Play comet on all devices forever (Ctrl+C to stop)
php artisan rgb:animate comet all

# Play fire flicker on strip (runs until interrupted)
php artisan rgb:animate fire-flicker strip

# Play meteor shower on fan for 10 seconds
php artisan rgb:animate meteor-shower fan --duration=10000
```

**Features:**
- Play on specific device or all devices
- Optional duration (runs forever if not specified)
- Graceful shutdown on Ctrl+C:
  1. All devices flash red
  2. Devices turn off one by one (400ms between each)
- Animation name is case-insensitive, accepts dashes or underscores

**Available Commands:**

```bash
# See all options
php artisan rgb:animate --help

# List animations (shows first 20)
php artisan rgb:animate invalid_name
```

## AnimatablePixelBus

`AnimatablePixelBus` extends `PixelBus` to support async operations and multi-channel animations. Perfect for devices with multiple coordinated LED channels (like a carnival High Striker game).

### What is AnimatablePixelBus?

It's an abstract class that:
- Manages multiple `PixelChannel` instances
- Supports async operation queuing (like `RGBDispatcher`)
- Provides `animate()` for bus-level animations
- Coordinates effects across multiple channels
- Uses Laravel Concurrency for parallel execution

### Creating Custom Device Shapes

Extend `AnimatablePixelBus` to create multi-channel devices:

```php
namespace App\LEDs;

use DeptOfScrapyardRobotics\LaravelRGB\AnimatablePixelBus;
use PhpdaFruit\NeoPixels\DeviceShapes\RGBStrip;
use PhpdaFruit\NeoPixels\PixelChannel;
use PhpdaFruit\NeoPixels\Enums\SPIDevice;
use PhpdaFruit\NeoPixels\Enums\NeoPixelType;

class CustomDevice extends AnimatablePixelBus
{
    public function __construct()
    {
        parent::__construct([
            'main_strip' => new RGBStrip(30, SPIDevice::SPI_0_0->value, NeoPixelType::GRB),
            'accent_strip' => new RGBStrip(15, SPIDevice::SPI_0_1->value, NeoPixelType::GRB),
            'status_led' => new PixelChannel(1, SPIDevice::SPI_1_0->value, NeoPixelType::RGB),
        ]);
    }

    /**
     * Custom animation method
     */
    public function rainbowWave(): static
    {
        // Access channels
        $main = $this->getChannel('main_strip');
        $accent = $this->getChannel('accent_strip');
        
        // Coordinate animation across both strips
        for ($i = 0; $i < 256; $i++) {
            $color = $this->wheel($i);
            $main->fill($color)->show();
            $accent->fill($this->wheel($i + 128))->show();
            usleep(20000);
        }
        
        return $this;
    }

    /**
     * Helper: Get color from color wheel
     */
    protected function wheel(int $pos): int
    {
        $pos = $pos % 256;
        if ($pos < 85) {
            return (($pos * 3) << 16) | ((255 - $pos * 3) << 8);
        } elseif ($pos < 170) {
            $pos -= 85;
            return ((255 - $pos * 3) << 16) | (($pos * 3));
        } else {
            $pos -= 170;
            return (($pos * 3) << 8) | (255 - $pos * 3);
        }
    }

    /**
     * Get individual channels
     */
    public function getMainStrip(): RGBStrip
    {
        return $this->getChannel('main_strip');
    }

    public function getAccentStrip(): RGBStrip
    {
        return $this->getChannel('accent_strip');
    }

    public function getStatusLed(): PixelChannel
    {
        return $this->getChannel('status_led');
    }
}
```

### Using Your Custom Device Shape

```php
use App\LEDs\CustomDevice;

$device = new CustomDevice();

// Use custom methods
$device->rainbowWave();

// Access individual channels
$device->getMainStrip()->fill(0xFF0000)->show();
$device->getStatusLed()->fill(0x00FF00)->show();

// Use async operations
$device->withAsync()
       ->rainbowWave()
       ->wait(1000)
       ->clearAll()
       ->fire();
```

### Example: HighStriker (Carnival Game)

The package includes a `HighStriker` device shape as an example:

```php
use DeptOfScrapyardRobotics\LaravelRGB\DeviceShapes\HighStriker;

// Create with rail and bell channels
$striker = new HighStriker(
    rail_dots: 15,    // Rail strip length
    bell_dots: 2,     // Bell indicator
    flip_rail: true   // Flip for upside-down mounting
);

// Play physics simulation
$striker->strike('strong');  // Random velocity strike
$striker->strike('weak');    // Likely to fail
$striker->strike('random');  // Random strength

// Manual control
$striker->getRail()->fill(0xFF0000)->show();
$striker->getBell()->fill(0xFFD700)->show();

// Async operation
$striker->withAsync()
        ->strike('medium')
        ->wait(1000)
        ->celebrateSuccess()
        ->fire();
```

### Bus Animation Architecture

For multi-channel animations, create `BusAnimationVisualization` implementations:

```php
use DeptOfScrapyardRobotics\LaravelRGB\Animations\BusVisualization;
use DeptOfScrapyardRobotics\LaravelRGB\AnimatablePixelBus;
use DeptOfScrapyardRobotics\LaravelRGB\Enums\BusAnimation;

class MyBusAnimation extends BusVisualization
{
    public function getAnimationType(): BusAnimation
    {
        return BusAnimation::CUSTOM;
    }

    public function run(AnimatablePixelBus $bus, int $duration_ms, array $options = []): void
    {
        $channels = $bus->channels();
        
        // Coordinate animation across all channels
        foreach ($channels as $name => $channel) {
            // Your animation logic
        }
    }

    public function getRequiredChannels(): array
    {
        return ['channel1', 'channel2']; // Or return [] for any bus
    }
}
```

## Available Methods

### RGBDispatcher

```php
// Async control
->withAsync()                           // Enable async mode
->withoutAsync()                        // Disable async mode
->fire()                                // Execute queued operations

// Operations
->setColor(int $color, ?int $brightness)  // Set color
->on(int $color)                         // Turn on with color
->off()                                  // Turn off
->wait(int $milliseconds)                // Wait/delay
->clear()                                // Clear LEDs
->status(string $status, int $blinks)    // Status indicator

// Proxy to underlying channel methods
// Any method on PixelChannel/DeviceShape can be called
->fill(int $color)
->setBrightness(int $brightness)
->animate(Animation $animation, int $duration_ms)
// ... and many more
```

### AnimatablePixelBus

```php
// Async control
->withAsync()                    // Enable async mode
->withoutAsync()                 // Disable async mode  
->fire()                         // Execute queued operations
->wait(int $milliseconds)        // Wait/delay

// Channel access
->channels()                     // Get all channels
->getChannel(string $name)       // Get specific channel
->clearAll()                     // Clear all channels
->showAll()                      // Update all channels

// Animations
->animate(BusAnimation $animation, int $duration_ms, array $options)

// Magic method proxy
// Any method calls are broadcast to all channels
->fill(int $color)              // Fills all channels
->setBrightness(int $brightness) // Sets brightness on all
```

## Dependencies

### Required Packages

1. **[phpixel extension](https://github.com/projectsaturnstudios/phpdafruit_phpixel_extension)**
   - C extension providing low-level SPI interface
   - Must be installed system-wide
   - See installation instructions in repository

2. **[neopixel-php](https://github.com/phpdafruit/neopixel-php)**
   - Core LED control library  
   - Object-oriented API
   - 35+ built-in animations
   - Device shapes (RGBStrip, DoubleDots, SingleDiode)

3. **Laravel 11.x** (or 10.x)
   - Service providers
   - Facades
   - Concurrency support
   - Artisan commands

## Troubleshooting

### Extension Not Found

```
PHP Fatal error: Uncaught Error: Class 'NeoPixel' not found
```

**Solution:** Install the [phpixel extension](https://github.com/projectsaturnstudios/phpdafruit_phpixel_extension).

### Device Not Found in Config

```
Device 'strip' not found
```

**Solution:** Publish and configure `config/rgb-lighting.php`:

```bash
php artisan vendor:publish --tag=rgb-lighting-config
```

### SPI Permission Denied

```
Failed to open SPI device: Permission denied
```

**Solution:** Add your user to the `spi` group:

```bash
sudo usermod -a -G spi $USER
# Log out and back in for group changes to take effect
```

### Wrong Colors

If colors are wrong (e.g., red appears as green), adjust `neopixel_type` in config:

```php
'neopixel_type' => 'GRB',  // Try RGB, RGBW, or GRBW
```

## License

MIT License. See LICENSE file for details.

## Links

- **neopixel-php**: [https://github.com/phpdafruit/neopixel-php](https://github.com/phpdafruit/neopixel-php)
- **phpixel extension**: [https://github.com/projectsaturnstudios/phpdafruit_phpixel_extension](https://github.com/projectsaturnstudios/phpdafruit_phpixel_extension)
- **Laravel Concurrency**: [https://laravel.com/docs/concurrency](https://laravel.com/docs/concurrency)

## Contributing

Contributions are welcome! Please:
- Follow PSR-12 coding standards
- Add tests for new features
- Update documentation
- Ensure all tests pass

## Support

- **LaravelRGB issues**: Open an issue in this repository
- **neopixel-php issues**: See [neopixel-php repository](https://github.com/phpdafruit/neopixel-php)
- **phpixel extension issues**: See [phpixel repository](https://github.com/projectsaturnstudios/phpdafruit_phpixel_extension)


