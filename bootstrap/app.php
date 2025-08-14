<?php
// Autoload Composer dependencies

use \Illuminate\Support\Carbon as Date;
use Illuminate\Support\Facades\Facade;
use Monolog\Level;

require_once __DIR__ . '/../vendor/autoload.php';

// Set up your application configuration
// Initialize slim application
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$_ENV['APP_KEY'] = env('APP_KEY');

// Crea un'istanza del gestore del database (Capsule)
$capsule = new \Illuminate\Database\Capsule\Manager();

// Aggiungi la configurazione del database al Capsule
$connections = require_once __DIR__.'/../config/database.php';
$capsule->addConnection($connections['mysql']);

// Esegui il boot del Capsule
$capsule->bootEloquent();
$capsule->setAsGlobal();

// Set up the logger
require_once __DIR__ . '/../config/logger.php';

// validator laravel
$validator = new \Illuminate\Validation\Factory(
    new \Illuminate\Translation\Translator(
        new \Illuminate\Translation\ArrayLoader(),
        'en'
    ),
);

//Setup cryptable
require_once __DIR__ . '/../config/cryptable.php';

// Set up the Facade application
Facade::setFacadeApplication([
    'log' => $logger,
    'date' => new Date('now', new DateTimeZone('Europe/Rome')),
    'validator' => $validator,
    'crypt' => $crypt,
    'db' => $capsule->getDatabaseManager(),

]);
