<?php
require_once 'InitUpdate.php';
require_once 'Common.php';
$tableList = Init::getTableList();
$i = 0;
foreach ($tableList as $table) {
    require_once getcwd() . '/update/' . $table . '.php';
    $c = new $table();
    echo "\n$table Migration Started....\n";
    $c->run();
    $i++;
    echo "$table migration Complete....\n";
}
echo "\n\n $i Tables Updated Successfully";

?>