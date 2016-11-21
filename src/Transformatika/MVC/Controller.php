<?php
/**
 * Controller
 *
 * Controller untuk MVC yang sering digunakan Transformatika
 * Ini hanya MVC Sederhana tidak ada fitur sekompleks Laravel dll
 * Untuk menambahkan fitur lain silahkan tambahkan sendiri dependenciesnya
 *
 * LICENSE: MIT
 *
 * @category  MVC
 * @package   Controller
 * @author    Prastowo aGung Widodo <agung@transformatika.com>
 * @copyright 2016 PT Daya Transformatika
 * @license   MIT
 * @version   GIT: $Id$
 * @link      https://github.com/transformatika/mvc.git
 */
namespace Transformatika\MVC;

use Transformatika\MVC\View;
use Transformatika\Config\Config;
use Zend\Diactoros\ServerRequest;

/**
 * Controller Class
 *
 * Untuk memenuhi PSR-7 maka digunakan zend diactoros response
 *
 * @category  MVC
 * @package   Controller
 * @author    Prastowo aGung Widodo <agung@transformatika.com>
 * @copyright 2016 PT Daya Transformatika
 * @license   MIT
 * @version   GIT: $Id$
 * @link      https://github.com/transformatika/mvc.git
 */
abstract class Controller
{
    const DEFAULT_TABLE = '';

    public $view;

    protected $request;

    public function __invoke($method, $request)
    {
        $this->request = $request;
        return $this->{$method}();
    }

    public function __construct()
    {
        if (Config::getConfig('srcPath') === 'src') { // New MVC Version
            $dir = 'src';
        } else {
            $dir = 'app';
        }
        $this->view = new View();
        $this->view->setAppPath(BASE_PATH . DS . $dir);
        $this->view->setViewPath($this->getViewDir());
    }

    /**
     * Set server request
     * ini biasanya digunakan untuk unit testing
     * 
     * @param ServerRequest $request [description]
     */
    public function setServerRequest(ServerRequest $request)
    {
        $this->request = $request;
        return $this;
    }

    protected function getViewDir()
    {
        $controllerDir = dirname((new \ReflectionClass(static::class))->getFileName());
        return realpath($controllerDir.DS.'..').DS.'View';
    }

    public function getViewPath()
    {
        return $this->view->getViewPath();
    }

    /**
     * Too many bugs!!!
     * may deleted soon!!
     *
     * @param  [type] $data  [description]
     * @param  string $table [description]
     * @return [type]        [description]
     */
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
