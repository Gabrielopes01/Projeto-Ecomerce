<?php
session_start();
require_once("vendor/autoload.php");

use \Slim\Slim;
use \Classes\Page;
use \Classes\PageAdmin;
use \Classes\Model\User;

$app = new Slim();

$app->config('debug', true);

$app->get('/', function() {

	$page = new Page();

    $page->setTpl("index");

    //Destruct é chamado no fim do PHP, chamando o footer

});

$app->get('/admin', function() {

    User::verifyLogin();

    $page = new PageAdmin();

    $page->setTpl("index");

    //Destruct é chamado no fim do PHP, chamando o footer

});

$app->get('/admin/login', function() {


    $page = new PageAdmin([
        "header" => false,
        "footer" => false
    ]);

    $page->setTpl("login");

    //Destruct é chamado no fim do PHP, chamando o footer

});

$app->post('/admin/login', function(){

    User::login($_POST["deslogin"], $_POST["despassword"]);

    header("Location: /admin");

    exit;

});


$app->get('/admin/logout', function(){

    User::logout();

    header("Location: /admin/login");
    exit;
});

$app->get('/admin/users', function() {


    User::verifyLogin();

    $users = User::listAll();

    $page = new PageAdmin();

    $page->setTpl("users", array(
        "users"=>$users
    ));

});

$app->get('/admin/users/create', function() {


    User::verifyLogin();

    $page = new PageAdmin();

    $page->setTpl("users-create");

});

$app->get("/admin/users/:iduser/delete", function($iduser){   //Deixe acima do outro get iduser, pois ele pode confundir os dois sendo o mesmo e nao executar o dleete

    User::verifyLogin();

    $user = new User();

    $user->get((int)$iduser);

    $user->delete();

    header("Location: /admin/users");
    exit;

});

$app->get('/admin/users/:iduser', function($iduser) {


    User::verifyLogin();

    $user = new User();

    $user->get((int)$iduser);

    $page = new PageAdmin();

    $page->setTpl("users-update", array(
        "user"=>$user->getValues()
    ));

});


$app->post("/admin/users/create", function(){

    User::verifyLogin();

    $user = new User();

    $_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;

    $user->setData($_POST);

    $user->save();

    header("Location: /admin/users");
    exit;

});

$app->post("/admin/users/:iduser", function($iduser){

    User::verifyLogin();

    $user = new User();

    $user->get((int)$iduser);

    $user->setData($_POST);

    $user->update();

    header("Location: /admin/users");
    exit;

});


$app->run();

 ?>