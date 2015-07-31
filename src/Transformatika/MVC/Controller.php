<?php
namespace Transformatika\MVC;

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
        $this->view = new View();
        $className = get_class($this);
        $explodeNamespace = explode('\\', $className);
        $controllerName = $explodeNamespace[1];
        $this->view->setCurrentController($controllerName);
        $this->view->setAppPath(BASE_PATH . DS . 'app');
        $this->view->setViewPath(BASE_PATH . DS . 'app' . DS . $explodeNamespace[0] . DS . $controllerName . DS . 'View');
    }

    /**
     * Display template file
     *
     * @param string $viewFile
     * @param unknown $data
     */
    public function display($viewFile = '', $data = array())
    {
        $this->view->display($viewFile, $data);
    }
}
