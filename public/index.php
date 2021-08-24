<?php
// use Psr\Http\Message\ResponseInterface as Response;
// use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\ContainerBuilder;

use Selective\BasePath\BasePathMiddleware;

// use PDO;

// $x = new PDO("1", "1", "1", "1");

require __DIR__ . '/../vendor/autoload.php';

// Instantiate PHP-DI ContainerBuilder
$containerBuilder = new ContainerBuilder();

if (false) { // Should be set to true in production
	$containerBuilder->enableCompilation(__DIR__ . '/../var/cache');
}

// Set up settings
$settings = require __DIR__ . '/../app/settings.php';
$settings($containerBuilder);

// Set up dependencies
$dependencies = require __DIR__ . '/../app/dependencies.php';
$dependencies($containerBuilder);

// Build PHP-DI Container instance
$container = $containerBuilder->build();

// Set container on app
AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addBodyParsingMiddleware();

$middleware = require __DIR__.'/../app/middleware.php';
$middleware($app);

// $app->setBasePath("/fuelqr/public/index.php");
$app->setBasePath("/fuelcam");

// $app->setBasePath("/slim_test");

$routes = require __DIR__.'/../app/routes.php';
$routes($app);


$app->run();