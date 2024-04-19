<?php

/**
 * DO NOT MODIFY THIS FILE!
 */

global $config;
require __DIR__."/vendor/autoload.php";

$config = require 'config/config.php';
use Lepton\Core\Application;

Application::loadErrorHandler();
Application::loadConfig($config);

Application::run();
