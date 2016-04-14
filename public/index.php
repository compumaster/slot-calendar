<?php
require '../vendor/autoload.php';
session_cache_limiter(false);
session_start();

DEFINE("DOMAINTENANCE", 0);
DEFINE("HOSTNAME_DBCONN", "serverName");
DEFINE("DATABASE_DBCONN", "databaseName");
DEFINE("USERNAME_DBCONN", "databaseUser");
DEFINE("PASSWORD_DBCONN", "databasePassword");
DEFINE("ADMIN_ACCOUNT_PASSWORD", "verysecurepassword");

$app = new \Slim\Slim(array(
    'views.path' => '../views'
));

$app->view(new \Slim\Views\Twig());
$app->view->setTemplatesDirectory('../views');
$app->view->parserOptions = array(
    'charset' => 'utf-8',
    'cache' => realpath('../views/cache'),
    'auto_reload' => true,
    'strict_variables' => false,
    'autoescape' => true,
    'debug' => true
);

$app->view->parserExtensions = array(
    new \Slim\Views\TwigExtension(),
);


$twig = $app->view->getInstance();
$twig->addExtension(new Twig_Extension_Debug());

$services = glob('../services/*.php');
foreach ($services as $service) {
    require $service;
}

$models = glob('../models/*.php');
foreach ($models as $model) {
    require $model;
}

$routers = glob('../controllers/*.php');
foreach ($routers as $router) {
    require $router;
}

Model::initialSetup();

$app->hook('slim.before.router', function () use ($app) {
    if ($app->request->getMethod() == 'POST') {
        if (0 === strpos($app->request->getContentType(), 'application/json')) {
            $body = $app->request->post();
            $app->container['jsonRequest'] = json_decode($app->request->getBody(), true);
        }
    }
});

$app->hook('slim.before.dispatch', function() use ($app) {
    if (strpos($app->request()->getPathInfo(), '/admin') === 0)
        if (!isset($_SESSION['user'])) {
            $_SESSION['urlRedirect'] = $app->request()->getPathInfo();
            $app->flash('error', 'Login required');
            $app->redirectTo('index.login');
        } else {
            $app->view()->setData('user', $_SESSION['user']);
        }
    $app->view()->setData('settings', Model::getSettings());
});

// Run app
$app->run();
