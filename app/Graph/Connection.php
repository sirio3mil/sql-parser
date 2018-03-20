<?php
/**
 * Created by PhpStorm.
 * User: reynier.delarosa
 * Date: 20/03/2018
 * Time: 14:45
 */

namespace App\Graph;

use GraphAware\Neo4j\Client\ClientBuilder;

class Connection
{

    protected $client;

    protected $localNode;

    protected $remoteNode;

    public function __construct()
    {
        $this->client = ClientBuilder::create()
            ->addConnection("bolt", "bolt://{$_ENV['NEO4J_USER']}:{$_ENV['NEO4J_PASSWORD']}@{$_ENV['NEO4J_SERVER']}")
            ->build();
    }

    public function CreateInitialNodes(array $types): void
    {
        $stack = $this->client->stack();
        $query = "MERGE (n:Server { name: {serverName} }) ON CREATE SET n.created = timestamp()";
        $stack->push($query, ['serverName' => $_ENV['CURRENT_SERVER']]);
        $query = "MATCH (server:Server { name: {serverName} }), (database:Database { name: {databaseName} }) MERGE (database)-[r:LOCATED_IN]->(server)";
        $stack->push($query, ['serverName' => $_ENV['CURRENT_SERVER'], 'databaseName' => $_ENV['CURRENT_DATABASE']]);
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
        $this->client->runStack($stack);
    }

    public function SetLocalNode(Node $node): Connection
    {
        $this->localNode = $node;
        $this->CreateNode($node);
        return $this;
    }

    public function SetRemoteNode(Node $node): Connection
    {
        $this->remoteNode = $node;
        $this->CreateNode($node);
        return $this;
    }

    protected function CreateNode(Node $node): void
    {
        $stack = $this->client->stack();
        $query = "MERGE (n:Server { name: {serverName} }) ON CREATE SET n.created = timestamp()";
        $stack->push($query, ['serverName' => $node->server]);
        $query = "MERGE (n:Database { name: {databaseName} }) ON CREATE SET n.created = timestamp()";
        $stack->push($query, ['databaseName' => $node->database]);
        $query = "MATCH (server:Server { name: {serverName} }), (database:Database { name: {databaseName} }) MERGE (database)-[r:LOCATED_IN]->(server)";
        $stack->push($query, ['serverName' => $node->server, 'databaseName' => $node->database]);
        $query = "MERGE (n:Schema { name: {schemaName} }) ON CREATE SET n.created = timestamp()";
        $stack->push($query, ['schemaName' => $node->schema]);
        $query = "MATCH (database:Database { name: {databaseName} }),(schema:Schema { name: {schemaName} }) MERGE (database)-[r:HAVE]->(schema)";
        $stack->push($query, ['databaseName' => $node->database, 'schemaName' => $node->schema]);
        $query = "MERGE (n:{$node->type} { name: {typeName} }) ON CREATE SET n.created = timestamp()";
        $stack->push($query, ['typeName' => $node->name]);
        $query = "MATCH (schema:Schema { name: {schemaName} }), (type:{$node->type} { name: {typeName} }) MERGE (schema)-[r:CONTAINS]->(type)";
        $stack->push($query, ['schemaName' => $node->schema, 'typeName' => $node->name]);
        $query = "MATCH (database:Database { name: {databaseName} }), (type:{$node->type} { name: {typeName} }) MERGE (type)-[r:IN]->(database)";
        $stack->push($query, ['databaseName' => $node->database, 'typeName' => $node->name]);
        $this->client->runStack($stack);
    }

    public function CreateRelation(): void
    {
        $query = "MATCH (local:{$this->localNode->type} { name: {typeLocalName} }), (remote:{$this->remoteNode->type} { name: {typeRemoteName} }) MERGE (local)-[r:USE]->(remote)";
        $this->client->run($query, ['typeLocalName' => $this->localNode->name, 'typeRemoteName' => $this->remoteNode->name]);
    }
}