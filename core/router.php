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
        
        if(!array_key_exists($route,$this->arr[$method])){
            return http_response_code(405);
        }

        if(!isset($this->arr[$method][$route])){
            return http_response_code(404);
        }

        $callable = $this->arr[$method][$route];
        $callable();

        
    }
}

?>