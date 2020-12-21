<?php

use \Classes\PageAdmin;
use \Classes\Model\User;

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

//Esqueceu Sua Senha
$app->get("/admin/forgot", function(){

    $page = new PageAdmin([
        "header" => false,
        "footer" => false
    ]);

    $page->setTpl("forgot");

});

//Apos inserir dados na pagina de Forgot
$app->post("/admin/forgot", function(){

    $user = User::getForgot($_POST["email"]);

    header("Location: /admin/forgot/sent");
    exit;

});

//Codigo Enviado ao email
$app->get("/admin/forgot/sent", function(){

    $page = new PageAdmin([
        "header" => false,
        "footer" => false
    ]);

    $page->setTpl("forgot-sent");

});

//Pagina que o email redireciona
$app->get("/admin/forgot/reset", function(){

    $user = User::validForgotDecrypt($_GET["code"]);

    $page = new PageAdmin([
        "header" => false,
        "footer" => false
    ]);

    $page->setTpl("forgot-reset", array(
        "name"=>$user["desperson"],
        "code"=>$_GET["code"]
    ));

});

//Tela para mudar a senha após a verificação
$app->post("/admin/forgot/reset", function(){

    $forgot = User::validForgotDecrypt($_POST["code"]);

    User::setForgotUsed($forgot["idrecovery"]);

    $user = new User();

    $user->get((int)$forgot["iduser"]);

    $password = password_hash($_POST["password"], PASSWORD_DEFAULT, [
        "cost"=>12
    ]);

    $user->setPassword($password); //Senha que veio do FORM

    $page = new PageAdmin([
        "header" => false,
        "footer" => false
    ]);

    $page->setTpl("forgot-reset-success");

});

?>