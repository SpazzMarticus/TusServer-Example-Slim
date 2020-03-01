<?php

use DI\Container;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\StreamFactoryInterface;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\StreamFactory;
use SpazzMarticus\Tus\TusServer;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Psr\SimpleCache\CacheInterface as SimpleCacheInterface;
use Cache\Adapter\Filesystem\FilesystemCachePool;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;
use SpazzMarticus\Tus\Factories\FilenameFactoryInterface;
use SpazzMarticus\Tus\Factories\OriginalFilenameFactory;
use SpazzMarticus\Tus\Providers\LocationProviderInterface;
use SpazzMarticus\Tus\Providers\PathLocationProvider;

ini_set('display_errors', "1");
ini_set('display_startup_errors', "1");
ini_set('html_errors', "0");
ini_set("error_log", __DIR__ . '/php_error.log');
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/../tus2/vendor/autoload.php';

$container = new Container();

$container->set(ResponseFactoryInterface::class, new ResponseFactory());
$container->set(StreamFactoryInterface::class, new StreamFactory());
$container->set(EventDispatcherInterface::class, new EventDispatcher());
$container->set(SimpleCacheInterface::class, function () {
    return new FilesystemCachePool(new Filesystem(new Local(__DIR__)), 'storage');
});
$container->set(FilenameFactoryInterface::class, function () {
    return new OriginalFilenameFactory(__DIR__ . '/uploads/');
});
$container->set(LocationProviderInterface::class, new PathLocationProvider);


AppFactory::setContainer($container);
$app = AppFactory::create();

$app->get('/', function (Request $request, Response $response, array $args) {
    $response->getBody()->write(file_get_contents(__DIR__ . '/../tus2/example/uploader.html'));
    return $response;
});

$tus = new TusServer(
    $container->get(ResponseFactoryInterface::class),
    $container->get(StreamFactoryInterface::class),
    $container->get(SimpleCacheInterface::class),
    $container->get(EventDispatcherInterface::class),
    $container->get(FilenameFactoryInterface::class),
    $container->get(LocationProviderInterface::class),
);

$app->any('/upload/[{id}]', $tus);

$app->run();
