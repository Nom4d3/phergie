<?php
/**
 * Phergie
 *
 * PHP version 5
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://phergie.org/license
 *
 * @category  Phergie
 * @package   Phergie_Plugin_Db
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Db
 */

 /**
  * @todo CREATE CLASS DESCRIPTION
  */

/**
 * @category Phergie
 * @package  Phergie_Plugin_Db
 * @author   Jared Folkins <jfolkins@gmail.com>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Db
 * @uses     Phergie_Plugin_UserInfo pear.phergie.org
 */

class Phergie_Plugin_Db extends Phergie_Plugin_Abstract
{
    const DEBUG = true;

    /**
     *  Checks to see if the root rbac user has been set in
     *  
     *
     *  @return void
     */
    public function onLoad()
    {
        $this->doesPdoExist();        
    }

    /**
     *  Validates that the Pdo Extenstion Exists.
     *
     *  @return void
     */
    private function doesPdoExist()
    {
        if (!extension_loaded('PDO') || !extension_loaded('pdo_sqlite'))
            $this->fail('PDO and pdo_sqlite extensions must be installed');
    }
    
    /**
     *  Initializes database
     *  @param string plugin name
     *  @param string database name
     *  @param string schema filename
     *  @return object
     */
    public function init($directory, $dbFile, $schemaFile)
    {
        $this->isResourceDirectory($directory);        
        $doesDbFileExist = is_readable($dbFile);

        try {
            $db = new PDO('sqlite:' . $dbFile);
        } catch (PDO_Exception $e) {
            throw new Phergie_Plugin_Exception($e->getMessage());
        }

        if(!$doesDbFileExist)
            $this->createTablesFromSchema($db, $schemaFile);
        return $db;
    }

    /**
     * fully specified file name as string used to check that the resource directory does exist
     * 
     * @param string $directory
     */
    public function isResourceDirectory($directory)
    {
        if(!is_dir($directory))
            $this->fail('The Resource directory: ' . $directory . ' does not exist');
    }

    /**
     *  fully specified file name as string used to check that the schema file does exist
     * 
     * @param string $file
     * 
     */
    public function isSchemaFile($file)
    {
        if(!is_readable($file))
            $this->fail('The schema file: ' . $file . ' is not readable or does not exist');
    }

    /**
     * Supply sql statement and one word type parameter (IE, create, update, insert, delete)
     * and the method will validate that the sql contains that syntax
     *
     * @param string $sql
     * @param string $type
     * @return bool
     */
    public function validateSqlType($sql, $type)
    {        
        preg_match('/^'.strtolower($type).'/',strtolower($sql),$matches);
        return ($matches[0]) ? true : false;
    }
    
    /**
     *  Creates database table
     *
     *  @param string  create table sql statement
     *  @return void
     */
    public function createTable($db, $sql)
    {
        if(!$this->validateSqlType($sql,'create'))
                $this->fail('The SQL provided is not a create statement');

        try {
            $db->exec($sql);
        } catch (PDO_Exception $e) {
            throw new Phergie_Plugin_Exception($e->getMessage());
        }
    }

    /**
     * Loads the schema file into array then searches for each table and if not found creates the table
     *
     * @return void
     */
    public function createTablesFromSchema($db, $file)
    {
        $this->isSchemaFile($file);
        $file = strtolower(file_get_contents($file));
        preg_match_all('/create\stable\s([a-z_]+).*;/', $file, $matches);

        if(count($matches[0]) != count($matches[1]))
            $this->fail('Schema array key value mismatch, the regular expression must not be working correctly');

        $tables = array_combine($matches[1],$matches[0]);

        foreach($tables as $name => $sql) {
            if(!$this->hasTable($db, $name))
                $this->createTable($db, $sql);
        }

    }
    
    /**
     *  Validates that database table exists
     *
     *  @param  string  table name
     *  @return bool
     */
    public function hasTable($db, $name)
    {
        $sql = 'SELECT COUNT(*)
            FROM sqlite_master
            WHERE name = :tableName';

        $statement = $db->prepare($sql);
        $statement->execute(array(':tableName' => $db->quote($name)));
        return (bool) $statement->fetchColumn();
    }

    public function dropTable($name){}
   
    /**
     *  Jared's crap debug method
     */

    private function debug($message)
    {
        if(self::DEBUG)
            echo 'DEBUG: ['. date('c') . '] - '. $message . "\n";
    }
}
?>
