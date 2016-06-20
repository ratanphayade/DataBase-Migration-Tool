<?php
require_once 'DataBaseConnection.inc';
require_once 'constants.php';

class Functions {
    protected $oldConnection = null;
    protected $newConnection = null;

    public function initConnection($newTable = '')
    {
        $this->oldConnection = new DataBaseConnection('oldDb');
        $this->newConnection = new DataBaseConnection('newDb');
        $this->newConnection->disableForeignkeyCheck();
        $this->newConnection->setCharset('utf8');
        if (isset($newTable)) {
            $this->newConnection->truncateTable($newTable);
        }
    }

    public function closeConnection($tableName = '', $columnName = 'id')
    {
        if ($tableName) {
            $this->setAutoIncrementValue($tableName, $columnName);
        }
        $this->newConnection->enableForeignkeyCheck();
        $this->oldConnection->close();
        $this->newConnection->close();
    }

    public function parseString($args = [])
    {
        $this->hasRequiredParams($args, ['value']);
        $first = (isset($args['start'])) ? strpos($args['value'], $args['start']) + strlen($args['start']) : 0; 
        $last = (isset($args['end'])) ? strpos($args['value'], $args['end'], $first) : strlen($args['value']);
        return utf8_decode(substr($args['value'], $first, $last - $first));
    }

    public function parseLastOccuringString($args = [])
    {
        $this->hasRequiredParams($args, ['value']);
        $first = (isset($args['start'])) ? strrpos($args['value'], $args['start']) + strlen($args['start']) : 0; 
        $last = (isset($args['end'])) ? strrpos($args['value'], $args['end'], $first) : strlen($args['value']);
        return utf8_decode(substr($args['value'], $first, $last - $first));
    }

    public function defaultValue($args = [])
    {
        $this->hasRequiredParams($args, ['value']);
        return $args['value'];
    }

    public function isTrueOrFalse($args = [])
    {
        $this->hasRequiredParams($args, ['value']);
        return ($args['value']) ? ($args['true']?? true) : ($args['false']?? false);
    }

    public function isExist($args = [])
    {
        $this->hasRequiredParams($args, ['value']);
        return ($args['value'])? ($args['exist']?? true) : ($args['notExist']?? false);
    }

    public function existingValue($args)
    {
        return array_values(array_filter($args))[0];
    }

    public function getConstantValue($args = [])
    {
        $this->hasRequiredParams($args, ['functionName', 'value']);
        return $args['functionName']($args['value']);
    }

    public function arithmeticOperation($args = [])
    {
        $this->hasRequiredParams($args, ['value', 'offset']);
        return eval("return $args[value] $args[offset];");
    }

    public function stringReplace($args = [])
    {
        $this->hasRequiredParams($args, ['value', 'pattern', 'replace']);
        return (strpos($value, $replace)) ? $value : str_replace($pattern, $replace, $value);
    }

    public function jsonParseExistingValue($args = [])
    {
        $this->hasRequiredParams($args, ['value', 'list']);
        $decoded = json_decode($args['value'], true);
        if (!$decoded) {
            return NULL;
        }
        $data = [];
        foreach ($args['list'] as $index) {
            if (isset($decoded[$index])) {
                $data[$index] = $decoded[$index];
            }
        }
        return $data;
    }

    public function jsonParser($args = [])
    {
        $this->hasRequiredParams($args, ['value', 'required', 'change']);
        $decoded = json_decode($args['value'], true);
        if (!$decoded && !is_array($args['required'])) {
            return getLastActionOffer($args['value']);
        }
        if (is_array($args['required'])) {
            $result = [];
            $lastIndex = "";
            foreach ($args['required'] as $value) {
                if (empty($lastIndex)) {
                    $result[$value] = $decoded[$value];
                    $lastIndex = $value;
                } else {
                    $result[$value] = $decoded[$result[$lastIndex]];
                    $lastIndex = $value;
                }
            }
            return $result[$lastIndex];
        } else {
            if (!empty($args['change'])) {
                return $args['change']($decoded[$args['required']]);
            } else {
                return (isset($decoded[$args['required']])) ? $decoded[$args['required']] : NULL;
            }
        }
    }

    public function setDefaultIf($args = [])
    {
        $this->hasRequiredParams($args, ['value', 'isValue', 'default']);
        return ($args['value'] != isset($args['isValue'])) ? $args['value'] : $args['default'];
    }

    public function setDefaultValue($args = [])
    {
        return isset($args['default'])? $args['default'] : NULL;
    }

    public function setAutoIncrementValue($table, $column)
    {
        if (!$this->newConnection->hasColumn($table, $column)) {
            return;
        }
        $this->newConnection->alterAutoIncrementValue($table, $column);
    }

    public function setDefaultValueForColumn($rules, $baseQuery, $condition)
    {
        foreach ($rules as $column => $value) {
            $defaultValues[] = ' ' . $column . ' = ' . $value;
        }
        $query = $baseQuery . implode(',', $defaultValues) . $condition;
        $this->newConnection->updateQuery($query);
    }

    public function getCondition($rules, $row)
    {
        $condition = " where";
        foreach ($rules as $new => $old) {
            $condition .= ' ' . $new . '=' . "'" . $row->{$old} . "' and";
        }
        $condition .= ' 1';
        return $condition;
    }    
    
    public function deleteData($rules)
    {
        $this->initConnection();
        foreach ($rules as $table => $rule) {
            $query = "delete from " . $table . " where " . $rule['column_name'] . " not in (" . implode(',', $rule['values']) . ")";
            $this->$newConnection->executeQuery($query);
        }
        $this->closeConnection();
    }

    public function hasRequiredParams($params, $required){
        $missingFields = [];
        foreach($required as $index){
            if(!isset($params[$index])){
                $missingFields[] = $index;
            }
        }
        if(!empty($missingFields)){
            throw new \Exception(debug_backtrace()[1]['function'] .' -> Missing Fields -> '. implode(',' ,$missingFields));
        }
        return true;
    } 
}