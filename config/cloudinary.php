<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Cloudinary\Configuration\Configuration;

Configuration::instance([
    'cloud' => [
        'cloud_name' => 'foodifyy',
        'api_key'    => '466225459753539',
        'api_secret' => 'HL1uQz0EnlTDk16PK0A4Wdjateg',
    ],
    'url' => [
        'secure' => true
    ]
]);