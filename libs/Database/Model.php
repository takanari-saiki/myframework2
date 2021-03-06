<?php

namespace Libs\Database;
use PDO;
use Libs\Database\Database;
use Libs\Database\MySqlGrammar as Query;

class Model {
  protected static $table;
  protected $db;
  protected $properties;

  public function __construct()
  {
    $this->db = Database::getInstance();
  }

  public static function find($id)
  {
    $db = Database::getInstance();
    $table = static::$table;
    $query = "SELECT * FROM {$table} WHERE id = :id LIMIT 1;";
    $params = ['id' => $id];
    return static::arrayToObject($db->query($query, $params)[0]);
  }

  public static function all()
  {
    $db = Database::getInstance();
    $table = static::$table;
    $query = "SELECT * FROM {$table};";
    return static::arrayToObjectList($db->query($query));
  }

  public function create($params)
  {
    $paramsWithDate = $this->_joinDatetimesInsert($params);
    $permittedParams = $this->permitProperties($paramsWithDate);
    $query = Query::insertInto($permittedParams, static::$table);
    $this->db->execute($query, $permittedParams);
  }

  public function update($params, $id)
  {
    // id を$paramsと別にすることで、createと$paramsの中身を同じにしても、動くようになる。
    if (in_array('updated_at', $this->properties)) {
      $params['updated_at'] = date('Y-m-d H:i:s');
    }
    $params['id'] = $id;
    $permittedParams = $this->permitProperties($params);
    $query = Query::updateOneRecord($permittedParams, static::$table);
    $this->db->execute($query, $permittedParams);
  }

  public function destroy($id = null)
  {
    if (!isset($id)) {
      $id = $this->id;
    }
    $table = static::$table;
    $query = "DELETE FROM {$table} WHERE id = :id";
    $this->db->execute($query, ['id' => $id]);
  }

  public static function arrayToObject(Array $record)
  {
    $obj = new static();
    unset($obj->db);
    foreach($record as $key => $val) {
      $obj->$key = $val;
    }
    return $obj;
  }

  public static function arrayToObjectList(Array $records)
  {
    $objectList = [];
    foreach ($records as $r) {
      array_push($objectList, static::arrayToObject($r));
    }
    return $objectList;
  }

  // --- protected members ---

  protected function permitProperties($params)
  {
    $results = [];
    foreach ($this->properties as $p) {
      if (isset($params[$p])) {
        $results[$p] = $params[$p];
      }
    }
    return $results;
  }

  protected function _joinDatetimesInsert($params)
  {
    if (in_array('created_at', $this->properties)) {
      $params['created_at'] = date('Y-m-d H:i:s');
    }
    if (in_array('updated_at', $this->properties)) {
      $params['updated_at'] = date('Y-m-d H:i:s');
    }
		return $params;
  }
}