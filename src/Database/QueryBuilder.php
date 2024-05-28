<?php

namespace Tabel\Core\Database;


/**
 * @package QueryBuilder
 * 
 * Class that interacts with the db
 * @return \Tabel\Models\Model Model returns an instance of Model class
 * 
 * @todo Implement ::get('database')->select('users')->where(['email', $email]);
 * 
 * @author Chungu Developers <developers@chungu.co.ke>
 */

class QueryBuilder {

  private static $instance;
  private $pdo;

  private function __construct($pdo)    {
      $this->pdo = $pdo;
  }

  public static function getInstance($pdo)    {
      if (self::$instance === null) {
          self::$instance = new self($pdo);
      }

      return self::$instance;
  }

   /**
     * Run a SQL query and return the results or true for update/delete queries.
     *
     * @param string $sql The SQL query.
     * @param string $table The name of the table.
     * @return array|bool The results of the query or true for update/delete queries.
     * @throws \Exception If there is an error with the query.
     */
  public function runQuery(string $sql, string $table) {

    $model = singularize(ucwords($table));

    try {
      $statement = $this->pdo->prepare($sql);
      $statement->execute();
    } catch (\Exception $e) {

      logger("Error", 'Database: ' . $e->getMessage() . PHP_EOL . " $sql ");
      throw new \Exception("There seems to be something wrong with the query!" . PHP_EOL);
    }


    $results = $statement->fetchAll(\PDO::FETCH_CLASS,  "Tabel\\Models\\{$model}");

    if ($this->isUpdateOrDeleteQuery($sql) && empty($results)) {
        return true;
    }

    return $results;
  }
  /**
   * selectAll
   * 
   * This selects everything from a given table
   * @param string $table table from which to selct the data
   * 
   * @return \Tabel\Models\Model returns an instance of Model with the same table name
   */
  public function selectAll(string $table) {

    $sql = "select * from {$table} ORDER BY `created_at` DESC;";

    return $this->runQuery($sql, $table);
  }
  /**
   * Select
   * Selects given values 
   * 
   * @param string $table Table from which to select
   * @param array $values The columns in the db to select from
   * 
   * @return \Tabel\Models\Model returns an instance of Model with the same table name
   */
  public function select(string $table, array $values) {

    $values =  implode(',', $values);
    $sql = "select {$values}  from {$table}";
    return $this->runQuery($sql, $table);
  }

  public function selectAllWhereID(string $table, $value) {

    //To do Implement Dynamic Primary key row
    $sql = "select * from {$table} where `id` = \"$value\" ORDER BY `created_at` DESC;";

    return $this->runQuery($sql, $table);
  }

  public function selectAllWhere(string $table, $column, $value, $condition = "=") {

    $sql = "select * from {$table} where `{$column}` $condition \"$value\" ORDER BY `created_at` DESC;";

    return $this->runQuery($sql, $table);
  }


  /**
   * SelectWhere
   * 
   * Selects given column names given a certain condition
   * 
   * @param string $table Table from which to select
   * @param array $values The columns in the db to select from
   * @param array $condition The condition to be fulfiled by the where clause
   * 
   * @example 
   *  selectWhere('table_name", ['email', 'pass'], ['email','test@test.com']);
   */

  public function selectWhere(string $table, array $values, array $condition) {

    $values =  implode(',', $values);

    //pure madness
    $condition[1] = sprintf("%s$condition[1]%s", '"', '"');

    $condition =  implode(' = ', $condition);

    $sql = "select {$values}  from {$table} where {$condition}";

    return $this->runQuery($sql, $table);
  }


  public function update(string $table, $dataToUpdate, $where, $isValue) {
    $sql = "UPDATE {$table} SET $dataToUpdate WHERE `$where` = \"$isValue\"";



    logger("Info", '<b>' . ucfirst(auth()->username) . '</b>' . " Updated a record in {$table} table ");

    return $this->runQuery($sql, $table);
  }
  //DELETE FROM table_name WHERE condition;
  public function delete(string $table, $where, $isValue) {

    $sql = "DELETE FROM {$table} WHERE `$where` = \"$isValue\"";


    logger("Info", '<b>' . ucfirst(auth() ? auth()->username : $_SERVER['REMOTE_ADDR']) . '</b>' . " Deleted a record in {$table} table ");

    return $this->runQuery($sql, $table);
  }
  public function insert(string $table, array $parameters) {

    $sql = sprintf(
      'INSERT INTO %s (%s) VALUES (%s)',

      $table,

      implode(', ', array_keys($parameters)),

      ':' . implode(', :', array_keys($parameters))
    );

    try {

      $statement = $this->pdo->prepare($sql);
      $statement->execute($parameters);

      logger("Info", '<b>' . ucfirst(auth()->username ?? "Someone") . '</b>' . " Inserted a new record to {$table} table ");
    } catch (\Exception $e) {

      logger("Error", "Database: " . $e->getMessage() . ": <br> <pre>{$sql}</pre>");

      throw new \Exception("Database: Error with Query" . $e->getCode());
    }
  }
  //Albtatry Query FROM table_name WHERE condition;
  public function query(string $sql) {

    list($childClass, $caller) = debug_backtrace(false, 2);

    try {
      $statement = $this->pdo->prepare($sql);
      $statement->execute();
    } catch (\Exception $e) {

      logger("Error", 'Database: ' . $e->getMessage() . PHP_EOL . " $sql ");
      throw new \Exception("Wrong query <br> <pre>{$sql}</pre>" . PHP_EOL . $e->getCode());
    }

    $results = $statement->fetchAll(\PDO::FETCH_CLASS, $caller['class']);

    if ($this->isUpdateOrDeleteQuery($sql) && empty($results)) {
       return true;
    }
    return $results;
  }

  public function queryInsert(string $sql) {
    try {

      $statement = $this->pdo->prepare($sql);
      if ($statement->execute()) {
        logger("Info", '<b>' . ucfirst(auth()->username ?? "Someone") . '</b>' . " Inserted a new record to table ");
      } else {
        logger("Info", '<b>' . ucfirst(auth()->username ?? "Someone") . '</b>' . " something went wrong");
      }
    } catch (\Exception $e) {

      logger("Error", "Database: " . $e->getMessage() . ": <br> <pre>{$sql}</pre>");

      throw new \Exception("Database: Error with Query" . $e->getCode());
    }
  }

  //DELETE FROM table_name WHERE condition;
  public function join(string $table1, string $table2, $fk, $pk) {


    /*
       * SELECT * FROM table1 JOIN table2 ON table1.id1=table2.id2
       */
    $sql = "SELECT * FROM `{$table1}` INNER JOIN `{$table2}` ON {$table1}.{$fk}={$table2}.{$pk} ";


    $statement = $this->pdo->prepare($sql);
    $statement->execute();

    return $statement->fetchAll(\PDO::FETCH_ASSOC);
  }

  public function count(string $table, array $condition) {
    //SELECT COUNT(*) FROM $table WHERE $condition[0] = $condition[2];
    list($column, $value) = $condition;
    $sql = "SELECT COUNT(*) AS count FROM $table WHERE $column = \"$value\"";

    return $this->runQuery($sql, $table);
  }

  private function isUpdateOrDeleteQuery(string $sql):bool {
    return str_contains($sql, "update") || str_contains($sql, "delete");
 }
}
