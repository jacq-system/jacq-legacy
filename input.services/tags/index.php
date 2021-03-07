<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Http\Request;
use Slim\Http\Response;

/************************
 * include all settings *
 ************************/
include __DIR__ . '/../inc/variables.php';
$settings = [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        'db' => [
            'host'     => $_CONFIG['DATABASES']['HERBARINPUT']['host'],
            'database' => $_CONFIG['DATABASES']['HERBARINPUT']['db'],
            'username' => $_CONFIG['DATABASES']['HERBARINPUT']['user'],
            'password' => $_CONFIG['DATABASES']['HERBARINPUT']['pass']
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => __DIR__ . '/../logs/tags.log',
            'level' => \Monolog\Logger::DEBUG,
        ],

        'guidUrlPrefix' => $_CONFIG['guidUrlPrefix'],
        'APIKEY' => $_CONFIG['APIKEY'],
    ],
];



/***********************
 * Instantiate the app *
 ***********************/
$app = new \Slim\App($settings);



/***********************
 * Set up dependencies *
 ***********************/
$container = $app->getContainer();
// monolog
$container['logger'] = function ($c)
{
    $settings = $c->get('settings')['logger'];
    $logger = new \Monolog\Logger($settings['name']);
    $logger->pushProcessor(new \Monolog\Processor\UidProcessor());
    $logger->pushHandler(new \Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

$container['db'] = function ($c)
{
    $db = $c['settings']['db'];
    $dbLink = new mysqli($db['host'], $db['username'], $db['password'], $db['database']);
    $dbLink->set_charset('utf8');
    return $dbLink;
};

//Add container to handle all runtime exceptions/errors, fail safe and return json
//works only for PHP 7.x
//$container['phpErrorHandler'] = function ($container) {
//    return function ($request, $response, $exception) use ($container) {
//        $data = [
//            'message' => $exception->getMessage()
//        ];
//        $jsonResponse = $response->withStatus(500)->withJson($data);
//        return $jsonResponse;
//    };
//};



/***********************
 * Register middleware *
 ***********************/
$app->add(function (Request $request, Response $response, $next)
{
//    if ($request->getUri()->getScheme() != 'https') {
//        return $response->withRedirect($request->getUri()->withScheme('https'));
//    } else
        if (!in_array($this->get('settings')['APIKEY'], $request->getHeader('APIKEY'))) {
        return $response->withStatus(403, 'Forbidden');
    } else {
    	return $next($request, $response);
    }
});



/*******************
 * Register routes *
 *******************/
$app->get('/uuid/{type}/{id}', function (Request $request, Response $response, array $args)
{
//    $this->logger->addInfo("called uuidUrl with <" . trim(filter_var($args['type'], FILTER_SANITIZE_STRING)) . ">");

    $mapper = new IdentifierMapper($this->db);
    $uuid = $mapper->getUuid(trim(filter_var($args['type'], FILTER_SANITIZE_STRING)),
                             intval(filter_var($args['id'], FILTER_SANITIZE_NUMBER_INT)));
    $jsonResponse = $response->withJson(array('uuid' => $uuid, 'url' => $this->get('settings')['guidUrlPrefix'] . $uuid));
    return $jsonResponse;
});

$app->get('/ids/{uuid}', function (Request $request, Response $response, array $args)
{
//    $this->logger->addInfo("called id with <" . trim(filter_var($args['uuid'], FILTER_SANITIZE_STRING)) . ">");

    $mapper = new IdentifierMapper($this->db);
    $ids = $mapper->getIDs(trim(filter_var($args['uuid'], FILTER_SANITIZE_STRING)));
    $jsonResponse = $response->withJson($ids);
    return $jsonResponse;
});

$app->get('/[{name}]', function (Request $request, Response $response, array $args)
{
    // Sample log message
    $this->logger->addInfo("Slim-Skeleton '/' route");

    $name = array('catch-all: ' => $args['name']);
    $jsonResponse = $response->withJson($name);
    return $jsonResponse;
});



/***********
 * Run app *
 ***********/
$app->run();
