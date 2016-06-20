<?php 

require_once 'Functions.php';

class Common extends Functions
{  
    private $isBulk = false;

    protected function setIsBulk($isBulk){
        $this->isBulk = $isBulk;
    }

    public function run($options = [])
    {
        $this->checkOptions($options);        
        $this->migrateData();
    }

    public function checkOptions($options = []){
        if(isset($options['o']) && $options['o'] === 'bulk'){
            $this->setIsBulk(true);
        } else {
            $this->setIsBulk(false);
        }
    }

    public function getRows($rules, &$newColumn, &$oldColumn, &$totalFields)
    {
        foreach ($rules as $new => $old) {
            $newColumn[] = '`' . $new . '`';
            $oldColumn[] = $old;
        }
        $oldColumn = array_filter($oldColumn);
        $totalFields = count($oldColumn);
    }

    public function insertSingleRecord($oldRecords, $totalFields, $oldColumn, $baseQuery, $fieldName, $rules)
    {
        while ($row = $oldRecords->fetch_object()) {
            for ($i = 0; $i < $totalFields; $i++) {
                $oldValues[] = $this->getValue($oldColumn, $row, $i, $rules);
            }
            echo $query = $baseQuery . "(" . implode(',', $oldValues) . ")";
            $this->newConnection->insertQuery($query);
            unset($oldValues);
        }
    }

    public function insertBulkRecords($oldRecords, $totalFields, $oldColumn, $baseQuery, $fieldName, $rules){
        $values = [];
        while ($row = $oldRecords->fetch_object()) {
            for ($i = 0; $i < $totalFields; $i++) {
                $oldValues[] = $this->getValue($oldColumn, $row, $i, $rules);
            }
            $values[] = "(" . implode(',', $oldValues) . ")";            
            unset($oldValues);
        }
        $query = $baseQuery . implode(',', $values);
        $this->newConnection->insertQuery($query);
    }

    public function insertMultiValuedData($oldRecords, $totalFields, $oldColumn, $baseQuery, $fieldName)
    {
        while ($row = $oldRecords->fetch_object()) {
            for ($i = 0; $i < $totalFields; $i++) {
                if ($fieldName != $oldColumn[$i]) {
                    $oldValues[] = '"' . $row->{$oldColumn[$i]} . '"';
                } else {
                    $values = array_filter(explode(",", $row->{$oldColumn[$i]}));
                    if (!empty($values)) {
                        foreach ($values as $value) {
                            $query = $baseQuery . ' (' . implode(',', $oldValues) . ',"' . $value . '")';
                            $this->newConnection->insertQuery($query);
                        }
                    }
                }
            }
            unset($oldValues);
        }
    }

    public function insertBulkMultiValuedData($oldRecords, $totalFields, $oldColumn, $baseQuery, $fieldName)
    {
        $insertData = [];
        while ($row = $oldRecords->fetch_object()) {
            for ($i = 0; $i < $totalFields; $i++) {
                if ($fieldName != $oldColumn[$i]) {
                    $oldValues[] = '"' . $row->{$oldColumn[$i]} . '"';
                } else {
                    $values = array_filter(explode(",", $row->{$oldColumn[$i]}));
                    if (!empty($values)) {
                        foreach ($values as $value) {
                            $insertData[] = ' (' . implode(',', $oldValues) . ',"' . $value . '")';                            
                        }
                    }
                }
            }
            unset($oldValues);
        }
        $query = $baseQuery . implode(',', $insertData);
        $this->newConnection->insertQuery($query);
    }

    public function getValue($oldColumn, $row, $i, $rules)
    {
        if (is_array($oldColumn[$i])) {
            if (isset($oldColumn[$i]['functionName'])) {
                if($oldColumn[$i]['args']['value']){
                    $oldColumn[$i]['args']['value'] = $row->{$oldColumn[$i]['args']['value']};
                }
                $value = $this->{$oldColumn[$i]['functionName']}(
                        $oldColumn[$i]['args']
                );
            } else if (isset($oldColumn[$i]['existing_value'])) {
                foreach ($oldColumn[$i]['existing_value']['column'] as $cols) {
                    $args[$cols] = $row->{$cols};
                }
                $value = $this->{$oldColumn[$i]['existing_value']['functionName']}(
                        array_merge(['values' => $args], ['args' => $oldColumn[$i]['existing_value']['args']])
                );
            } else {
                $value = (isset($rules[$oldColumn[$i]['new_column_name']]['values'][$row->{$oldColumn[$i]['column_name']}])) ?
                    $rules[$oldColumn[$i]['new_column_name']]['values'][$row->{$oldColumn[$i]['column_name']}] : NULL;
            }
            $value = $this->newConnection->escapeString($value);
            return ($value != NULL) ? '"' . $value . '"' : 'NULL';
        } else {
            $value = $this->newConnection->escapeString($row->{$oldColumn[$i]});
            return ($row->{$oldColumn[$i]} != NULL) ? '"' . $value . '"' : 'NULL';
        }
    }

    public function updateRows($oldRecords, $totalFields, $oldColumn, $newColumn, $baseQuery, $rules)
    {
        while ($row = $oldRecords->fetch_object()) {
            $setValues = [];
            for ($i = 0; $i < $totalFields; $i++) {
                $value = $this->getValue($oldColumn, $row, $i, $rules['column']);
                if (!$value) {
                    continue;
                }
                $setValues[] = ' ' . $newColumn[$i] . ' = ' . $value;
            }
            if (!$setValues) {
                continue;
            }
            $this->setDefaultValueForColumn($rules['defaulValue'], $baseQuery, $this->getCondition($rules['condition'], $row));
            $query = $baseQuery . implode(',', $setValues) . $this->getCondition($rules['condition'], $row);
            $this->newConnection->updateQuery($query);
            unset($setValues);
        }
    }

    public function startUpdation($rules, $reference, $newTable, $isQuery = 0)
    {
        $this->initConnection();
        $this->getRows($rules['column'], $newColumn, $oldColumn, $totalFields);
        $query = ($isQuery == 1) ? $reference : "select * from $reference";
        $oldRecords = $this->oldConnection->selectQuery($query);
        if (!$oldRecords) {
            return;
        }
        $baseQuery = "update $newTable set ";
        $this->updateRows($oldRecords, $totalFields, $oldColumn, $newColumn, $baseQuery, $rules);
        $this->closeConnection();
    }

    public function startMigration($rules, $reference, $newTable, $isQuery = false, $handleConnection = true, $type = 'single', $delimiter = ',', $fieldName = '')
    {
        if ($handleConnection) {
            $this->initConnection($newTable);
        }
        $this->getRows($rules, $newColumn, $oldColumn, $totalFields);
        $query = ($isQuery) ? $reference : "select * from $reference";
        $oldRecords = $this->oldConnection->selectQuery($query);
        if (!$oldRecords) {
            return;
        }
        $baseQuery = "insert into $newTable (" . implode(',', $newColumn) . ") values ";
        if ($type === 'multivalued') {
            if($this->isBulk){
                $this->insertBulkMultiValuedData($oldRecords, $totalFields, $oldColumn, $baseQuery, $fieldName);
            } else {
                $this->insertMultiValuedData($oldRecords, $totalFields, $oldColumn, $baseQuery, $fieldName);
            }            
        } else {
            if($this->isBulk){
                $this->insertBulkRecords($oldRecords, $totalFields, $oldColumn, $baseQuery, $fieldName, $rules);
            } else {
                $this->insertSingleRecord($oldRecords, $totalFields, $oldColumn, $baseQuery, $fieldName, $rules);                
            }
        }
        if ($handleConnection) {
            $this->closeConnection($newTable);
        }
    }
}

?>