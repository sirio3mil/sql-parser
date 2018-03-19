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

$client = ClientBuilder::create()
    ->addConnection('bolt', 'bolt://neo4j:password@localhost:7687')
    ->build();
$query = "MATCH (n:Person)-[:FOLLOWS]->(friend) RETURN n.name, collect(friend) as friends";
$result = $client->run($query);

foreach ($result->getRecords() as $record) {
    echo sprintf('Person name is : %s and has %d number of friends', $record->value('name'), count($record->value('friends')));
}