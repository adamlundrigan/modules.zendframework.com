<?php

namespace ZfModule\Mapper;

use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Db\Sql\Expression;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Stdlib\Hydrator\HydratorInterface;
use ZfcBase\Mapper\AbstractDbMapper;

class Module extends AbstractDbMapper implements ModuleInterface
{
    protected $tableName = 'module';

    /**
     * @param int $page
     * @param int $limit
     * @param string $query
     * @param string $orderBy
     * @param string $sort
     * @return Paginator
     */
    public function pagination($page, $limit, $query = null, $orderBy = null, $sort = 'ASC')
    {
        $select = $this->getSelect();

        if ($orderBy) {
            $select->order($orderBy . ' ' . $sort);
        }

        if (null !== $query) {
            $spec = function ($where) use ($query) {
                $where->like('name', '%' . $query . '%')->or->like('description', '%' . $query . '%');
            };
            $select->where($spec);
        }
        $resultSet = new HydratingResultSet($this->getHydrator(), $this->getEntityPrototype());

        $adapter = new DbSelect($select, $this->getSql(), $resultSet);
        $paginator = new Paginator($adapter);

        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($limit);

        return $paginator;
    }

    public function findAll($limit = null, $orderBy = null, $sort = 'ASC')
    {
        $select = $this->getSelect();

        if ($orderBy) {
            $select->order($orderBy . ' ' . $sort);
        }

        if ($limit) {
            $select->limit($limit);
        }

        $entity = $this->select($select);
        $this->getEventManager()->trigger('find', $this, ['entity' => $entity]);

        return $entity;
    }

    public function findByLike($query, $limit = null, $orderBy = null, $sort = 'ASC')
    {
        $select = $this->getSelect();

        if ($orderBy) {
            $select->order($orderBy . ' ' . $sort);
        }

        if ($limit) {
            $select->limit($limit);
        }

        $spec = function ($where) use ($query) {
            $where->like('name', '%' . $query . '%')->or->like('description', '%' . $query . '%');
        };
        $select->where($spec);

        $entity = $this->select($select);
        $this->getEventManager()->trigger('find', $this, ['entity' => $entity]);

        return $entity;
    }

    public function findByOwner($owner, $limit = null, $orderBy = null, $sort = 'ASC')
    {
        $select = $this->getSelect();
        if ($owner) {
            $select->where(['owner' => $owner]);
        }

        if ($orderBy) {
            $select->order($orderBy . ' ' . $sort);
        }

        if ($limit) {
            $select->limit($limit);
        }

        $entity = $this->select($select);
        $this->getEventManager()->trigger('find', $this, ['entity' => $entity]);

        return $entity;
    }

    public function findByName($name)
    {
        return $this->findBy('name', $name);
    }

    public function findByUrl($url)
    {
        return $this->findBy('url', $url);
    }

    public function findById($id)
    {
        return $this->findBy('module_id', $id);
    }

    public function findBy($key, $value)
    {
        $select = $this->getSelect();
        $select->where([$key => $value]);

        $entity = $this->select($select)->current();
        $this->getEventManager()->trigger('find', $this, ['entity' => $entity]);

        return $entity;
    }

    public function insert($entity, $tableName = null, HydratorInterface $hydrator = null)
    {
        $result = parent::insert($entity, $tableName, $hydrator);
        $entity->setId($result->getGeneratedValue());

        return $result;
    }

    public function update($entity, $where = null, $tableName = null, HydratorInterface $hydrator = null)
    {
        if (!$where) {
            $where = ['module_id' => $entity->getId()];
        }

        return parent::update($entity, $where, $tableName, $hydrator);
    }

    public function delete($entity, $where = null, $tableName = null)
    {
        if (!$where) {
            $where = ['module_id' => $entity->getId()];
        }

        return parent::delete($where, $tableName);
    }

    public function getTotal()
    {
        $select = $this->getSelect();
        $select->columns(['num' => new Expression('COUNT(*)')]);

        $stmt = $this->getSlaveSql()->prepareStatementForSqlObject($select);
        $row = $stmt->execute()->current();

        return $row['num'];
    }
}
