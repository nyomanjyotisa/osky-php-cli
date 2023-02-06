
<?php
require_once __DIR__ . '/vendor/autoload.php';
use Symfony\Component\Console\Application;
use Osky\SearchCommand;
$app = new Application('Redit Search', 'v0.1.0');
$app -> add(new SearchCommand());
$app -> run();