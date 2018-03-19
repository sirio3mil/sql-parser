<?php
spl_autoload_register(function ($class) {
    include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . str_replace('\\', '/', $class) . '.php';
});