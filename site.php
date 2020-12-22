<?php

use \Classes\Page;
use \Classes\Model\Product;
use \Classes\Model\User;
use \Classes\Model\Category;
use \Classes\Model\Cart;
use \Classes\Model\Address;

$app->get('/', function() {

    $products = Product::listAll();

    $page = new Page();

    $page->setTpl("index", [
        "products"=>Product::checkList($products)
    ]);

    //Destruct é chamado no fim do PHP, chamando o footer

});

$app->get("/categories/:idcategory", function($idcategory){

    User::verifyLogin();

    $page = (isset($_GET["page"])) ? (int)$_GET["page"] : 1;

    $category = new Category();

    $category->get((int)$idcategory);

    $pagination = $category->getProductsPage($page);

    $pages = [];

    for ($i=1; $i <= $pagination["pages"] ; $i++) {
        array_push($pages, [
            "link"=>"/categories/".$category->getidcategory()."?page=".$i,
            "page"=>$i
        ]);
    }

    $page = new Page();

    $page->setTpl("category", [
        "category" => $category->getValues(),
        "products" => $pagination["data"],
        "pages"=> $pages
    ]);

});


$app->get("/products/:desurl", function($desurl) {

    $product = new Product;

    $product->getFromURL($desurl);

    $page = new Page();

    $page->setTpl("product-detail", [
        "product"=>$product->getValues(),
        "categories"=>$product->getCategories()
    ]);

});

$app->get("/cart", function(){

    $cart = Cart::getFromSession();

    $page = new Page();

    $page->setTpl("cart", [
        "cart"=>$cart->getValues(),
        "products"=>$cart->getProducts(),
        "error"=>Cart::getMsgError()
    ]);

});


$app->get("/cart/:idproduct/add", function($idproduct){

    $product = new Product();

    $product->get((int)$idproduct);

    $cart = Cart::getFromSession();

    $qtd = (isset($_GET["Qtd"]) ? (int)$_GET["Qtd"] : 1);

    for ($i=0; $i < $qtd; $i++) {
        $cart->addProduct($product);
    }

    header("Location: /cart");
    exit;

});

//Remove apenas um item do carrinho
$app->get("/cart/:idproduct/minus", function($idproduct){

    $product = new Product();

    $product->get((int)$idproduct);

    $cart = Cart::getFromSession();

    $cart->removeProduct($product);

    header("Location: /cart");
    exit;

});

//Remoção de Produto
$app->get("/cart/:idproduct/remove", function($idproduct){

    $product = new Product();

    $product->get((int)$idproduct);

    $cart = Cart::getFromSession();

    $cart->removeProduct($product, true);

    header("Location: /cart");
    exit;

});

//Frete
$app->post("/cart/freight", function(){

    $cart = Cart::getFromSession();

    $cart->setFreight($_POST["zipcode"]);

    header("Location: /cart");
    exit;

});

//Checando informaçoes antes da compra
$app->get("/checkout", function(){

    User::verifyLogin(false);

    $cart = Cart::getFromSession();

    $address = new Address();

    $page = new Page();

    $page->setTpl("checkout", [
        "cart"=>$cart->getValues(),
        "address"=>$address->getValues()
    ]);

});


$app->get("/login", function(){

    $page = new Page();

    $page->setTpl("login", [
        "error"=>User::getError(),
        "errorRegister"=>User::getErrorRegister(),
        "registerValues"=>(isset($_SESSION["registerValues"])) ? $_SESSION["registerValues"] : ["name"=>"", "email"=>"", "phone"=>""]
    ]);

});

//Acessar o site de usuario via Login
$app->post("/login", function(){

    try{

        User::login($_POST["login"], $_POST["password"]);
        header("Location: /checkout");
        exit;

    } catch(Exception $e){

        User::setError($e->getMessage());

    }

    header("Location: /login");
    exit;

});

//Deslogar do site de usuario
$app->get("/logout", function(){

    User::logout();

    header("Location: /login");
    exit;

});


//Registro de usuarios
$app->post("/register", function(){

    $_SESSION["registerValues"] = $_POST;

    if (!isset($_POST["name"]) || $_POST["name"] == "") {

        User::setErrorRegister("Preencha o seu nome de usuário");
        header("Location: /login");
        exit;

    }

   if (!isset($_POST["email"]) || $_POST["email"] == "") {

        User::setErrorRegister("Preencha o seu email");
        header("Location: /login");
        exit;

    }

    if (!isset($_POST["password"]) || $_POST["password"] == "") {

        User::setErrorRegister("Preencha a senha");
        header("Location: /login");
        exit;

    }

    if (User::checkLoginExists($_POST["email"]) === true){

        User::setErrorRegister("Este endereço de email ja está sendo usado por outro usuário");
        header("Location: /login");
        exit;

    }

    $user = new User();

    $user->setData([
        "inadmin"=>0,
        "deslogin"=>$_POST["email"],
        "desperson"=>$_POST["name"],
        "desemail"=>$_POST["email"],
        "despassword"=>$_POST["password"],
        "nrphone"=>$_POST["phone"]
    ]);

    $user->save();

    User::login($_POST["email"], $_POST["password"]);

    header("Location: /checkout");
    exit;

});



//Esqueceu Sua Senha
$app->get("/forgot", function(){

    $page = new Page();

    $page->setTpl("forgot");

});

//Apos inserir dados na pagina de Forgot
$app->post("/forgot", function(){

    $user = User::getForgot($_POST["email"], false);

    header("Location: /forgot/sent");
    exit;

});

//Codigo Enviado ao email
$app->get("/forgot/sent", function(){

    $page = new Page();

    $page->setTpl("forgot-sent");

});

//Pagina que o email redireciona
$app->get("/forgot/reset", function(){

    $user = User::validForgotDecrypt($_GET["code"]);

    $page = new Page();

    $page->setTpl("forgot-reset", array(
        "name"=>$user["desperson"],
        "code"=>$_GET["code"]
    ));

});

//Tela para mudar a senha após a verificação
$app->post("/forgot/reset", function(){

    $forgot = User::validForgotDecrypt($_POST["code"]);

    User::setForgotUsed($forgot["idrecovery"]);

    $user = new User();

    $user->get((int)$forgot["iduser"]);

    $password = password_hash($_POST["password"], PASSWORD_DEFAULT, [
        "cost"=>12
    ]);

    $user->setPassword($password); //Senha que veio do FORM

    $page = new Page();

    $page->setTpl("forgot-reset-success");

});


$app->get("/profile", function(){

    User::verifyLogin(false);

    $user = User::getFromSession();

    User::getPersonValues($user, $user->getiduser());

    $page = new Page();

    $page->setTpl("profile", [
        "user"=>$user->getValues(),
        "profileMsg"=>User::getSucess(),
        "profileError"=>User::getError()
    ]);

});


$app->post("/profile", function(){

    User::verifyLogin(false);

    if (!isset($_POST["desperson"]) || $_POST["desperson"] === ""){
        User::setError("Preencha o seu nome");
        header("Location: /profile");
        exit;
    }

    if (!isset($_POST["desemail"]) || $_POST["desemail"] === ""){
        User::setError("Preencha o seu email");
        header("Location: /profile");
        exit;
    }

    $user = User::getFromSession();

    User::getPersonValues($user, $user->getiduser());

    if ($_POST["desemail"] !== $user->getdesemail()){

        if (User::checkLoginExists($_POST["desemail"]) === true){

            User::setError("Este endereço de email ja esta cadastrado");
            header("Location: /profile");
            exit;

        }

    }

    $_POST["inadmin"] = $user->getinadmin();
    $_POST["despassword"] = $user->getdespassword();
    $_POST["deslogin"] = $_POST["desemail"];

    $user->setData($_POST);

    $user->save();

    User::setSucess("Dados alterados com sucesso");

    header("Location: /profile");
    exit;

});


?>