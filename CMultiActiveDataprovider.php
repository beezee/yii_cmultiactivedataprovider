<?php

class CMultiActiveModelLookup
{
    private $_class;
    private $_pk;
    private $_sortColumn;
    private $_where;
    
    public function __construct($options = false)
    {
        if (is_array($options))
            foreach($options as $k => $v)
                $this->{'_'.$k} = $v;
    }
    
    public function isValid()
    {
        return $this->_class and $this->_sortColumn and $this->_pk;
    }
    
    public function query()
    {
        $class = $this->_class;
        $table = $class::Model()->tableName();
        $query = "SELECT {$this->_pk} AS PK, '{$this->_class}' AS TYPE,
            {$this->_sortColumn} AS SORT FROM $table";
        if ($this->_where) $query .= " WHERE {$this->_where}";
        return $query;
    }
}

class CMultiActiveDataProvider extends CDataProvider
{
    private $_modelLookupTable;
    private $_sortOrder;
    
    public function __construct($modelLookupTable, $config=array(), $sortOrder='DESC')
    {
        $this->_modelLookupTable = $modelLookupTable;
        foreach($config as $k => $v)
            $this->$k = $v;
        $this->_sortOrder = $sortOrder;
    }
    
    private function pkQueries()
    {
        $r = array();
        foreach($this->_modelLookupTable as $modelLookup)
            if ($ml = new CMultiActiveModelLookup($modelLookup) and $ml->isValid())
                $r[] = $ml->query();
        return $r;
    }
    
    private function paginationClause()
    {
        return (($pagination=$this->getPagination())!==false)
            ? ' LIMIT '.$pagination->getOffset().', '.$pagination->getLimit()
            : '';
    }
    
    private function updatePagination()
    {
        if(($pagination=$this->getPagination())===false) return;
        $pagination->setItemCount($this->getTotalItemCount());
    }
    
    private function indexedLookupTable()
    {
        $r = array();
        foreach($this->_modelLookupTable as $modelLookup)
            $r[$modelLookup['class']] = $modelLookup;
        return $r;
    }
    
    private function collectAndSortModels(array $allPks, array $pksByModel)
    {
        $iLookup = $this->indexedLookupTable();
        $modelsByType = array();
        foreach($pksByModel as $model => $pks)
            foreach($model::Model()->findAllByPk($pks) as $fm)
                $modelsByPk[$model][$fm->{$iLookup[$model]['pk']}] = $fm;
        $models = array();
        foreach($allPks as $pk)
            $models[] = $modelsByPk[$pk['TYPE']][$pk['PK']];
        return $models;
    }
    
    protected function fetchData()
    {
        $pkQueries = $this->pkQueries();
        if (empty($pkQueries)) return array();
        $this->updatePagination();
        $query = '('.join(') UNION (', $pkQueries).') ORDER BY SORT '.$this->_sortOrder.$this->paginationClause();
        $pks = Yii::app()->db->createCommand($query)->queryAll();
        $pksByModel = array();
        foreach($pks as $pk)
            $pksByModel[$pk['TYPE']][] = $pk['PK'];
        return $this->collectAndSortModels($pks, $pksByModel);
    }
    
    protected function fetchKeys()
    {
        return array_keys($this->getData());
    }
    
    protected function calculateTotalItemCount()
    {
        $query = '('.join(') UNION (', $this->pkQueries()).') ORDER BY SORT DESC';
        $pks = Yii::app()->db->createCommand($query)->queryAll();
        return count($pks);
    }
}