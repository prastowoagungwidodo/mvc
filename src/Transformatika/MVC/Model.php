<?php
namespace Transformatika\MVC;

use Transformatika\Config\Config;
use Transformatika\Database\Database;

abstract class Model
{

    protected $limit;

    protected $page;

    protected $totalPage;

    protected $totalData;

    protected $condition;
    
    protected $keyword;
    
    protected $searchIn;
    
    protected $relationship;
    
    protected $table;

    public function __construct()
    {
        if ($this->modelPath === null) {
            $config = new Config();
            $this->limit = $config->getConfig('application/displayLimit');
            
            $thisClass = get_class($this);
            $tableConf = $config->readConfigFile('conf.d/table/'.$thisClass.'.yml');
            
        }
    }

    public function read()
    {}

    public function getTotalData()
    {
        
    }

    /**
     * Get Limit
     * 
     * @return integer
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Set Limit
     *
     * @param integer $limit            
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Get Current Page
     *
     * @return integer
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Set Page
     *
     * @param integer $page            
     */
    public function setPage($page)
    {
        $this->page = $page;
        return $this;
    }

    /**
     * Get SQL Condition
     *
     * @return string
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * Set Condition
     *
     * @param string $condition            
     */
    public function setCondition($condition)
    {
        $this->condition = $condition;
        return $this;
    }

    /**
     * Get Keyword
     *
     * @return string
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * Get current Keyword
     *
     * @param string $keyword            
     */
    public function setKeyword($keyword)
    {
        $this->keyword = $keyword;
        return $this;
    }
    
    /**
     * Get searchin fields
     */
    public function getSearchIn()
    {
        return $this->searchIn;
    }
    
    /**
     * Set Search in
     * @param string $searchIn
     * @return \Transformatika\MVC\Model
     */
    public function setSearchIn($searchIn)
    {
        $this->searchIn = $searchIn;
        return $this;
    }
 
 
 
}