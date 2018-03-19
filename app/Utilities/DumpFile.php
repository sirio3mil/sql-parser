<?php
/**
 * Created by PhpStorm.
 * User: reynier.delarosa
 * Date: 16/03/2018
 * Time: 14:25
 */

namespace App\Utilities;


class DumpFile
{
    protected const CREATION_PATTERN_CONTENT = '~(create)([\s]+)(assembly|aggregate|table|synonym|trigger|view|function|procedure)([\s]+)([^\s\#\(¬]+)([\s|\(|¬])(.*?)¬¬GO¬~im';

    protected $matches_description = [];

    protected $clean_filename = '';

    protected $file_content = '';

    public function __construct(string $filename)
    {

        if(!file_exists($filename)){
            throw new \Exception('File does not exists: ' + $filename);
        }

        $this->clean_filename = dirname($filename) . DIRECTORY_SEPARATOR . 'clean.' . basename($filename);

        if(file_exists($this->clean_filename)){
            unlink($this->clean_filename);
        }

        $this->file_content = preg_replace("/[\n\r]/","¬", file_get_contents($filename));

        file_put_contents($this->clean_filename, $this->file_content);
    }

    public function GetMatches(): array
    {
        if(!$this->matches_description){
            preg_match_all(self::CREATION_PATTERN_CONTENT, $this->file_content, $this->matches_description);
        }
        return $this->matches_description;
    }
}