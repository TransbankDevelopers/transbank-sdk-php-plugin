<?php

namespace Transbank\Plugin\Model;

class TbkTable
{
    public $name;
    /**
     * @var ColumnTable
    */
    public $columnId;
    /**
     * @var ColumnTable[]
    */
    public $columns = [];

    public function __construct($name, $columnId) {
        $this->name = $name;
        $this->columnId = $columnId;
    }

    public function addColumn($name, $type, $required = false, $default = null){
        $this->columns[] = new ColumnTable($name, $type, $required, $default);
    }

    public function getSqlCreateTable(){
        $arrayColumnsSql = [];
        foreach ($this->getColumns() as $col) {
            $arrayColumnsSql[] = $col->getSql();
        }
        $columnsSql = implode(', ', $arrayColumnsSql);
        return "CREATE TABLE IF NOT EXISTS `{$this->name}` (
            {$this->getColumnId()->getSql()} AUTO_INCREMENT,
            {$columnsSql},
            PRIMARY KEY ({$this->getColumnId()->getName()})
            ) ";
    }

    /**
     * @return ColumnTable
    */
    public function getColumnByName($name)
    {
        foreach ($this->getColumns() as $col) {
            if ($col->getName() === $name) {
                return $col;
            }
        }
        return null;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return ColumnTable
    */
    public function getColumnId() {
        return $this->columnId;
    }

    public function setColumnId($columnId) {
        $this->columnId = $columnId;
    }

    public function getColumns() {
        return $this->columns;
    }

    public function setColumns($columns) {
        $this->columns = $columns;
    }

}
