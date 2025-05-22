<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use Freema\ReactAdminApiBundle\Dev\DevKernel;
use Symfony\Component\HttpFoundation\Request;

$kernel = new DevKernel('dev', true);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);

$response->send();
$kernel->terminate($request, $response);