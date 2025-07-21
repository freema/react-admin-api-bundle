<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use Freema\ReactAdminApiBundle\Dev\DevKernel;
use Freema\ReactAdminApiBundle\Dev\DataFixtures;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\ErrorHandler\Debug;

Debug::enable();

$kernel = new DevKernel('dev', true);
$kernel->boot();

// Initialize database with test data for SQLite in memory
$container = $kernel->getContainer();
$dataFixtures = new DataFixtures($container->get('doctrine.orm.entity_manager'));
$dataFixtures->load();

$request = Request::createFromGlobals();

// Add CORS headers for development
if ($request->getMethod() === 'OPTIONS') {
    $response = new \Symfony\Component\HttpFoundation\Response();
    $response->headers->set('Access-Control-Allow-Origin', 'http://localhost:5173');
    $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Range');
    $response->headers->set('Access-Control-Max-Age', '86400');

    $response->send();
    $kernel->terminate($request, $response);
}

$response = $kernel->handle($request);
$response->headers->set('Access-Control-Allow-Origin', 'http://localhost:5173');
$response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
$response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Range');
$response->send();
$kernel->terminate($request, $response);