<?php

namespace DeptOfScrapyardRobotics\LaravelRGB\Providers;

use ProjectSaturnStudios\LaravelDesignPatterns\Providers\BaseServiceProvider;

class LaravelRGBServiceProvider extends BaseServiceProvider
{
    protected string $short_name = 'customers';
    protected array $config = [
        'rgb-lighting' => __DIR__ . '/../../config/rgb-lighting.php',
    ];

    protected array $publishable_config = [
        ['key' => 'rgb-lighting', 'file_path' => __DIR__ . '/../../config/rgb-lighting.php', 'groups' => ['rgb-lighting']],
    ];

    protected array $commands = [];
}
