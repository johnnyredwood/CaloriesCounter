<?php
date_default_timezone_set('America/New_York');
$conexion = mysqli_connect("localhost:3307", "usuario", "", "nutrition");
if (mysqli_connect_errno())
{
echo "Failed to connect to MySQL: " . mysqli_connect_error();
} else {
echo "";
}

// Bootstrap configuration for CaloriesCounter with optional Dotenv support

// 1) Try Composer autoload if present
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
	require __DIR__ . '/vendor/autoload.php';
}

// 2) Load environment variables via vlucas/phpdotenv if available
if (class_exists('Dotenv\\Dotenv')) {
	try {
		$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
		// safeLoad: continues if .env is missing
		$dotenv->safeLoad();
	} catch (Throwable $e) {
		// Silently continue to avoid breaking the app if dotenv fails
	}
}

// 3) Helpers to read env values with defaults
function env(string $key, $default = null)
{
	if (array_key_exists($key, $_ENV)) {
		return $_ENV[$key];
	}
	$val = getenv($key);
	return $val !== false ? $val : $default;
}

// Example: configure database using env with sensible defaults
$DB_HOST = env('DB_HOST', 'localhost');
$DB_NAME = env('DB_NAME', 'calories');
$DB_USER = env('DB_USER', 'root');
$DB_PASS = env('DB_PASS', '');
$DB_CHARSET = env('DB_CHARSET', 'utf8mb4');

// If this project uses PDO, construct a DSN here for downstream files
$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";

// Define constants if the rest of the app expects them
if (!defined('DB_HOST')) define('DB_HOST', $DB_HOST);
if (!defined('DB_NAME')) define('DB_NAME', $DB_NAME);
if (!defined('DB_USER')) define('DB_USER', $DB_USER);
if (!defined('DB_PASS')) define('DB_PASS', $DB_PASS);
if (!defined('DB_CHARSET')) define('DB_CHARSET', $DB_CHARSET);
if (!defined('DB_DSN')) define('DB_DSN', $dsn);
