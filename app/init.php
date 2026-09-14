<?php
// Initialize core MVC classes
require_once __DIR__ . '/../core/App.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Database.php';

// Load helpers
require_once __DIR__ . '/helpers/csrf_helper.php';
require_once __DIR__ . '/helpers/periode_helper.php';
require_once __DIR__ . '/helpers/financial_helper.php';

require_once __DIR__ . '/helpers/env_helper.php';
load_env(__DIR__ . '/../.env');

// Define base URL for assets and routing
if (!defined('BASE_URL')) {
    define('BASE_URL', rtrim(env('BASE_URL', 'http://localhost/KAsql'), '/'));
}
