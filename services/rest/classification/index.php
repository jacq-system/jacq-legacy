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
            'path' => __DIR__ . '/../logs/classification.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
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



/*******************
 * Register routes *
 *******************/
$app->get('/references/{referenceType}', function (Request $request, Response $response, array $args)
{
//    $this->logger->addInfo("called references ");

    $mapper = new ClassificationMapper($this->db);
    $references = $mapper->getReferences($args['referenceType']);
    $jsonResponse = $response->withJson($references);
    return $jsonResponse;
});

$app->get('/nameReferences/{taxonID}', function (Request $request, Response $response, array $args)
{
//    $this->logger->addInfo("called references ");

    $mapper = new ClassificationMapper($this->db);
    $nameReferences = $mapper->getNameReferences($args['taxonID'], $request->getQueryParam('excludeReferenceId'));
    $jsonResponse = $response->withJson($nameReferences);
    return $jsonResponse;
});

$app->get('/children/{referenceType}/{referenceId}', function (Request $request, Response $response, array $args)
{
//    $this->logger->addInfo("called children " . intval($request->getQueryParam('taxonID')));

    $mapper = new ClassificationMapper($this->db);
    $children = $mapper->getChildren($args['referenceType'], $args['referenceId'], $request->getQueryParam('taxonID'));
    $jsonResponse = $response->withJson($children);
    return $jsonResponse;
});

$app->get('/synonyms/{referenceType}/{referenceId}/{taxonID}', function (Request $request, Response $response, array $args)
{
//    $this->logger->addInfo("called synonyms. args=" . var_export($args, true));

    $mapper = new ClassificationMapper($this->db);
    $synonyms = $mapper->getSynonyms($args['referenceType'], $args['referenceId'], $args['taxonID']);
    $jsonResponse = $response->withJson($synonyms);
    return $jsonResponse;
});

$app->get('/parent/{referenceType}/{referenceId}/{taxonID}', function (Request $request, Response $response, array $args)
{
//    $this->logger->addInfo("called parent. args=" . var_export($args, true));

    $mapper = new ClassificationMapper($this->db);
    $synonyms = $mapper->getParent($args['referenceType'], $args['referenceId'], $args['taxonID']);
    $jsonResponse = $response->withJson($synonyms);
    return $jsonResponse;
});

$app->get('/numberOfChildrenWithChildrenCitation/{referenceId}', function (Request $request, Response $response, array $args)
{
//    $this->logger->addInfo("called references ");

    $mapper = new ClassificationMapper($this->db);
    $number = $mapper->getNumberOfChildrenWithChildrenCitation($args['referenceId'], $request->getQueryParam('taxonID'));
    $jsonResponse = $response->withJson($number);
    return $jsonResponse;
});

$app->get('/periodicalStatistics/{referenceId}', function (Request $request, Response $response, array $args)
{
//    $this->logger->addInfo("called periodicalStatistics ");

    $mapper = new ClassificationMapper($this->db);
    $statistics = $mapper->getPeriodicalStatistics($args['referenceId']);
    $jsonResponse = $response->withJson($statistics);
    return $jsonResponse;
});

$app->get('/childrenJsTree/{referenceType}/{referenceId}', function (Request $request, Response $response, array $args)
{
//    $this->logger->addInfo("called childrenJsTree " . intval($request->getQueryParam('taxonID')));

    $mapper = new ClassificationMapper($this->db);
    $browser = new JsTreeBrowser($mapper);
    $result = $browser->getChildren($args['referenceType'], $args['referenceId'], $request->getQueryParam('taxonID'));
    $jsonResponse = $response->withJson($result);
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
