use GaiaAlpha\Hook;
use GaiaAlpha\Env;
use JwtAuth\Middleware;
use JwtAuth\Controller\JwtSettingsController;

// Register Middleware to handle JWT authentication early in the request lifecycle
Hook::add('app_boot', function () {
Middleware::handle();
});

// Register Controller
Hook::add('framework_load_controllers_after', function () {
$controllers = Env::get('controllers');
$controller = new JwtSettingsController();
$controller->init();
$controllers['jwt-settings'] = $controller;
Env::set('controllers', $controllers);
});

// Register CLI Commands
Hook::add('cli_resolve_command', function ($current, $group, $parts) {
if ($group === 'jwt') {
return JwtAuth\Cli\JwtCommands::class;
}
return $current;
});