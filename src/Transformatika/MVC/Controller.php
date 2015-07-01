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
        $this->view = new View();
    }
    
    /**
     * Display view file
     * 
     * @param string $viewFile
     * @param unknown $data
     */
    public function display($viewFile='',$data=array())
    {
        $this->view->display($viewFile,$data);
    }
}
