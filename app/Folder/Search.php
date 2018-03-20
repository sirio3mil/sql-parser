<?php
/**
 * Created by PhpStorm.
 * User: reynier.delarosa
 * Date: 20/03/2018
 * Time: 15:40
 */

namespace App\Folder;

use App\Graph\Connection;
use App\Graph\Node;

class Search
{

    protected $types;

    protected $graph;

    protected const CREATION_PATTERN_CONTENT = '~((\w+)\.){2,3}(\w+)~im';

    public function __construct()
    {
        $this->graph = new Connection();
        $this->types = ['synonym', 'view', 'function', 'procedure'];
    }

    public function Init(): void
    {
        $this->graph->CreateInitialNodes($this->types);
    }

    public function LookUp(): void
    {
        foreach ($this->types as $type){
            $directory = new \RecursiveDirectoryIterator('split' . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR);
            $iterator = new \RecursiveIteratorIterator($directory);
            $objects = new \RegexIterator($iterator, '/^.+\.sql$/i', \RecursiveRegexIterator::GET_MATCH);
            foreach($objects as $object){
                $localNode = (new Node())->ParsePath($object[0]);
                $this->graph->SetLocalNode($localNode);
                $content = str_replace("[", "", str_replace("]", "", file_get_contents($object[0])));
                $matches = [];
                preg_match_all(self::CREATION_PATTERN_CONTENT, $content, $matches);
                if($matches && $matches[0]) {
                    foreach ($matches[0] as $match){
                        $remoteNode = (new Node())->ParseDescription($match);
                        if($remoteNode->schema != $_ENV['DEFAULT_SCHEMA']){
                            continue;
                        }
                        if(!$remoteNode->name){
                            continue;
                        }
                        $this->graph->SetRemoteNode($remoteNode);
                        $this->graph->CreateRelation();
                    }
                }
            }
        }
    }
}