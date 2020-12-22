<?php

use \Classes\Page;
use \Classes\Model\Product;
use \Classes\Model\User;
use \Classes\Model\Category;
use \Classes\Model\Cart;

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

//Redmove apenas um item do carrinho
$app->get("/cart/:idproduct/minus", function($idproduct){

    $product = new Product();

    $product->get((int)$idproduct);

    $cart = Cart::getFromSession();

    $cart->removeProduct($product);

    header("Location: /cart");
    exit;

});

$app->get("/cart/:idproduct/remove", function($idproduct){

    $product = new Product();

    $product->get((int)$idproduct);

    $cart = Cart::getFromSession();

    $cart->removeProduct($product, true);

    header("Location: /cart");
    exit;

});


$app->post("/cart/freight", function(){

    $cart = Cart::getFromSession();

    $cart->setFreight($_POST["zipcode"]);

    header("Location: /cart");
    exit;

});

?>