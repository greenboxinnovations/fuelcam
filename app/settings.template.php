<?php
declare(strict_types=1);

use App\Application\Settings\Settings;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {

    // Timezone
    date_default_timezone_set('Asia/Calcutta');

    // Global Settings Object
    $containerBuilder->addDefinitions([
        'settings' => [
            'displayErrorDetails' => true, // Should be set to false in production
            'logger' => [
                'name' => 'slim-app',
                'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
                'level' => Logger::DEBUG,
            ],
            'db' => [
                'driver' => 'mysql',
                'host' => 'localhost',
                'username' => '',
                'database' => '',
                'password' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'flags' => [
                    // Turn off persistent connections
                    PDO::ATTR_PERSISTENT => false,
                    // Enable exceptions
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    // Emulate prepared statements
                    PDO::ATTR_EMULATE_PREPARES => true,
                    // Set default fetch mode to array
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    // Set character set
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci'
                ],
            ],
            // time added in seconds after which otp is invalid
            "otp_timeout" => 30,    
            "jwt_key" => 'example_key',
            "ref_token_expiry" => "+1 year",
            "auth_token_expiry" => "+3 hour",
            "token_key" => "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9",
            "pump_id" => -999999,
            "set_rates_per_day" => 2, // 2 rates can be set per day
            "android_double_entry_time_seconds" => 20, // android prevent double tap , 20 seconds
            "printer_ip" => "192.168.1.101",
            "print" => true
    ]);
};
