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

    $page->setTpl("cart");

})

?>