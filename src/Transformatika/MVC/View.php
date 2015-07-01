<?php
namespace Transformatika\MVC;

use Transformatika\Config\Config;

class View
{

    protected $viewPath;

    protected $appPath;

    protected $currentController;

    public function __construct()
    {
        if ($this->viewPath === null) {
            $config = new Config();
            
            if (isset($_SERVER['QUERY_STRING']) && ! empty($_SERVER['QUERY_STRING'])) {
                $parts = trim($_SERVER['QUERY_STRING'], '/');
                $parts = explode('/', $parts);
                $this->currentController = array_shift($parts);
            } else {
                $defaultRoute = $config->getConfig('application/defaultRoute');
                $arrayRoute = explode('/', $defaultRoute);
                $this->currentController = $arrayRoute[0];
            }
            
            $controllerName = ucfirst($this->currentController);
            $appDir = str_replace('/', DIRECTORY_SEPARATOR, $config->getRootDir() . DIRECTORY_SEPARATOR . 'app'. DIRECTORY_SEPARATOR .'packages');
            $this->viewPath = $appDir . DIRECTORY_SEPARATOR . $controllerName . DIRECTORY_SEPARATOR . 'View';
            $this->appPath = $appDir;
        }
    }

    /**
     * Render Template
     * 
     * @param string $templateFile            
     * @param unknown $data            
     */
    public function render($templateFile = '', $data = array())
    {
        if (! empty($templateFile)) {
            if (file_exists($this->viewPath . DIRECTORY_SEPARATOR . $templateFile)) {
                extract($data);
                require_once $this->viewPath . DIRECTORY_SEPARATOR . $templateFile;
            }
        }
    }

    /**
     * Alias of Render Template
     * 
     * @param string $templateFile            
     * @param unknown $data            
     */
    public function display($templateFile = '', $data = array())
    {
        $this->render($templateFile, $data);
    }

    /**
     * Include other file
     * 
     * @param string $templateFile            
     */
    public function includeFile($templateFile = '')
    {
        if (! empty($templateFile)) {
            str_replace('/', DIRECTORY_SEPARATOR, $templateFile);
            if (file_exists($this->appPath . DIRECTORY_SEPARATOR . $templateFile)) {
                require_once $this->appPath . DIRECTORY_SEPARATOR . $templateFile;
            }
        }
    }

    /**
     * Alias of includeFile function
     * 
     * @param string $file            
     */
    public function inc($file = '')
    {
        $this->includeFile($file);
    }
}