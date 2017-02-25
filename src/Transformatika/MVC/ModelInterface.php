<?php
/**
 * Model Interface
 *
 * Untuk menyeragamkan model saja
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

interface ModelInterface
{
    public function getData();
    public function getDataById();
    public function insertData();
    public function updateData();
    public function deleteData();
    public function setLimit($limit);
    public function getLimit();
    public function setPage($page);
    public function getPage();
}
