# DataBase-Migration-Tool

This tool is writen in php to help migrate the data from old database structure to new database structure. It understands the rule and migrated the data accordingly. Its also easy to extend to custom methods for the data.

Installation and Configuration : 

 * Clone the repo to local.
 * Edit DataBaseConnection.inc for old and new database(MySql) connections.
 * Write the migration rule for every table in table folder in separate files (Each file name should match the class name).
 * Add the class name in init.php (Only the class added in init.php will be migrate).
 * Inorder to run migration new base structure should be present.


Execution :
	
    # php migrate.php [options]

Options :
	
    -o : will specify the whether to use bulk insertion or individual query for the insertion.
	    example: php migrate.php -o bulk
        	     php migrate.php -o single (default)


Example Migration Rules : 

```php
<?php
    class Example extends Common{
        public function rules(){
            return [
                'id'=>'id',
                'name'=>'name',
            ];
        }
        public function migrateData(){
            parent::startMigration(self::rules(),'old_example_table', 'new_example_table');
        }
    }
?>
```

	startMigration($rules, $reference, $newTable, $isQuery = false, $handleConnection = true, $type = 'single', $fieldName = '')
    
    * $rules - Table Migration Rules
    * $reference - Old table name OR sql query
    * $newTable - New Table Name
    * $isQuery - false -> if $reference is table name. 
     			 true  -> if $reference is a query
    * $handleConnection - true  -> will initialize connection to both the dbs. 
     					(with $isQuery false will truncate the new table is theres any data).
                        false -> in case you have to initialize it manually.
    * $type - multivalued -> will define whether table have multivalued attribute.
    		  single -> single valued attribute.
    * $fieldName -> in case $type is multivalued specify the attribute name which has multiple values.


### Defining Rules:

```php
    public function rules()
    {
        return [
            'user_id' => [
                'functionName' => 'getUserId',
                'args' => [
                    'value' => 'userid',
                ]
            ],
            'code' => 'code',
            'status' => [
                'functionName' => 'getConstantValue',
                'args' => [
                    'value' => 'IsApproved',
                    'functionName' => 'getIsApprovedStatus',
                ],
            ],
        ];
    }

    public static function migrateData()
    {
        parent::startMigration(self::rules(), 'Example1', 'Example2');
    }

    public function getUserId($args = [])
    {
        if ($args['userid'] === 0) {
            return NULL;
        }
        return $args['userid'];
    }
}
```

Constant methods should be defined in constants.php. Constants will be used when you want to associate a value to another value. like change of enum, values

Basic functions are defined in Funcations.php. In case user need some custom methods, it can be writen in the rules file which accepts a array as parameters.