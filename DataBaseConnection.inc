<?php

class DataBaseConnection
{

    private $dbConnection = null;
    private $host = '';
    private $databaseName = '';
    private $username = '';

    function __construct($db)
    {

	$connection = [
   'oldDb' => [
        'host' => 'localhost',
        'databaseName' => 'coupondunia',
        'username' => 'root',
        'password' => 'root',
    ],
    'newDb' => [
        'host' => 'localhost',
        'databaseName' => 'cdapi',
        'username' => 'root',
        'password' => 'root',
    ],
];

        $this->host = $connection[$db]['host'];
        $this->databaseName = $connection[$db]['databaseName'];
        $this->username = $connection[$db]['username'];
        $this->dbConnection = new mysqli($connection[$db]['host'], $connection[$db]['username'], $connection[$db]['password'], $connection[$db]['databaseName']);
        $this->dbConnection->set_charset('utf8');
        if ($this->dbConnection->connect_error) {
            die('Connect Error (' . $this->dbConnection->connect_errno . ') ' . $dbConnection->connect_error);
        } else {
            echo 'Success... ' . $this->dbConnection->host_info . PHP_EOL;
        }
    }

    public function close()
    {
        $this->dbConnection->close();
    }

    public function hasColumn($table, $column)
    {
        $this->dbConnection->query("SELECT * FROM information_schema.COLUMNS WHERE
                                    TABLE_SCHEMA = '" . $this->databaseName . "' AND TABLE_NAME = '" . $table . "'
                                    AND COLUMN_NAME = '" . $column . "'");
        return $this->dbConnection->affected_rows;
    }

    public function selectQuery($query)
    {
        $result = $this->dbConnection->query($query);
        if ($result) {
            return $result;
        } else {
            echo "Error Executing Query or no data found: " . $query . "<br/>";
        }
    }

    public function setCharset($encode)
    {
        $this->dbConnection->set_charset($encode);
    }

    public function escapeString($value)
    {
        return $this->dbConnection->real_escape_string($value);
    }

    public function insertQuery($query)
    {
        $this->dbConnection->query($query);
        if (1 > $this->dbConnection->affected_rows) {
            echo 'Error Executing Query :' . $query . PHP_EOL;
            echo $this->dbConnection->error . PHP_EOL;
        }
    }

    public function updateQuery($query)
    {
        if (!$this->dbConnection->query($query)) {
            echo 'Error Executing Query :' . $query . "<br/>";
            echo $this->dbConnection->error . "<br/>";
        }
    }

    public function truncateTable($table)
    {
        $this->dbConnection->query("truncate table $table");
        if (!$this->dbConnection->affected_rows) {
            return false;
        }
        return true;
    }

    public function executeQuery($query)
    {
        $this->dbConnection->query($query);
    }

    public function alterAutoIncrementValue($table, $column)
    {
        $value = $this->selectQuery("select max($column) as max_id from $table");
        $query = "ALTER table $table AUTO_INCREMENT='" . $value->fetch_object()->max_id + 1 . "'";
        $this->dbConnection->query($query);
    }

    public function disableForeignkeyCheck()
    {
        $this->dbConnection->query("SET FOREIGN_KEY_CHECKS = 0");
    }

    public function enableForeignkeyCheck()
    {
        $this->dbConnection->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    public function getColumn($table)
    {
        return $this->dbConnection->query("desc $table");
    }
}

?>
