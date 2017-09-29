<?php

/*
Plugin Name: Responsive Menu Pro
Plugin URI: http://responsive.menu
Description: Responsive Menu Pro
Version: 1.0.9
Author: Responsive Menu
Text Domain: responsive-menu-pro
Author URI: http://responsive.menu
License: GPL2
Tags: responsive, menu, responsive menu, pro, responsive menu pro

|--------------------------------------------------------------------------
| Bootstrap The Application
|--------------------------------------------------------------------------
|
| This bootstraps the Responsive Menu and gets it ready for use, then it
| will load up the Responsive Menu application so that we can run it.
|
*/

require_once 'app/bootstrap.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can simply call the run method,
| which will setup everything we need to display the Responsive Menu 
| straight out the box with no extra customisation needed.
|
*/

$app->run();
