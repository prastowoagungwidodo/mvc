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

    protected $middleware;

    public function __construct($routes, $middleware = null)
    {
        $this->routes = $routes;
        $this->request = ServerRequestFactory::fromGlobals();
        $dir = Config::getRootDir().DS.'storage'.DS.'cache'.DS;
        $this->middleware = $middleware;
        $this->dispatcher = \FastRoute\cachedDispatcher(function (\FastRoute\RouteCollector $r) {
            // $r->addRoute('GET', '/user/{name}/{id:[0-9]+}', 'handler0');
            // $r->addRoute('GET', '/user/{id:[0-9]+}', 'handler1');
            // $r->addRoute('GET', '/user/{name}', 'handler2');
            foreach ($this->routes as $k => $v) {
                $method = explode('|', $v['method']);
                $v['path'] = !isset($v['path']) ? $v['match'] : $v['path'];

                if (substr($v['path'], -1) == '/') {
                    $v['path'] = substr($v['path'], 0, -1);
                }

                if (count($method) > 1) {
                    foreach ($method as $key => $m) {
                        $r->addRoute($m, $v['path'], $v['controller']);
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
        $httpMethod = $this->request->getMethod();
        $uri = $this->request->getUri()->getPath();
        if ($uri !== '/' && substr($uri, -1) == '/') {
            $uri = substr($uri, 0, -1);
        }
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
                if (null !== $this->middleware && class_exists($this->middleware)) {
                    $md = new $this->middleware();
                    $md($this->request);
                }
                $explodeController = explode('#', $routeInfo[1]);
                $actionClass = $explodeController[0];
                $controller = new $actionClass();
                $data = $controller($explodeController[1], $this->request);

                $defaultResponse = Config::getConfig('response');
                $responseArray = [
                    'json' => 'application/json',
                    'html' => 'text/html'
                ];
                if (!isset($data['headers']) || !isset($data['headers']['Content-Type'])) {
                    $data['headers']['Content-Type'] = $responseArray[$defaultResponse];
                }
                if ($data['headers']['Content-Type'] === 'application/json') {
                    foreach ($data['headers'] as $hKey => $hVal) {
                        header($hKey.":".$hVal);
                    }
                    echo json_encode($data);
                } else {
                    if (isset($data['redirect']) && $data['redirect'] === true) {
                        if (!isset($data['headers']['location'])) {
                            throw new \Exception("Invalid headers. Headers location must be set", 1);
                        } else {
                            header('location:'.$data['headers']['location']);
                        }
                    } else {
                        if (!isset($data['template'])) {
                            throw new \Exception("Template Configuration Not Found", 1);
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
