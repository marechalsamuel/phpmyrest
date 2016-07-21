<?php
  class DBObject
  {
      protected $data;
      protected static $tableName;

      public function __construct($data)
      {
          $this->data = $data;
      }

      public function getData()
      {
          return $this->data;
      }

      public function validate($check_not_null = true, $data = null)
      {
          if (is_null($data) && isset($this->data)) {
              $data = $this->data;
          }
          $this->data = MyValidator::validate($data, static::$tableName, $check_not_null);

          return $this->data;
      }

      protected function checkCanWrite()
      {
          return MyValidator::checkCanWrite(static::$tableName);
      }

      public function insert()
      {
          $this->checkCanWrite();
          $this->validate(true);
          $id = Controller::getConnection()->insert(static::$tableName, $this->data);

          return $this->fetch(['id' => $id]);
      }

      public function update($where = null)
      {
          $this->checkCanWrite();
          $this->validate(false);
          if (is_null($where)) {
              $where = ['id' => $this->data['id']];
          }
          Controller::getConnection()->update(static::$tableName, $this->data, $where);

          return $this->fetch($where);
      }

      public function delete()
      {
          $this->checkCanWrite();
          $this->validate(false);
          $id = $this->data['id'];
          Controller::getConnection()->delete(static::$tableName, $this->data);

          return $this->fetch(['id' => $id]);
      }

      protected function fetch($where = null)
      {
          $this->validate(false);
          if (is_null($where)) {
              $where = ['AND' => $this->data];
          }
          $this->data = Controller::getConnection()->get(static::$tableName, '*', $where);

          return $this->getData();
      }
      //STATIC PART
      public static function get($where = null)
      {
          if (count($where) > 1) {
              $where = array('AND' => $where);
          }
          $data = Controller::getConnection()->select(static::$tableName, '*', $where);

          return $data;
      }
  }
