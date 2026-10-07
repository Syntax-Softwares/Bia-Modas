<?php 
require_once __DIR__ . "/" . "../core/router.php";
require_once __DIR__ . "/" . "../controllers/home_controller.php";
require_once __DIR__ . "/" . "../core/database.php";

$pdo = Database::conenct();



$router = new Router();

$router->get("/", [new HomeController($pdo), "index"] );

$router->dispatch($_SERVER["REQUEST_METHOD"], $_SERVER["REQUEST_URI"]);

?>