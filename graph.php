<?php
/**
 * Created by PhpStorm.
 * User: reynier.delarosa
 * Date: 19/03/2018
 * Time: 12:43
 */
require_once 'vendor/autoload.php';
require_once 'autoload.php';

use GraphAware\Neo4j\Client\ClientBuilder;
use Dotenv\Dotenv;

$environment = new Dotenv(__DIR__);
$environment->load();

$client = ClientBuilder::create()
    ->addConnection("bolt", "bolt://{$_ENV['NEO4J_USER']}:{$_ENV['NEO4J_PASSWORD']}@{$_ENV['NEO4J_SERVER']}:{$_ENV['NEO4J_PORT']}")
    ->build();