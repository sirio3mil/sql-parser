<style>
    table, th, td {
        border: 1px solid black;
    }
</style>
<?php
/**
 * Created by PhpStorm.
 * User: reynier.delarosa
 * Date: 19/03/2018
 * Time: 12:43
 */
set_time_limit(0);
require_once 'vendor/autoload.php';

use GraphAware\Neo4j\Client\ClientBuilder;
use Dotenv\Dotenv;

$environment = new Dotenv(__DIR__);
$environment->overload();

$currentServer = $_ENV['CURRENT_SERVER'];

$currentDatabase = $_ENV['CURRENT_DATABASE'];

$defaultSchema = $_ENV['DEFAULT_SCHEMA'];

$types = ['synonym', 'view', 'function', 'procedure'];

const CREATION_PATTERN_CONTENT = '~((\w+)\.){2,3}(\w+)~im';
const PRINT_OUTPUT_TABLE = false;
const FIRST_TIME = false;

$servers = $databases = $schemas = [];

$database_schemas = [];

$processed = [];

$database_schemas[$currentDatabase] = [];

if(!PRINT_OUTPUT_TABLE) {
    $client = ClientBuilder::create()
        ->addConnection("bolt", "bolt://{$_ENV['NEO4J_USER']}:{$_ENV['NEO4J_PASSWORD']}@{$_ENV['NEO4J_SERVER']}")
        ->build();
}
if(!PRINT_OUTPUT_TABLE && FIRST_TIME) {
    $stack = $client->stack();
    $query = "MERGE (n:Server { name: {serverName} }) ON CREATE SET n.created = timestamp()";
    $stack->push($query, ['serverName' => $currentServer]);
    $query = "MERGE (n:Database { name: {databaseName} }) ON CREATE SET n.created = timestamp()";
    $stack->push($query, ['databaseName' => $currentDatabase]);
    $query = "MATCH (server:Server { name: {serverName} }), (database:Database { name: {databaseName} }) MERGE (database)-[r:LOCATED_IN]->(server)";
    $stack->push($query, ['serverName' => $currentServer, 'databaseName' => $currentDatabase]);
    $query = "CREATE INDEX ON :Server(name)";
    $stack->push($query);
    $query = "CREATE INDEX ON :Database(name)";
    $stack->push($query);
    $query = "CREATE INDEX ON :Schema(name)";
    $stack->push($query);
    $query = "CREATE INDEX ON :Object(name)";
    $stack->push($query);
    foreach ($types as $type) {
        $type = ucwords($type);
        $query = "CREATE INDEX ON :{$type}(name)";
        $stack->push($query);
    }
    $client->runStack($stack);
}

echo '<table>';
foreach ($types as $type){
    $directory = new RecursiveDirectoryIterator('split' . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR);
    $iterator = new RecursiveIteratorIterator($directory);
    $objects = new RegexIterator($iterator, '/^.+\.sql$/i', RecursiveRegexIterator::GET_MATCH);
    foreach($objects as $object){
        list($folder, $type, $schema, $filename) = explode(DIRECTORY_SEPARATOR, $object[0]);
        $info = pathinfo($object[0]);
        $filename = $info['filename'];
        $type = ucwords($type);
        if(!PRINT_OUTPUT_TABLE) {
            $stack = $client->stack();
            $query = "MERGE (n:Schema { name: {schemaName} }) ON CREATE SET n.created = timestamp()";
            $stack->push($query, ['schemaName' => $schema]);
            $query = "MATCH (database:Database { name: {databaseName} }),(schema:Schema { name: {schemaName} }) MERGE (database)-[r:HAVE]->(schema)";
            $stack->push($query, ['databaseName' => $currentDatabase, 'schemaName' => $schema]);
            $query = "MERGE (n:{$type} { name: {typeName} }) ON CREATE SET n.created = timestamp()";
            $stack->push($query, ['typeName' => $filename]);
            $query = "MATCH (schema:Schema { name: {schemaName} }), (type:{$type} { name: {typeName} }) MERGE (schema)-[r:CONTAINS]->(type)";
            $stack->push($query, ['schemaName' => $schema, 'typeName' => $filename]);
            $query = "MATCH (database:Database { name: {databaseName} }), (type:{$type} { name: {typeName} }) MERGE (type)-[r:IN]->(database)";
            $stack->push($query, ['databaseName' => $currentDatabase, 'typeName' => $filename]);
            $client->runStack($stack);
        }
        else{
            echo "<tr><th>{$object[0]}</th><th>{$folder}</th><th>{$type}</th><th>{$schema}</th><th>{$filename}</th></tr>";
        }
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
                }
                if($schema != $defaultSchema){
                    continue;
                }
                if ($reference){
                    if(PRINT_OUTPUT_TABLE){
                        echo "<tr><td>{$match}</td><td>{$server}</td><td>{$database}</td><td>{$schema}</td><td>{$reference}</td></tr>";
                    }
                    else {
                        $stack = $client->stack();
                        $query = "MERGE (n:Server { name: {serverName} }) ON CREATE SET n.created = timestamp()";
                        $stack->push($query, ['serverName' => $server]);
                        $query = "MERGE (n:Database { name: {databaseName} }) ON CREATE SET n.created = timestamp()";
                        $stack->push($query, ['databaseName' => $database]);
                        $query = "MATCH (server:Server { name: {serverName} }), (database:Database { name: {databaseName} }) MERGE (database)-[r:LOCATED_IN]->(server)";
                        $stack->push($query, ['serverName' => $server, 'databaseName' => $database]);
                        $query = "MERGE (n:Schema { name: {schemaName} }) ON CREATE SET n.created = timestamp()";
                        $stack->push($query, ['schemaName' => $schema]);
                        $query = "MATCH (database:Database { name: {databaseName} }),(schema:Schema { name: {schemaName} }) MERGE (database)-[r:HAVE]->(schema)";
                        $stack->push($query, ['databaseName' => $database, 'schemaName' => $schema]);
                        $query = "MERGE (n:Object { name: {typeName} }) ON CREATE SET n.created = timestamp()";
                        $stack->push($query, ['typeName' => $reference]);
                        $query = "MATCH (schema:Schema { name: {schemaName} }), (type:Object { name: {typeName} }) MERGE (schema)-[r:CONTAINS]->(type)";
                        $stack->push($query, ['schemaName' => $schema, 'typeName' => $reference]);
                        $query = "MATCH (database:Database { name: {databaseName} }), (type:Object { name: {typeName} }) MERGE (type)-[r:IN]->(database)";
                        $stack->push($query, ['databaseName' => $database, 'typeName' => $reference]);
                        $query = "MATCH (local:{$type} { name: {typeLocalName} }), (remote:Object { name: {typeRemoteName} }) MERGE (local)-[r:USE]->(remote)";
                        $stack->push($query, ['typeLocalName' => $filename, 'typeRemoteName' => $reference]);
                        $client->runStack($stack);
                    }
                }
            }
        }
    }
}
echo '</table>';