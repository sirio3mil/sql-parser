<?php
/**
 * Created by PhpStorm.
 * User: reynier.delarosa
 * Date: 19/03/2018
 * Time: 12:43
 */
require_once 'vendor/autoload.php';

use GraphAware\Neo4j\Client\ClientBuilder;
use Dotenv\Dotenv;

$environment = new Dotenv(__DIR__);
$environment->overload();

$client = ClientBuilder::create()
    ->addConnection("bolt", "bolt://{$_ENV['NEO4J_USER']}:{$_ENV['NEO4J_PASSWORD']}@{$_ENV['NEO4J_SERVER']}")
    ->build();

/*
echo "http://{$_ENV['NEO4J_USER']}:{$_ENV['NEO4J_PASSWORD']}@{$_ENV['NEO4J_SERVER']}";
echo '<br/>';
echo "bolt://{$_ENV['NEO4J_USER']}:{$_ENV['NEO4J_PASSWORD']}@{$_ENV['NEO4J_SERVER']}";
echo '<br/>';
print_r($_ENV);
exit();
*/

$currentServer = $_ENV['CURRENT_SERVER'];

$currentDatabase = $_ENV['CURRENT_DATABASE'];

$defaultSchema = $_ENV['DEFAULT_SCHEMA'];

$types = ['synonym', 'trigger', 'view', 'function', 'procedure'];

$query = "MERGE (n:Server { name: {serverName} }) ON CREATE SET n.created = timestamp()";
$client->run($query, ['serverName' => $currentServer]);

$query = "MERGE (n:Database { name: {databaseName} }) ON CREATE SET n.created = timestamp()";
$client->run($query, ['databaseName' => $currentDatabase]);

$query = "MATCH (server:Server { name: {serverName} }), (database:Database { name: {databaseName} }) MERGE (database)-[r:LOCATED_IN]->(server)";
$client->run($query, ['serverName' => $currentServer, 'databaseName' => $currentDatabase]);

const CREATION_PATTERN_CONTENT = '~((\w+)\.){2,3}(\w+)~im';

$servers = $databases = $schemas = [];

$database_schemas = [];

$database_schemas[$currentDatabase] = [];

foreach ($types as $type){
    $directory = new RecursiveDirectoryIterator('split' . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR);
    $iterator = new RecursiveIteratorIterator($directory);
    $objects = new RegexIterator($iterator, '/^.+\.sql$/i', RecursiveRegexIterator::GET_MATCH);

    foreach($objects as $object){
        $stack = $client->stack();
        list($folder, $type, $schema, $filename) = explode(DIRECTORY_SEPARATOR, $object[0]);
        $type = ucwords($type);
        if(!in_array($schema, $schemas)) {
            $schemas[] = $schema;
            $query = "MERGE (n:Schema { name: {schemaName} }) ON CREATE SET n.created = timestamp()";
            $stack->push($query, ['schemaName' => $schema]);
        }
        if(!in_array($schema, $database_schemas[$currentDatabase])) {
            $database_schemas[$currentDatabase][] = $schema;
            $query = "MATCH (database:Database { name: {databaseName} }),(schema:Schema { name: {schemaName} }) MERGE (database)-[r:HAVE]->(schema)";
            $stack->push($query, ['databaseName' => $currentDatabase, 'schemaName' => $schema]);
        }
        $info = pathinfo($object[0]);
        $filename = $info['filename'];
        $query = "MERGE (n:{$type} { name: {typeName} }) ON CREATE SET n.created = timestamp()";
        $stack->push($query, ['typeName' => $filename]);
        $query = "MATCH (schema:Schema { name: {schemaName} }), (type:{$type} { name: {typeName} }) MERGE (schema)-[r:CONTAINS]->(type)";
        $stack->push($query, ['schemaName' => $schema, 'typeName' => $filename]);
        $query = "MATCH (database:Database { name: {databaseName} }), (type:{$type} { name: {typeName} }) MERGE (type)-[r:IN]->(database)";
        $stack->push($query, ['databaseName' => $currentDatabase, 'typeName' => $filename]);
        $content = str_replace("[", "", str_replace("]", "", file_get_contents($object[0])));
        $matches = [];
        preg_match_all(CREATION_PATTERN_CONTENT, $content, $matches);
        if($matches && $matches[0]) {
            foreach ($matches[0] as $match){
                $server = $database = $schema = $reference = null;
                $occurrences = substr_count($match, '.');
                switch ($occurrences){
                    case 3:
                        list($server, $database, $schema, $reference) = explode(".", $match);
                        if(!in_array($server, $servers)) {
                            $servers[] = $server;
                            $query = "MERGE (n:Server { name: {serverName} }) ON CREATE SET n.created = timestamp()";
                            $stack->push($query, ['serverName' => $server]);
                        }
                        break;
                    case 2:
                        list($database, $schema, $reference) = explode(".", $match);
                        $server = $currentServer;
                        break;
                    default:
                        $server = $currentServer;
                        $database = $currentDatabase;
                        if(!$schema){
                            $schema = $defaultSchema;
                        }
                        continue;
                }
                if ($reference){
                    if(!in_array($database, $databases)) {
                        $databases[] = $database;
                        $query = "MERGE (n:Database { name: {databaseName} }) ON CREATE SET n.created = timestamp()";
                        $stack->push($query, ['databaseName' => $database]);
                        $query = "MATCH (server:Server { name: {serverName} }), (database:Database { name: {databaseName} }) MERGE (database)-[r:LOCATED_IN]->(server)";
                        $stack->push($query, ['serverName' => $server, 'databaseName' => $database]);
                    }
                    if(!in_array($schema, $schemas)) {
                        $schemas[] = $schema;
                        $query = "MERGE (n:Schema { name: {schemaName} }) ON CREATE SET n.created = timestamp()";
                        $stack->push($query, ['schemaName' => $schema]);
                    }
                    if(!in_array($schema, $database_schemas[$database])) {
                        $database_schemas[$currentDatabase][] = $schema;
                        $query = "MATCH (database:Database { name: {databaseName} }),(schema:Schema { name: {schemaName} }) MERGE (database)-[r:HAVE]->(schema)";
                        $stack->push($query, ['databaseName' => $database, 'schemaName' => $schema]);
                    }
                }
            }

        }
        $client->runStack($stack);
    }
}