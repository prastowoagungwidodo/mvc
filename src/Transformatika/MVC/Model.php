<?php
/**
 * Model
 *
 * This file is useless and should be deleted!
 */
namespace Transformatika\MVC;

use Transformatika\Config\Config;
use Transformatika\Database\Database;

class Model
{

    protected $limit;

    protected $page = 1;

    protected $totalPage;

    protected $totalData;

    protected $condition;

    protected $keyword;

    protected $searchIn;

    protected $relationship;

    protected $table;

    private $db;

    private $config;

    protected $primaryKey;

    protected $orderBy;

    protected $orderType;

    protected $filterByColumn;

    protected $thisClass;

    /**
     * Constructor
     */
    public function __construct($data = array())
    {
        if ($this->limit === null) {
            $config = new Config();
            $this->limit = $config->getConfig('application/display_limit');

            $thisClass = get_class($this);
            $thisClass = str_replace("App\\Model\\", "", $thisClass);
            $this->config = $config->readConfigFile('conf.d/table/' . $thisClass . '.yml');
            $this->table = $this->config['name'];
            $this->thisClass = $thisClass;
            $primaryKeys = array();

            foreach ($this->config['columns'] as $k => $v) {
                if (isset($v['primary_key']) && $v['primary_key'] === true) {
                    $primaryKeys[] = $k;
                }
            }
            if (count($primaryKeys) > 1) {
                $this->primaryKey = $primaryKeys;
            } else {
                $this->primaryKey = $primaryKeys[0];
            }
        }
        $this->db = new Database();

        foreach ($data as $obj => $value) {
            if (array_key_exists($obj, $this->config['columns'])) {
                $objName = 'set' . $this->underscoreToCamelCase($obj, true);
                $this->{$objName}($value);
            }
        }
    }

    /**
     * Underscrore to Camel Case
     *
     * @param string $string
     * @param boolean $firstCharCaps
     * @return string
     */
    private function underscoreToCamelCase($string, $firstCharCaps = false)
    {
        $arrayString = explode('_', $string);
        $camelCase = '';
        foreach ($arrayString as $k => $v) {
            if ($k === 0 && $firstCharCaps === false) {
                $camelCase .= $v;
            } else {
                $camelCase .= ucfirst($v);
            }
        }
        return $camelCase;
    }

    public function read()
    {

    }

    public function insert()
    {
        $data = array();
        foreach ($this->config['columns'] as $k => $v) {
            $propertyName = 'get' . $this->underscoreToCamelCase($k, true);
            if (!empty($this->{$propertyName}())) {
                $data[$k] = $this->{$propertyName}();
            }
        }
        return $this->db->insert($this->table, $data);
    }
    public function findByPk($id)
    {
        $addSQL = '';
        if (is_array($this->primaryKey)) {
            foreach ($this->primaryKey as $k => $v) {
                if (empty($addSQL)) {
                    $addSQL .= 'WHERE ' . $v . ' = \'' . $id . '\' ';
                } else {
                    $addSQL .= ' AND ' . $v . ' = \'' . $id . '\' ';
                }
            }
        } else {
            $addSQL = 'WHERE ' . $this->primaryKey . ' = \'' . $id . '\'';
        }

        $this->db->query('SELECT * FROM ' . $this->table . ' ' . $addSQL);
        $data = $this->db->fetch();

        $thisClass = get_class($this);
        return new $thisClass($data);
    }

    public function filterBy($column)
    {
        $this->filterByColumn[] = $column;
        return $this;
    }

    public function setOrderBy($orderBy)
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    public function setOrderType($orderType)
    {
        $this->orderType = $orderType;
        return $this;
    }

    public function find($keyword)
    {
        $addSQL = '';
        if (is_array($this->filterByColumn)) {
            foreach ($this->filterByColumn as $k => $v) {
                if (empty($addSQL)) {
                    $addSQL .= 'WHERE ' . $v . ' = \'' . $keyword . '\'';
                } else {
                    $addSQL .= 'AND ' . $v . ' = \'' . $keyword . '\'';
                }
            }

        }

        if ($this->orderBy !== null) {
            $addSQL .= ' ORDER BY ' . $this->orderBy;
            if ($this->orderType !== null) {
                $addSQL .= ' ' . $this->orderType;
            } else {
                $addSQL .= ' ASC';
            }
        }

        $this->db->query('SELECT * FROM ' . $this->table . ' ' . $addSQL);
        $data = $this->db->fetchAll();
        $datas = array();
        $thisClass = get_class($this);
        foreach ($data as $key => $val) {
            $datas[] = new $thisClass($val);
        }

        return $datas;

    }

    public function update()
    {
        $data = array();
        foreach ($this->config['columns'] as $k => $v) {
            $propertyName = 'get' . $this->underscoreToCamelCase($k, true);
            $condition = '';
            if (!empty($this->{$propertyName}())) {
                $data[$k] = $this->{$propertyName}();
                if (isset($v['primary_key']) && $v['primary_key'] === true && empty($this->condition)) {
                    if (!empty($condition)) {
                        $condition .= ' AND ';
                    }
                    $condition .= ' ' . $k . ' = \'' . $this->{$propertyName}() . '\' ';
                }
            }
            $this->condition = $condition;
        }
        if (!empty($this->condition)) {
            return $this->db->update($this->table, $data, 'WHERE ' . $this->condition);
        } else {
            return false;
        }
    }

    /**
     * Get Total Row
     *
     * @return integer
     */
    public function getTotalData()
    {
        return $this->db->counts($this->table, $this->condition);
    }

    /**
     * An alias of getTotalData
     *
     * @return integer
     */
    public function getTotalRows()
    {
        return $this->getTotalData();
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

    public function createTable()
    {
        if (!$this->db->tableExists($this->table)) {
            $index = 0;
            $sql = 'CREATE TABLE ' . $this->table . ' (';
            foreach ($this->config['columns'] as $key => $val) {
                $notnull = '';
                $default = '';
                $length = '';
                if ($val['type'] === 'string') {
                    $val['type'] = 'character varying';
                }
                if ($val['type'] === 'datetime') {
                    $val['type'] = 'timestamp without time zone';
                }
                if (isset($val['primary_key']) && $val['primary_key'] === true) {
                    $primaryKey = ', CONSTRAINT ' . $this->table . '_' . $key . ' PRIMARY KEY (' . $key . ') ';
                }
                if (isset($val['length']) && $val['length'] != 0) {
                    $length = '(' . $val['length'] . ')';
                }
                if (isset($val['not_null']) && $val['not_null'] === true) {
                    $notnull = ' NOT NULL';
                }
                if (isset($val['default']) && !empty($val['default'])) {
                    $default = ' DEFAULT \'' . $val['default'] . '\'::' . $val['type'];
                }
                if ($index > 0) {
                    $sql .= ',';
                }

                $sql .= ' ' . $key . ' ' . $val['type'] . $length . $notnull . $default;
                $index++;
            }
            $sql .= $primaryKey;
            $sql .= ')';
            $this->db->query($sql);

        } else {
            die('table already exists');
        }
    }

    public function syncTable()
    {
        $fields = $this->db->getFields($this->table);
        $sql = 'ALTER TABLE ' . $this->table . ' ';
        $primaryKey = '';
        $index = 0;
        foreach ($this->config['columns'] as $key => $val) {
            if (!in_array($key, $fields)) {
                $notnull = '';
                $default = '';
                $length = '';
                if ($val['type'] === 'string') {
                    $val['type'] = 'character varying';
                }
                if ($val['type'] === 'datetime') {
                    $val['type'] = 'timestamp without time zone';
                }

                if (isset($val['primary_key']) && $val['primary_key'] === true) {
                    $primaryKey = ', CONSTRAINT ' . $k . '_' . $key . ' PRIMARY KEY (' . $key . ') ';
                }
                if (isset($val['length']) && $val['length'] != 0) {
                    $length = '(' . $val['length'] . ')';
                }
                if (isset($val['not_null']) && $val['not_null'] === true) {
                    $notnull = ' NOT NULL';
                }
                if (isset($val['default'])) {
                    $default = ' DEFAULT \'' . $val['default'] . '\'::' . $val['type'];
                }
                if ($index > 0) {
                    $sql .= ',';
                }
                $sql .= ' ADD COLUMN ' . $key . ' ' . $val['type'] . $length . $notnull . $default;
                $index++;
            }
        }
        $sql .= $primaryKey;
        $sql .= '';
        //echo $sql;exit();
        if ($index > 0) {
            $this->db->query($sql);
        }
    }
}
