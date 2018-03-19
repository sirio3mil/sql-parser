<?php
/**
 * Created by PhpStorm.
 * User: reynier.delarosa
 * Date: 16/03/2018
 * Time: 14:06
 */

namespace App\Utilities;


class ObjectName
{
    public static function RemoveBrackets(string $name): string
    {
        return str_replace("[", "", str_replace("]", "", $name));
    }

    public static function GetCleanName(string $object_name): \stdClass
    {
        $obj = new \stdClass();
        if(strpos($object_name, '\\') !== false){
            $object_name = array_pop(explode('\\', $object_name));
        }
        $object_name = self::RemoveBrackets($object_name);
        $parts = explode(".", $object_name);
        $obj->name = strtolower(array_pop($parts));
        $obj->schema = ($parts)?strtolower(implode(".", $parts)):"dbo";
        return $obj;
    }
}