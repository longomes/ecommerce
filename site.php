<?php

use \Hcode\Page;
use \Hcode\Model\Category;
use \Hcode\Model\Product;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;
use \Hcode\Model\User;

// Home
$app->get('/', function() {
	$products = Product::listAll();
	
	$page = new Page;
	$page->setTpl('main',[
		'products' => Product::checkList($products)
	]);
});

$app->get('/categories/:idcategory', function($idcategory) {
	$numPage = (isset($_GET['page'])) ? (int) $_GET['page'] : 1;

	$category = new Category;
	$category->getById((int)$idcategory);
	
	$pagination = $category->getProductsPage($numPage);
	$pages = [];

	for ($i=1; $i <= $pagination['pages']; $i++) {
		array_push($pages, [ 
			'link' => '/categories/' . 
						$category->getidcategory() . 
						'?page=' . $i,
			'page' => $i
		]);
	}

	$page = new Page;
	$page->setTpl('category', [
		'category' => $category->getValues(),
		'products' => $pagination['data'],
		'pages' => $pages
	]);
});

$app->get('/products/:desurl', function($desurl) {
	$product = new Product;
	$product->getFromURL($desurl);

	$page = new Page;
	$page->setTpl('product-detail', [
		'product' => $product->getValues(),
		'categories' => $product->getCategories()
	]);
});

$app->get('/cart', function() {
	$cart = Cart::getFromSession();

	$page = new Page;
	$page->setTpl('cart', [
		'cart' => $cart->getValues(),
		'products' => $cart->getProducts(),
		'error' => Cart::getMsgError()
	]);
});

$app->get('/cart/:idproduct/add', function($idproduct) {
	$product = new Product;
	$product->getById((int)$idproduct);

	$cart = Cart::getFromSession();
	$qtd = (isset($_GET['qtd'])) ? (int) $_GET['qtd'] : 1;

	for ($i=0; $i < $qtd; $i++) {
		$cart->addProduct($product);
	}	

	header('Location: /cart');
	exit;
});

// Remove one only
$app->get('/cart/:idproduct/minus', function($idproduct) {
	$product = new Product;
	$product->getById((int)$idproduct);

	$cart = Cart::getFromSession();
	$cart->removeProduct($product);

	header('Location: /cart');
	exit;
});

// Remove all (boolean true)
$app->get('/cart/:idproduct/remove', function($idproduct) {
	$product = new Product;
	$product->getById((int)$idproduct);

	$cart = Cart::getFromSession();
	$cart->removeProduct($product, true);

	header('Location: /cart');
	exit;
});

$app->post('/cart/freight', function() {
	$cart = Cart::getFromSession();
	$cart->setFreight($_POST['zipcode']);

	header('Location: /cart');
	exit;
});

$app->get('/checkout', function() {
	User::verifyLogin(false);

	$cart = Cart::getFromSession();
	$address = new Address;

	$page = new Page;
	$page->setTpl('checkout', [
		'cart' => $cart->getValues(),
		'address' => $address->getValues()
	]);
});

$app->get('/login', function() {
	$address = new Address;

	$page = new Page;
	$page->setTpl('login', [
		'error' => User::getError(),
		'errorRegister' => User::getErrorRegister(),
		'registerValues' => (isset($_SESSION['REGISTER_VALUES'])) ? 
			$_SESSION['REGISTER_VALUES'] : ['name'=>'', 'email'=>'', 'phone'=>'']
	]);
});

$app->post('/login', function() {
	try {
		User::login($_POST['login'], $_POST['password']);
	} catch(Exception $e) {
		User::setError($e->getMessage());
	}	
	
	header('Location: /checkout');
	exit;
});

$app->get('/logout', function() {
	User::logout();

	header('Location: /login');
	exit;
});

$app->post('/register', function() {
	// Para manter as informações já enviadas
	$_SESSION['REGISTER_VALUES'] = $_POST;

	if (!isset($_POST['name']) ||
		$_POST['name'] == '') {
			User::setErrorRegister('Informe o seu nome.');
			header('Location: /login');
			exit;
		}
	
	if (!isset($_POST['email']) ||
		$_POST['email'] == '') {
			User::setErrorRegister('Informe um email válido.');
			header('Location: /login');
			exit;
		}
	
	if (!isset($_POST['password']) ||
		$_POST['password'] == '') {
			User::setErrorRegister('Preencha a senha.');
			header('Location: /login');
			exit;
		}
	
	if (User::checkLoginExists($_POST['email'])) {
		User::setErrorRegister('Email informado já está em uso.');
		header('Location: /login');
		exit;
	}

	$user = new User;
	$user->setValues([
		'deslogin' => $_POST['email'],
		'desperson' => $_POST['name'],
		'desemail' => $_POST['email'],
		'despassword' => $_POST['password'],
		'nrphone' => $_POST['phone'],
		'inadmin' => 0
	]);

	$user->save();	
	User::login($_POST['email'], $_POST['password']);

	header('Location: /checkout');
	exit;
});

$app->get('/forgot', function() {
	$page = new Page;
	$page->setTpl('forgot');
});

$app->post('/forgot', function() {
	$user = User::getForgot($_POST['email'], false);

	header('Location: /forgot/sent');
	exit;
});

$app->get('/forgot/sent', function() {
	$page = new Page;
	$page->setTpl('forgot-sent');
});

$app->get('/forgot/reset', function(){
	$user = User::validForgotDecrypt($_GET['code']);
	
	$page = new Page;	
	$page->setTpl('forgot-reset', [
		'name' => $user['desperson'],
		'code' => $_GET['code']
	]);
});

$app->post('/forgot/reset', function(){
	$forgot = User::validForgotDecrypt($_POST['code']);

	User::setForgotUsed($forgot['idrecovery']);

	$user = new User;
	$user->getById((int)$forgot['iduser']);
	$user->setPassword($_POST['password']);

	$page = new Page();	
	$page->setTpl('forgot-reset-success');
});