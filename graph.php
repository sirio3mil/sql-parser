<?php
/**
 * Created by PhpStorm.
 * User: reynier.delarosa
 * Date: 19/03/2018
 * Time: 12:43
 */
set_time_limit(0);
require_once 'vendor/autoload.php';

use Dotenv\Dotenv;
use App\Folder\Search;
use App\Utilities\Clock;

$environment = new Dotenv(__DIR__);
$environment->overload();

$search = new Search();

const FIRST_TIME = false;

if(FIRST_TIME) {
    $search->Init();
}

$search->LookUp();

echo Clock::GetFormattedDuration(Clock::CalculateScriptDuration());