<?php 
require_once __DIR__ . "/" . "../core/router.php";
require_once __DIR__ . "/" . "/../controllers/home_controller.php";

$router = new Router();

$router->get("/", [HomeController::class, "index"] );

$router->dispatch($_SERVER["REQUEST_METHOD"], $_SERVER["REQUEST_URI"]);

?>