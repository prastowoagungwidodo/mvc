<?php
namespace Transformatika\MVC;

use Transformatika\Config\Config;
use Transformatika\MVC\View;

abstract class Controller
{

    protected $modelPath;

    public $version = 0.1;

    public $description = 'No description';

    public $author = 'Unknown';

    public $buildConfiguration = array();

    public $dependencies = array();

    public $releaseDate = '2015-01-01';

    public $view;

    public function __construct()
    {
        $config = new Config();
        $this->view = new View();
        $className = get_class($this);
        $explodeNamespace = explode('\\', $className);
        $controllerName = $explodeNamespace[0];
        $this->view->setCurrentController($controllerName);
        $this->view->setAppPath($config->getRootDir() . DIRECTORY_SEPARATOR . 'app');
        $this->view->setViewPath($config->getRootDir() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'packages' . DIRECTORY_SEPARATOR . $controllerName . DIRECTORY_SEPARATOR . 'View');
    }

    /**
     * Display view file
     *
     * @param string $viewFile
     * @param unknown $data
     */
    public function display($viewFile = '', $data = array())
    {
        $this->view->display($viewFile, $data);
    }
}
