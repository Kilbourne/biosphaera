<?php

/*
|--------------------------------------------------------------------------
| Autoload our application
|--------------------------------------------------------------------------
|
| Here we include our autoloader file that will deal with including all our
| required class files.
|
*/

require_once 'autoload.php';

/*
|--------------------------------------------------------------------------
| Load initial config
|--------------------------------------------------------------------------
|
| Here we include and save our initial config files and save them to the 
| registry
|
*/

require_once 'configuration.php';

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first real thing we will do is create a new Responsive Menu instance.
|
*/

$app = new ResponsiveMenuPro;