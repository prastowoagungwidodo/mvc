<?php
namespace Transformatika\MVC;

use Transformatika\MVC\View;
use Transformatika\Config\Config;

abstract class Controller
{
    const DEFAULT_TABLE = '';

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
        if (Config::getConfig('response') === 'json' || isset($_GET['_api_'])) {
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

    public function responseFilter($data, $table = '')
    {
        /* suka lupa ini object nya propel */
        if (is_object($data)) {
            $data = $data->toArray();
        }
        if ($table === '' && empty(static::DEFAULT_TABLE)) {
            return $data;
        } elseif ($table === '' && !empty(static::DEFAULT_TABLE)) {
            $table = static::DEFAULT_TABLE;
        }

        $excludeFields = Config::getConfig('excludeFields');
        if (!in_array($table, $excludeFields)) {
            return $data;
        } else {
            $tempData = $data;
            if (isset($tempData[0])) { // MULTIDIMENTIAL ARRAY
                foreach ($tempData as $k => $v) {
                    foreach ($v as $index => $value) {
                        if (is_array($value)) {
                            $tempData[$k][$index] = $this->responseFilter($value, $index);
                        } else {
                            foreach ($excludeFields[$table] as $exKey => $exVal) {
                                unset($tempData[$k][$exVal]);
                            }
                        }
                    }
                }
            } else { // SINGLE ARRAY
                foreach ($tempData as $index => $value) {
                    if (is_array($value)) {
                        $tempData[$k][$index] = $this->responseFilter($value, $index);
                    } else {
                        foreach ($excludeFields[$table] as $exKey => $exVal) {
                            unset($tempData[$k][$exVal]);
                        }
                    }
                }
            }
            return $tempData;
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
