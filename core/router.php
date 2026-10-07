<?php

class Router{
    private $arr = [];

    public function get($route,callable $callback){
        $this->arr["GET"][$route] = $callback;
        return;
    }

    public function post($route,callable $callback){
        $this->arr["POST"][$route] = $callback;
        return;
    }

    public function dispatch($method,$route){
        $arr = $this[$method];
        if(!array_key_exists($route,$arr)){
            return http_response_code(405);
        }

        if(!isset($arr[$route])){
            return http_response_code(404);
        }

        arr[$method][$route];

        
    }
}

?>