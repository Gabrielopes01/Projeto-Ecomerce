<?php

use \Classes\Model\Category;
use \Classes\Model\User;
use \Classes\Model\Product;
use \Classes\Page;
use \Classes\PageAdmin;

//Categorias
$app->get("/admin/categories", function(){

    User::verifyLogin();

    $categories = Category::listAll();

    $page = new PageAdmin();

    $page->setTpl("categories", array(
        "categories"=>$categories
    ));


});

//Adiconar Categorias
$app->get("/admin/categories/create", function(){

    User::verifyLogin();

    $page = new PageAdmin();

    $page->setTpl("categories-create");


});

//Post da criaçao de categoria
$app->post("/admin/categories/create", function(){

    User::verifyLogin();

    $category = new Category();

    $category->setData($_POST);

    $category->save();

    header("Location: /admin/categories");
    exit;

});


//Deletar Categoria
$app->get("/admin/categories/:idcategory/delete", function($idcategory){

    User::verifyLogin();

    $category = new Category();

    $category->get((int)$idcategory);

    $category->delete();

    header("Location: /admin/categories");
    exit;

});


//Pagina que recebe o valor da categoria para altera-la
$app->get("/admin/categories/:idcategory", function($idcategory){

    User::verifyLogin();

    $category = new Category();

    $category->get((int)$idcategory);

    $page = new PageAdmin();

    $page->setTpl("categories-update", [
        "category"=>$category->getValues()
    ]);

});


//Este metodo post executa comandows no BD alterando a categoria
$app->post("/admin/categories/:idcategory", function($idcategory){

    User::verifyLogin();

    $category = new Category();

    $category->get((int)$idcategory);

    $category->setData($_POST);

    $category->save();

    header("Location: /admin/categories");
    exit;

});


$app->get("/admin/categories/:idcategory/products", function($idcategory){

    User::verifyLogin();

    $category = new Category();

    $category->get((int)$idcategory);

    $page = new PageAdmin();

    $page->setTpl("categories-products", [
        "category" => $category->getValues(),
        "productsRelated" =>$category->getProducts(),
        "productsNotRelated" =>$category->getProducts(false)
    ]);

});


$app->get("/admin/categories/:idcategory/products/:idproduct/add", function($idcategory, $idproduct){

    User::verifyLogin();

    $category = new Category();

    $category->get((int)$idcategory);

    $product = new Product();

    $product->get((int)$idproduct);

    $category->addProduct($product);

    header("Location: /admin/categories/".$idcategory."/products");
    exit;

});

$app->get("/admin/categories/:idcategory/products/:idproduct/remove", function($idcategory, $idproduct){

    User::verifyLogin();

    $category = new Category();

    $category->get((int)$idcategory);

    $product = new Product();

    $product->get((int)$idproduct);

    $category->removeProduct($product);

    header("Location: /admin/categories/".$idcategory."/products");
    exit;

});



?>