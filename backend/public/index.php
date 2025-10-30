<?php

/**
 * CodeIgniter 4 Framework Entry Point
 *
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

/*
 *---------------------------------------------------------------
 * BOOTSTRAP THE APPLICATION
 *---------------------------------------------------------------
 * This process sets up the path constants, loads and registers
 * our autoloader, along with Composer's, loads our constants
 * and fires up an environment-specific bootstrapping.
 */

// Ensure the current directory is pointing to the front controller's directory
if (getcwd() . DIRECTORY_SEPARATOR !== FCPATH) {
    chdir(FCPATH);
}

// Load our paths config file
// This is the line that might need to be changed, depending on your folder structure.
$pathsConfig = FCPATH . '../app/Config/Paths.php';
// ^^^ Change this line if you move your application folder
require realpath($pathsConfig) ?: $pathsConfig;

$paths = new Config\Paths();

// Location of the autoloader
$autoloader = $paths->systemDirectory . '/../autoload.php';
if (!file_exists($autoloader)) {
    $autoloader = $paths->systemDirectory . '/../../autoload.php';
}
require realpath($autoloader) ?: $autoloader;

// Load environment settings
require_once $paths->systemDirectory . '/Config/DotEnv.php';
(new CodeIgniter\Config\DotEnv($paths->appDirectory . '/../'))->load();

// Define ENVIRONMENT
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', env('CI_ENVIRONMENT', 'production'));
}

// Load path constants
$pathsConfig = $paths->systemDirectory . '/Config/Paths.php';
require realpath($pathsConfig) ?: $pathsConfig;

// Load common functions
$common = $paths->systemDirectory . '/Common.php';
require_once realpath($common) ?: $common;

// Load environment-specific boot file
$bootFile = $paths->appDirectory . '/Config/Boot/' . ENVIRONMENT . '.php';
if (is_file($bootFile)) {
    require $bootFile;
}

/*
 * ---------------------------------------------------------------
 * GRAB OUR CODEIGNITER INSTANCE
 * ---------------------------------------------------------------
 *
 * The CodeIgniter class contains the core functionality to make
 * the application run, and does all of the dirty work to get
 * the pieces all working together.
 */

$app = Config\Services::codeigniter();
$app->initialize();
$context = is_cli() ? 'php-cli' : 'web';

/*
 *---------------------------------------------------------------
 * CORS HANDLING - Allow all origins for API access
 *---------------------------------------------------------------
 * This handles CORS headers before the request is processed.
 * Allows all origins to access the API.
 */
if (!is_cli()) {
    // Set CORS headers for all requests
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
    header('Access-Control-Expose-Headers: Content-Type, X-Total-Count');
    header('Content-Type: application/json');

    // Handle preflight OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit(0);
    }
}

/*
 *---------------------------------------------------------------
 * LAUNCH THE APPLICATION
 *---------------------------------------------------------------
 * Now that everything is set up, it's time to actually fire
 * up the engines and make this app do its thing.
 */

$app->run();
