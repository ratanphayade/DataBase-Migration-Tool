<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set("memory_limit", "-1");

require_once 'Init.php';
require_once 'Common.php';
require_once 'KeyGenerator.php';

$options = getopt('o:');

$tableList = Init::getTableList();
$i = 0;
try {
    foreach ($tableList as $table) {
        require_once getcwd() . '/tables/' . $table . '.php';
        $c = new $table();
        echo "\n$table Migration Started...." . PHP_EOL;
        try{
            $c->run($options);
            $i++;
            echo "$table migration Complete...." . PHP_EOL;
        } catch (\Exception $e){
            echo $table. ' Migration Failed. Reason : '. $e->getMessage();
        }                
    }
    echo "\n\n $i Tables migrated Successfully" . PHP_EOL;
} catch (Exception $e) {
    echo $e->getMessage();
}

?>