<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Cloudinary\Configuration\Configuration;

Configuration::instance([
    'cloud' => [
        'cloud_name' => 'your_cloud_name',
        'api_key'    => 'your_api',
        'api_secret' => 'your_api_secret',
    ],
    'url' => [
        'secure' => true
    ]
]);