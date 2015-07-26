<?php
namespace Transformatika\MVC;

use Transformatika\Config\Config;

class View
{

    protected $viewPath;

    protected $appPath;

    protected $currentController;

    protected $config;

    public function __construct()
    {
        if ($this->config === null) {
            $config = new Config();
            $this->config = $config->getConfig('application');
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
        if (!empty($templateFile)) {
            $cacheDir = realpath($this->appPath . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'templates';
            $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . MD5($this->currentController . str_replace('.html', '', $templateFile)) . '.php';

            if (!file_exists($cacheFile) || $this->config['cache'] === false) {
                $templateTmp = file_get_contents($this->viewPath . DIRECTORY_SEPARATOR . $templateFile);
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
        $blockName = str_replace('/', DIRECTORY_SEPARATOR, $blockName);
        $blockContent = '';
        if (file_exists($this->appPath . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . $blockName . '.html')) {
            $blockContent = file_get_contents($this->appPath . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . $blockName . '.html');
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
