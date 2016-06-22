<?php
namespace Transformatika\MVC;

use Transformatika\Config\Config;
use Zend\Diactoros\ServerRequestFactory;

class RouteDispatcher
{
    protected $controller;

    protected $action;

    protected $routes;

    protected $request;

    protected $dispatcher;

    public function __construct($routes)
    {
        $this->routes = $routes;
        $this->request = ServerRequestFactory::fromGlobals();
        $dir = Config::getRootDir().DS.'storage'.DS.'cache'.DS;
        $this->dispatcher = \FastRoute\cachedDispatcher(function (\FastRoute\RouteCollector $r) {
            // $r->addRoute('GET', '/user/{name}/{id:[0-9]+}', 'handler0');
            // $r->addRoute('GET', '/user/{id:[0-9]+}', 'handler1');
            // $r->addRoute('GET', '/user/{name}', 'handler2');
            foreach ($this->routes as $k => $v) {
                $method = explode('|', $v['method']);
                $v['path'] = !isset($v['path']) ? $v['match'] : $v['path'];
                if (count($method) > 1) {
                    foreach ($method as $key => $m) {
                        $r->addRoute($m, $v['path'],$v['controller']);
                    }
                } else {
                    $r->addRoute($v['method'], $v['path'], $v['controller']);
                }
            }
        }, [
            'cacheFile' => $dir . 'route.cache', /* required */
            'cacheDisabled' => false,     /* optional, enabled by default */
        ]);

        return $this;
    }

    public function dispatch()
    {
        // $httpMethod = $this->request->getMethod();
        // $uri = $this->request->getUri()->getPath();
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];
        $routeInfo = $this->dispatcher->dispatch($httpMethod, $uri);

        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                header('HTTP/1.0 404 Not Found');
                echo 'Page not found';
                exit();
                break;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                header('HTTP/1.0 405 Not Found');
                echo 'Method not allowed';
                exit();
                break;
            case \FastRoute\Dispatcher::FOUND:
                $explodeController = explode('#', $routeInfo[1]);
                $actionClass = $explodeController[0];
                $controller = new $actionClass();
                $data = $controller($explodeController[1], $this->request);
                if (isset($data['headers'])
                && isset($data['headers']['Content-Type'])
                && $data['headers']['Content-Type'] === 'application/json') {
                    foreach ($data['headers'] as $hKey => $hVal) {
                        header($hKey.":".$hVal);
                    }
                    echo json_encode($data);
                } else {
                    if (isset($data['redirect']) && $data['redirect'] === true) {
                        if (!isset($data['headers']['location'])) {
                            throw new Exception("Invalid headers. Headers location must be set", 1);
                        } else {
                            header('location:'.$data['headers']['location']);
                        }
                    } else {
                        if (!isset($data['template'])) {
                            throw new Exception("Template Configuration Not Found", 1);
                        }
                        foreach ($data['headers'] as $hKey => $hVal) {
                            header($hKey.":".$hVal);
                        }
                        $controller->display($data['template'], $data);
                    }
                }
                break;
        }
    }
}
