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
$response = $kernel->handle($request);

$response->send();
$kernel->terminate($request, $response);