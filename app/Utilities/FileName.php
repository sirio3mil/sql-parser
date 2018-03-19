<?php
/**
 * Created by PhpStorm.
 * User: reynier.delarosa
 * Date: 16/03/2018
 * Time: 14:15
 */

namespace App\Utilities;


class FileName
{

    protected $filename;

    protected const ROOT_FILE_FOLDER_NAME = "split";

    public function __construct(string $type, string $object_name)
    {
        $this->SetFileName($type, $object_name);
    }

    protected function SetFileName(string $type, string $object_name): void
    {
        $extension = 'sql';
        switch ($type) {
            case 'assembly':
                $this->filename = self::ROOT_FILE_FOLDER_NAME . DIRECTORY_SEPARATOR  . $type . DIRECTORY_SEPARATOR . ObjectName::RemoveBrackets($object_name) . '.' . $extension;
                break;
            default:
                $obj = ObjectName::GetCleanName($object_name);
                $this->filename = self::ROOT_FILE_FOLDER_NAME . DIRECTORY_SEPARATOR  . $type . DIRECTORY_SEPARATOR . $obj->schema . DIRECTORY_SEPARATOR . $obj->name . '.' . $extension;
        }
    }

    protected function ValidateFolder(): void
    {
        if (file_exists($this->filename)) {
            if(!unlink($this->filename)){
                throw new \Exception('Error deleting file: ' + $this->filename);
            }
        } else {
            $path = dirname($this->filename);
            if (!is_dir($path)) {
                if (!mkdir($path, 0777, true)) {
                    throw new \Exception('Folder can not be created: ' + $path);
                }
            }
        }
    }

    public function SaveContent(string $content): void
    {
        $this->ValidateFolder();
        $content = str_replace("¬","", str_replace("¬¬","\n", $content));
        if(!file_put_contents($this->filename, $content)){
            throw new \Exception('Error writing file: ' + $this->filename);
        }
    }
}