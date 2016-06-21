<?php
    class Example extends Common{
        public function rules(){
            return [
                'id'=>'id',
                'name'=>'name',
                'description'=>'description',
                'added_on'=>'added_on',
                'updated_on'=>'updated_on',
            ];
        }
        public function migrateData(){
            parent::startMigration(self::rules(),'old_example_table', 'new_example_table');
        }
    }
?>