<?php

require_once("vendor/autoload.php");

use \Slim\Slim;
use \Classes\Page;
use \Classes\PageAdmin;

$app = new Slim();

$app->config('debug', true);

$app->get('/', function() {

	$page = new Page();

    $page->setTpl("index");

    //Destruct é chamado no fim do PHP, chamando o footer

});

$app->get('/admin', function() {

    $page = new PageAdmin();

    $page->setTpl("index");

    //Destruct é chamado no fim do PHP, chamando o footer

});

$app->run();

 ?>