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
$environment->load();

$client = ClientBuilder::create()
    ->addConnection("bolt", "bolt://{$_ENV['NEO4J_USER']}:{$_ENV['NEO4J_PASSWORD']}@{$_ENV['NEO4J_SERVER']}:{$_ENV['NEO4J_PORT']}")
    ->build();

$types = ['synonym', 'trigger', 'view', 'function', 'procedure'];

const CREATION_PATTERN_CONTENT = '~((\w+)\.){2,3}(\w+)~im';

foreach ($types as $type){
    $directory = new RecursiveDirectoryIterator('split' . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR);
    $iterator = new RecursiveIteratorIterator($directory);
    $objects = new RegexIterator($iterator, '/^.+\.sql$/i', RecursiveRegexIterator::GET_MATCH);

    foreach($objects as $object){
        list($folder, $type, $schema, $filename) = explode(DIRECTORY_SEPARATOR, $object[0]);
        $info = pathinfo($object[0]);
        $filename = $info['filename'];
        $content = str_replace("[", "", str_replace("]", "", file_get_contents($object[0])));
        echo $type . '<br />';
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
                        break;
                    default:
                        continue;
                }
                if ($reference){

                }
            }

        }
    }
}