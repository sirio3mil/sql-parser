<?php
/**
 * Created by PhpStorm.
 * User: reynier.delarosa
 * Date: 16/03/2018
 * Time: 8:58
 */
require_once 'vendor/autoload.php';

use App\Utilities\FileName;
use App\Utilities\DumpFile;
use App\Utilities\Clock;
use App\Utilities\Cache;

try {

    Cache::InvalidateFileOpCache(__FILE__);

    $matches = (new DumpFile('tmp/rdsdb5.publish.sql'))->GetMatches();

    foreach ($matches[5] as $key => $object_name) {
        $type = strtolower($matches[3][$key]);
        (new FileName($type, $object_name))->SaveContent($matches[0][$key]);
    }

}
catch (Exception $e){
    echo '<h1>', $e->getMessage(), '</h1>';
}

echo Clock::GetFormattedDuration(Clock::CalculateScriptDuration());