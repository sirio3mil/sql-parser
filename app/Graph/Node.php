<?php
/**
 * Created by PhpStorm.
 * User: reynier.delarosa
 * Date: 20/03/2018
 * Time: 15:02
 */

namespace App\Graph;


class Node
{
    public $server;

    public $schema;

    public $name;

    public $database;

    public $type;

    public function ParsePath(string $path): Node
    {
        list($this->server, $this->type, $this->schema, $this->name) = explode(DIRECTORY_SEPARATOR, $path);
        $info = pathinfo($path);
        $this->name = $info['filename'];
        $this->type = ucwords($this->type);
        $this->server = $_ENV['CURRENT_SERVER'];
        $this->database = $_ENV['CURRENT_DATABASE'];
        return $this;
    }

    public function ParseDescription(string $match): Node
    {
        $this->type = $_ENV['DEFAULT_OBJECT_TYPE'];
        $occurrences = substr_count($match, '.');
        switch ($occurrences){
            case 3:
                list($this->server, $this->database, $this->schema, $this->name) = explode(".", $match);
                break;
            case 2:
                list($this->database, $this->schema, $this->name) = explode(".", $match);
                $this->server = $_ENV['CURRENT_SERVER'];
                break;
            default:
                $this->server = $_ENV['CURRENT_SERVER'];
                $this->database = $_ENV['CURRENT_DATABASE'];
                if(!$this->schema){
                    $this->schema = $_ENV['DEFAULT_SCHEMA'];
                }
        }
        return $this;
    }
}