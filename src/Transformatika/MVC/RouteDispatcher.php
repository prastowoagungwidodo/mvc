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

    protected $middleWare;

    protected $redirectUrl = [
        404 => '',
        405 => ''
    ];

    public function __construct($routes, $middleWare = null)
    {
        $this->routes = $routes;
        $this->request = ServerRequestFactory::fromGlobals();
        $dir = Config::getRootDir().DS.'storage'.DS.'cache'.DS;
        $this->middleWare = $middleWare;
        $this->dispatcher = \FastRoute\cachedDispatcher(function (\FastRoute\RouteCollector $r) {
            // $r->addRoute('GET', '/user/{name}/{id:[0-9]+}', 'handler0');
            // $r->addRoute('GET', '/user/{id:[0-9]+}', 'handler1');
            // $r->addRoute('GET', '/user/{name}', 'handler2');
            foreach ($this->routes as $k => $v) {
                $method = explode('|', $v['method']);
                $v['path'] = !isset($v['path']) ? $v['match'] : $v['path'];

                if ($v['path'] !== '/' && substr($v['path'], -1) == '/') {
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

    public function setMiddleWare($middleWare)
    {
        $this->middleWare = $middleWare;
        return $this;
    }

    public function setRedirectUrl($options)
    {
        $this->redirectUrl = array(
            404 => !isset($options[404]) ? '' : $options[404],
            405 => !isset($options[405]) ? '' : $options[405]
        );
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
                if (!empty($this->redirectUrl[404])) {
                    header('location: ' . $this->redirectUrl[404]);
                } else {
                    header('HTTP/1.1 404 Not Found');
                    header('Content-Type: text/plain');
                    echo "HTTP/1.1 404 Not Found\n";
                    echo "Content-Type: text/plain\n";
                    echo "\n";
                    echo "Page Not Found";
                    echo "\n";
                }

                exit();
                break;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                if (!empty($this->redirectUrl[405])) {
                    header('location: ' . $this->redirectUrl[405]);
                } else {
                    header('HTTP/1.1 405 Method Not Allowed');
                    header('Content-Type: text/plain');
                    echo "HTTP/1.1 405 Method Not Allowed\n";
                    echo "Content-Type: text/plain\n";
                    echo "\n";
                    echo "Method Not Allowed";
                    echo "\n";
                }
                exit();
                break;
            case \FastRoute\Dispatcher::FOUND:
                if (null !== $this->middleWare && class_exists($this->middleWare)) {
                    $md = new $this->middleWare();
                    $md($this->request);
                }
                foreach ($routeInfo[2] as $k => $v) {
                    $this->request = $this->request->withAttribute($k, $v);
                }
                $explodeController = explode('#', $routeInfo[1]);
                $actionClass = $explodeController[0];
                $controller = new $actionClass();
                $data = $controller($explodeController[1], $this->request);
                $defaultResponse = Config::getConfig('response');
                $responseArray = [
                    'json' => 'application/json',
                    'html' => 'text/html',
                    'text' => 'text/plain'
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
