<?php
/**
 * RouteDispatcher
 *
 * Router untuk MVC yang sering digunakan Transformatika
 * Ini hanya MVC Sederhana tidak ada fitur sekompleks Laravel dll
 * Untuk menambahkan fitur lain silahkan tambahkan sendiri dependenciesnya
 *
 * LICENSE: MIT
 *
 * @category  MVC
 * @package   RouteDispatcher
 * @author    Prastowo aGung Widodo <agung@transformatika.com>
 * @copyright 2016 PT Daya Transformatika
 * @license   MIT
 * @version   GIT: $Id$
 * @link      https://github.com/transformatika/mvc.git
 */
namespace Transformatika\MVC;

use Transformatika\Config\Config;
use Zend\Diactoros\ServerRequestFactory;

/**
 * RouteDispatcher Class
 *
 * Handle route process
 *
 * @category  MVC
 * @package   RouteDispatcher
 * @author    Prastowo aGung Widodo <agung@transformatika.com>
 * @copyright 2016 PT Daya Transformatika
 * @license   MIT
 * @version   GIT: $Id$
 * @link      https://github.com/transformatika/mvc.git
 */
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

    protected $twig;

    protected $rootDir;

    protected $srcPath;

    protected $cacheDir;

    public function __construct($routes = null, $middleWare = null)
    {
        $this->rootDir = Config::getRootDir();
        $this->srcPath = $this->rootDir . DIRECTORY_SEPARATOR . Config::getConfig('srcPath');
        /**
         * Get Config from Module Config directory
         * File name must be Router.php
         */

        if (!empty(Config::getConfig('cachePath'))) {
            $this->cacheDir = Config::getConfig('cachePath');
        } else {
            $this->cacheDir = $this->rootDir . DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'cache';
        }

        if (empty($routes)) {
            $cacheFile = $this->cacheDir.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'router.php';
            if (true === Config::getConfig('cache') && file_exists($cacheFile)) {
                $routes = require_once($cacheFile);
            } else {
                $directory = new \RecursiveDirectoryIterator(
                    $this->srcPath,
                    \RecursiveDirectoryIterator::KEY_AS_FILENAME |
                    \RecursiveDirectoryIterator::CURRENT_AS_FILEINFO
                );
                $files = new \RegexIterator(
                    new \RecursiveIteratorIterator($directory),
                    '#^Router\.php$#',
                    \RegexIterator::MATCH,
                    \RegexIterator::USE_KEY
                );
                $routerConfiguration = [];
                foreach ($files as $filePath) {
                    $moduleRouterConfiguration = require_once($filePath->getPathname());
                    $routerConfiguration = array_merge($routerConfiguration, $moduleRouterConfiguration);
                }
                $routes = $routerConfiguration;
            }

            if (true === Config::getConfig('cache')) {
                if (!file_exists($cacheFile)) {
                    touch($cacheFile);
                }
                $str = "<?php\nreturn ".var_export($routes, true).";\n";
                file_put_contents($cacheFile, $str);
            }
        }

        $this->routes = $routes;
        $this->request = ServerRequestFactory::fromGlobals();
        $dir = $this->cacheDir.DIRECTORY_SEPARATOR;
        $this->middleWare = $middleWare;
        if (true === Config::getConfig('cache')) {
            $this->dispatcher = \FastRoute\cachedDispatcher(function (\FastRoute\RouteCollector $r) {
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
        } else {
            $this->dispatcher = \FastRoute\simpleDispatcher(function (\FastRoute\RouteCollector $r) {
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
            });
        }

        if (true === Config::getConfig('useTwig')) {
            $loader = new \Twig_Loader_Filesystem($this->srcPath);
            $params = array(
                'cache' => $this->cacheDir.DIRECTORY_SEPARATOR.'twig',
                'auto_reload' => !Config::getConfig('cache')
            );
            $this->twig = new \Twig_Environment($loader, $params);
        }

        return $this;
    }

    /**
     * Simple middleware
     * @param [type] $middleWare [description]
     */
    public function setMiddleWare($middleWare)
    {
        $this->middleWare = $middleWare;
        return $this;
    }

    /**
     * Ganti error page
     * @param [type] $options [description]
     */
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
        $baseUrl = Config::getConfig('basePath');
        $uri = $this->request->getUri()->getPath();
        $uri = substr($uri, strlen($baseUrl));
        $uri = empty($uri) ? '/' : $uri;

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
                        if (true === Config::getConfig('useTwig')) {
                            $viewPath = $controller->view->getViewPath();
                            $viewPath = str_replace($this->srcPath, '', $viewPath);
                            echo $this->twig->render($viewPath.'/'.$data['template'], $data);
                        } else {
                            $controller->display($data['template'], $data);
                        }
                    }
                }
                break;
        }
    }
}
