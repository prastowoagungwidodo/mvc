<?php
/**
 * View
 *
 * View untuk MVC yang sering digunakan Transformatika
 * Ini hanya MVC Sederhana tidak ada fitur sekompleks Laravel dll
 * Untuk menambahkan fitur lain silahkan tambahkan sendiri dependenciesnya
 *
 * LICENSE: MIT
 *
 * @category  MVC
 * @package   View
 * @author    Prastowo aGung Widodo <agung@transformatika.com>
 * @copyright 2016 PT Daya Transformatika
 * @license   MIT
 * @version   GIT: $Id$
 * @link      https://github.com/transformatika/mvc.git
 */
namespace Transformatika\MVC;

use Transformatika\Config\Config;

/**
 * View Class
 *
 * Handle view
 * Simple php templating, hanya menggunakan pure php
 * atau file extensi lain dengan menambahkan kode {{php}} untuk menjalankan
 * PHP Script. akhiri dengan {{/php}} untuk menutup kode php
 * ini hanya me-replace {{php}} menjadi <?php dan {{/php}} menjadi ?>
 *
 * @category  MVC
 * @package   RouteDispatcher
 * @author    Prastowo aGung Widodo <agung@transformatika.com>
 * @copyright 2016 PT Daya Transformatika
 * @license   MIT
 * @version   GIT: $Id$
 * @link      https://github.com/transformatika/mvc.git
 */
class View
{

    protected $viewPath;

    protected $appPath;

    protected $currentController;

    protected $config;

    public function __construct()
    {
        if ($this->config === null) {
            $this->config = Config::getConfig();
        }
    }

    /**
     * Render Template (php file aja)
     *
     * @param string $templateFile
     * @param unknown $data
     */
    public function render($templateFile = '', $data = array())
    {
        if (!empty($templateFile)) {
            $cacheDir = realpath($this->appPath . DS . '..') . DS . 'storage' . DS . 'cache' . DS . 'templates';
            $cacheFile = $cacheDir . DS . MD5($this->viewPath . str_replace('.' . $this->config['templateExtension'], '', $templateFile)) . '.php';

            if (!file_exists($cacheFile) || ($this->config['cache'] === 'false' || $this->config['cache'] === false)) {
                $templateTmp = file_get_contents($this->viewPath . DS . $templateFile);
                preg_match_all("~\{\{\s*(.*?)\s*\}\}~", $templateTmp, $block);
                foreach ($block[1] as $k => $v) {
                    if ($v === 'php') {
                        $templateTmp = str_replace('{{php}}', '<?php', $templateTmp);
                    } elseif ($v === '/php') {
                        $templateTmp = str_replace('{{/php}}', '?>', $templateTmp);
                    } else {
                        $blockTemplate = $this->includeBlock($v);
                        $templateTmp = str_replace('{{' . $v . '}}', $blockTemplate, $templateTmp);
                    }
                }
                $templateTmp .= "\n <!-- Generated at: " . date('Y-m-d H:i:s') . " -->";
                $fp = fopen($cacheFile, 'w');
                $write = fwrite($fp, $templateTmp);
                fclose($fp);
            }
            extract((array) $data);
            require_once $cacheFile;
        }
    }

    /**
     * Include Block Layout
     */
    public function includeBlock($blockName)
    {
        $blockName = str_replace('/', DS, $blockName);
        $blockContent = '';
        $blockFile = BASE_PATH . DS . 'storage' . DS . 'share' . DS . 'layout' . DS . $blockName . '.' . $this->config['templateExtension'];

        if (file_exists($blockFile)) {
            $templateTmp = file_get_contents($blockFile);
            preg_match_all("~\{\{\s*(.*?)\s*\}\}~", $templateTmp, $block);
            foreach ($block[1] as $k => $v) {
                if ($v === 'php') {
                    $templateTmp = str_replace('{{php}}', '<?php', $templateTmp);
                } elseif ($v === '/php') {
                    $templateTmp = str_replace('{{/php}}', '?>', $templateTmp);
                } else {
                    $blockTemplate = $this->includeBlock($v);
                    $templateTmp = str_replace('{{' . $v . '}}', $blockTemplate, $templateTmp);
                }
            }
            $blockContent = $templateTmp;
        } else {
            $blockContent = 'FILE NOT FOUND (' . $blockFile . ')';
        }
        return $blockContent;
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
        if (!empty($templateFile)) {
            str_replace('/', DS, $templateFile);
            if (file_exists($this->appPath . DS . $templateFile)) {
                require_once $this->appPath . DS . $templateFile;
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

    /**
     * Get the value of View Path
     *
     * @return mixed
     */
    public function getViewPath()
    {
        return $this->viewPath;
    }

    /**
     * Set the value of View Path
     *
     * @param mixed viewPath
     *
     * @return self
     */
    public function setViewPath($viewPath)
    {
        $this->viewPath = $viewPath;

        return $this;
    }

    /**
     * Get the value of App Path
     *
     * @return mixed
     */
    public function getAppPath()
    {
        return $this->appPath;
    }

    /**
     * Set the value of App Path
     *
     * @param mixed appPath
     *
     * @return self
     */
    public function setAppPath($appPath)
    {
        $this->appPath = $appPath;

        return $this;
    }

    /**
     * Get the value of Current Controller
     *
     * @return mixed
     */
    public function getCurrentController()
    {
        return $this->currentController;
    }

    /**
     * Set the value of Current Controller
     *
     * @param mixed currentController
     *
     * @return self
     */
    public function setCurrentController($currentController)
    {
        $this->currentController = $currentController;

        return $this;
    }
}
