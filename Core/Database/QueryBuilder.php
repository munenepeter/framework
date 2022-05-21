<?php

namespace Babel\Core\Database;

use Babel\Core\Mantle\Logger;


/**
 * @package QueryBuilder
 * 
 * Class that interacts with the db
 * @return Object Model returns an instance of Model class
 * 
 * @todo Implement App::get('database')->select('users')->where(['email', $email]);
 */

class QueryBuilder {

  protected $pdo;

  public function __construct($pdo) {

    $this->pdo = $pdo;
  }

  /**
   * selectAll
   * 
   * This selects everything from a given table
   * @param String $table table from which to selct the data
   * 
   * @return Model returns an instance of Model with the same table name
   */
  public function selectAll(String $table) {

    $statement = $this->pdo->prepare("select * from {$table}");

    if (!$statement->execute()) {

      throw new \Exception("Something is up with your Select {$statement}!");
    }

    $model = substr_replace(ucwords($table), '', -1);

    return $statement->fetchAll(\PDO::FETCH_CLASS,  "Babel\\Models\\{$model}");
  }
  /**
   * Select
   * Selects given values 
   * 
   * @param String $table Table from which to select
   * @param Array $values The columns in the db to select from
   * 
   * @return Model returns an instance of Model with the same table name
   */
  public function select(string $table, array $values) {

    $values =  implode(',', $values);
    $statement = $this->pdo->prepare("select {$values}  from {$table}");

    if (!$statement->execute()) {

      throw new \Exception("Something is up with your Select {$statement}!");
    }
    $model = ucwords($table);
    return $statement->fetchAll(\PDO::FETCH_CLASS,  "Babel\\Models\\{$model}");
  }

  public function selectAllWhere(string $table, int $value) {
  
    $model = singularize(ucwords($table));

    $statement = $this->pdo->prepare("select * from {$table} where {$model}ID = $value");

    if (!$statement->execute()) {

      throw new \Exception("Something is up with your Select {$statement}!");
    }
    
    return $statement->fetchAll(\PDO::FETCH_CLASS,  "Babel\\Models\\{$model}");
  }

  /**
   * SelectWhere
   * 
   * Selects given column names given a certain condition
   * 
   * @param String $table Table from which to select
   * @param Array $values The columns in the db to select from
   * @param Array $condition The condition to be fulfiled by the where clause
   * 
   * @example 
   *  selectWhere('table_name", ['email', 'pass'], ['email','test@test.com']);
   */

  public function selectWhere(string $table, array $values, array $condition) {

    $values =  implode(', ', $values);
    //pure madness
    $condition[1] = sprintf("%s$condition[1]%s", '"', '"');

    $condition =  implode(' = ', $condition);
    $statement = $this->pdo->prepare("select {$values}  from {$table} where {$condition}");

    $sql = "select {$values}  from {$table} where {$condition}";
    Logger::log("INFO: Called(where) $sql");

    if (!$statement->execute()) {
      throw new \Exception("Something is up with your Select {$statement}!");
    }
    $model = ucwords(substr($table, 0, -1));
    return $statement->fetchAll(\PDO::FETCH_CLASS,  "Babel\\Models\\{$model}");
  }

  public function insert(string $table, array $parameters) {

    $sql = sprintf(
      'insert into %s (%s) values (%s)',

      $table,

      implode(', ', array_keys($parameters)),

      ':' . implode(', :', array_keys($parameters))
    );
    Logger::log("INFO: Called (insert) $sql");
    try {

      $statement = $this->pdo->prepare($sql);
      $statement->execute($parameters);
    } catch (\Exception $e) {

      throw new \Exception('Something is up with your Insert!' . $e->getMessage());
      die();
    }
  }
   //Albtatry Query FROM table_name WHERE condition;
   public function query(string $sql) {
    try {

      $statement = $this->pdo->prepare($sql);
      $statement->execute();

      return $statement->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\Exception $e) {

      Logger::log("ERROR: Something is up with your Query -> $sql, " .$e->getMessage());

      throw new \Exception('Something is up with your Query!' . $e->getMessage());
      die();
    }
  }
}
