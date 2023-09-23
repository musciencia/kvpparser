<?php

$classLoader = require __DIR__ . '/../vendor/autoload.php';

// Add namespace to autoloader
// Using here instead of adding to composer.json 
// so that we can exclude test files wen package is intalled
$classLoader->addPsr4('Tests\\Unit\\', __DIR__ . '/unit');
