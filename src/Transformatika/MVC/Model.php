<?php
/**
 * Model
 *
 * Jika dibutuhkan silahkan diextends
 *
 * LICENSE: MIT
 *
 * @category  MVC
 * @package   MVC
 * @author    Prastowo aGung Widodo <agung@transformatika.com>
 * @copyright 2016 PT Daya Transformatika
 * @license   MIT
 * @version   GIT: $Id$
 * @link      https://github.com/transformatika/mvc.git
 */
namespace Transformatika\MVC;

/**
 * Model Class
 *
 * Model Class. implementasi dari ModelInterface
 *
 * @category  MVC
 * @package   MVC
 * @author    Prastowo aGung Widodo <agung@transformatika.com>
 * @copyright 2016 PT Daya Transformatika
 * @license   MIT
 * @version   GIT: $Id$
 * @link      https://github.com/transformatika/mvc.git
 */
class Model implements ModelInterface
{
    protected $limit;
    protected $page;

    public function setLimit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function setPage($page)
    {
        $this->page = (int) $page;
        return $this;
    }

    public function getPage()
    {
        return $this->page;
    }

    public function getData($options = [])
    {
    }
    public function getDataById($id = '')
    {
    }
    public function insertData($data = [])
    {
    }
    public function updateData($id = null, $data = [])
    {
    }
    public function deleteData($id = null)
    {
    }
}
