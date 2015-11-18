<?php
namespace Transformatika\MVC;

use Transformatika\MVC\View;
use Transformatika\Config\Config;

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
        if (Config::getConfig('srcPath') === 'src') { // New MVC Version
            $dir = 'src';
        } else {
            $dir = 'app';
        }
        if (Config::getConfig('response') === 'json') {
            /* Karena setiap request pasti ada parameter berikut jadi di set default dulu disini */
            $_GET['page'] = !isset($_GET['page']) ? 1 : $_GET['page'];
            $_GET['limit'] = !isset($_GET['limit']) ? Config::getConfig('displayLimit') : $_GET['limit'];
            $_GET['keyword'] = !isset($_GET['keyword']) ? null : $_GET['keyword'];
            $_GET['filter'] = !isset($_GET['filter']) ? null : $_GET['filter'];
            $_GET['order'] = !isset($_GET['order']) ? null : $_GET['order'];
            $_GET['orderType'] = !isset($_GET['orderType']) ? 'ASC' : $_GET['orderType'];
            $_GET['id'] = !isset($_GET['id']) ? null : $_GET['id'];
        } else {
            $this->view = new View();
            $className = get_class($this);
            $explodeNamespace = explode('\\', $className);
            $controllerName = $explodeNamespace[1];
            $this->view->setCurrentController($controllerName);
            $this->view->setAppPath(BASE_PATH . DS . $dir);
            $this->view->setViewPath(BASE_PATH . DS . $dir . DS . $explodeNamespace[0] . DS . $controllerName . DS . 'View');
        }
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
