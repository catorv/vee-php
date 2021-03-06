<?php
/**
 * VEE-PHP - a lightweight, simple, flexible, fast PHP MVC framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to catorwei@gmail.com so we can send you a copy immediately.
 *
 * @package vee-php
 * @copyright Copyright (c) 2005-2079 Cator Vee
 * @license http://www.opensource.org/licenses/bsd-license.php
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */

/**
 * 数据库实体类操作辅助器
 * @package vee-php\helpers
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class DbEntityHelper {
    /**
     * 删除实体类对象
     * @param string $class 实体类名
     * @param string $ids 将被删除的实体ID，多个ID之间用逗号分隔
     * @param boolean $delete 是否物理删除该实体类对象，为false时只将状态改为-1
     * @return boolean 返回是否删除成功
     */
    static public function remove($class, $ids, $delete = false) {
        $ids = explode(',', $ids);
        $methodExists = method_exists($class, 'remove');
        V::db()->beginTransaction();
        foreach ($ids as $id) {
            $entity = new $class($id);
            if (!$entity->isNull()) {
                if ($methodExists) {
                    if (!$entity->remove()) {
                        V::db()->rollBack();
                        return false;
                    }
                } else if (!$delete) {
                    $entity->status = -1;
                    if (!$entity->save()) {
                        V::db()->rollBack();
                        return false;
                    }
                } else if(!$entity->delete()) {
                    V::db()->rollBack();
                    return false;
                }
            }
        }
        v::db()->commit();
        return true;
    }

    /**
     * 保存实体对象
     * @param string $class 实体类名
     * @param boolean $isUpdate 是否是更新，任何逻辑值ture的值都是更新，否则为新增
     * @param string $uniqueField 判断唯一值的字段名
     * @return boolean 返回是否保存成功
     */
    static public function save($class, $isUpdate, $uniqueField = '') {
        if ($isUpdate) { // update
            return DbEntityHelper::update($class, $_POST, $uniqueField);
        } else { // new
            return DbEntityHelper::add($class, $_POST, $uniqueField);
        }
    }

    /**
     * 更新实体对象
     * @param string $class 实体类名
     * @param array $fields 字段列表
     * @param string $uniqueField 判断唯一值的字段名
     * @param string $idField 主键字段名
     * @return boolean 返回是否更新成功
     */
    static public function update($class, $fields,
                                  $uniqueField = '', $idField = 'id') {
        $entity = new $class(intval($fields[$idField]));
        if (!$entity->isNull()) {
            if (method_exists($entity, 'update')) {
                if ($entity->update($fields)) {
                    ExtjsHelper::$data = $entity;
                    return true;
                }
            } else if ($uniqueField) {
                $c = $entity->dbQuery(true)
                            ->addWhere($uniqueField, $fields[$uniqueField])
                            ->addWhere($idField, $entity->$idField, Db::OP_NE)
                            ->addWhere('status', 0, Db::OP_GE)
                            ->getRecordCount();
                if (0 == $c) {
                    $entity->importProperties($fields);
                    if (property_exists($entity, 'lasttime')) {
                        $entity->lasttime = $_SERVER['REQUEST_TIME'];
                    }
                    if ($entity->save()) {
                        ExtjsHelper::$data = $entity;
                        return true;
                    }
                } else {
                    ExtjsHelper::addError($uniqueField, '“' . $fields[$uniqueField]
                                                        . '”已经被使用，该字段值不允许出现重复的值。');
                }
            } else {
                $entity->importProperties($fields);
                if (property_exists($entity, 'lasttime')) {
                    $entity->lasttime = $_SERVER['REQUEST_TIME'];
                }
                if ($entity->save()) {
                    ExtjsHelper::$data = $entity;
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 新增实体对象
     * @param string $class 实体类名
     * @param array $fields 字段列表
     * @param string $uniqueField 判断唯一值的字段名
     * @return boolean 返回是否新增成功
     */
    static public function add($class, $fields, $uniqueField = '') {
        $entity = new $class();
        if (method_exists($entity, 'add')) {
            if ($entity->add($fields)) {
                ExtjsHelper::$data = $entity;
                return true;
            }
        } else if ($uniqueField) {
            $count = $entity->dbQuery(true)
                            ->addWhere($uniqueField, $fields[$uniqueField])
                            ->addWhere('status', 0, Db::OP_GE)
                            ->getRecordCount();
            if (0 == $count) {
                $entity->importProperties($fields);
                if (property_exists($entity, 'ordering')) {
                    $query = $entity->dbQuery(true);
                    $query->addField('ordering', 'mo', Db::OP_MAX);
                    if (property_exists($entity, 'pid')) {
                        $query->addWhere('pid', $fields['pid']);
                    }
                    $entity->ordering = $query->getValue() + 1;
                }
                if (property_exists($entity, 'creatime')) {
                    $entity->creatime = $_SERVER['REQUEST_TIME'];
                }
                if (property_exists($entity, 'lasttime')) {
                    $entity->lasttime = $_SERVER['REQUEST_TIME'];
                }
                if ($entity->save()) {
                    ExtjsHelper::$data = $entity;
                    return true;
                }
            } else {
                ExtjsHelper::addError($uniqueField, '“' . $fields[$uniqueField]
                                                  . '”已经被使用，该字段值不允许出现重复的值。');
            }
        } else {
            $entity->importProperties($fields);
            if (property_exists($entity, 'creatime')) {
                $entity->creatime = $_SERVER['REQUEST_TIME'];
            }
            if (property_exists($entity, 'lasttime')) {
                $entity->lasttime = $_SERVER['REQUEST_TIME'];
            }
            if ($entity->save()) {
                ExtjsHelper::$data = $entity;
                return true;
            }
        }
        return false;
    }

    /**
     * 实体对象列表预处理
     * @param string $class 实体类名
     * @return DbEntity
     */
    static public function listPrepare($class) {
        $entity = new $class();
        $query  = $entity->dbQuery(true);
        return $entity;
    }

    /**
     * 实体对象列表数据处理
     * @param DbEntity $query 经过listPrepare处理过的实体对象
     */
    static public function listData($entity) {
        $start  = intval(v::get('start'));
        $limit  = intval(v::get('limit'));
        $sort   = v::get('sort');
        $dir    = v::get('dir');

        $query = $entity->dbQuery();
        if ($limit > 0) {
            ExtjsHelper::$total = $query->getRecordCount();
            if (ExtjsHelper::$total == 0) {
                ExtjsHelper::$data = array();
                return;
            }
        }
        if ($sort) {
            $query->addOrderBy($sort, $dir);
        }
//        ExtjsHelper::$data = $entity->getList($query, $start, $limit);
        ExtjsHelper::$data = $query->getList(null, $start, $limit);
    }
}