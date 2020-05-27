<?php

error_reporting(E_ALL);
ini_set('display_errors', true);

session_start();

require_once("vendor/autoload.php");

use \Slim\Slim;

$app = new Slim;
$app->config('debug', true);

require_once 'functions.php';
require_once 'site.php';
require_once 'admin.php';
require_once 'admin-users.php';
require_once 'admin-categories.php';
require_once 'admin-products.php';

$app->run();