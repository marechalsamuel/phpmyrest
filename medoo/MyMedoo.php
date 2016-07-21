<?php
  class MyMedooException extends PublicPrivateException
  {
      public function __construct($public_message, $private_message)
      {
          parent::__construct($public_message, $private_message, HTTP_STATUS_DATA_ERROR);
      }
  }

  require_once 'medoo/medoo.php';

  class MyMedoo extends medoo
  {
      public function __construct()
      {
          parent::__construct([
        'database_type' => DB_TYPE,
        'database_name' => DB_NAME,
        'server' => DB_HOST,
        'username' => DB_USER,
        'password' => DB_PW,
        'charset' => 'utf8',
        'port' => DB_PORT,

        'option' => [
          PDO::ATTR_CASE => PDO::CASE_NATURAL,
        ],
      ]);
      }

      private function throwException($mymedoo_code, $details = '')
      {
          $public_message = 'Error ( MYMEDOO'.$mymedoo_code.' )';
          $private_message = $public_message.' - Mysql error - '.$details.' ( Last query : '.$this->last_query().' )';
          throw new MyMedooException($public_message, $private_message);
      }

      private function treatException($mymedoo_code, $e, $action, $table_or_view = null)
      {
          $error_message = $e->getMessage();
          $error_code = $e->getCode();
          $error_file = $e->getFile();
          $error_line = $e->getLine();
          $details = $action.' error';
          if (!is_null($table_or_view)) {
              $details .= ' on table or view '.$table_or_view;
          }
          $details .= ' - '.$error_message.' ( code '.$error_code.' ) in '.$error_file.' line '.$error_line;
          $this->throwException($mymedoo_code, $details);
      }

      private function treatMedooError($mymedoo_code, $action, $table_or_view = null)
      {
          $error = $this->error();
          if (!is_null($error[2])) {
              $details = $action.' error';
              if (!is_null($table_or_view)) {
                  $details .= ' on table or view '.$table_or_view;
              }
              $details .= ' - '.$error[2];
              $this->throwException($mymedoo_code, $details);
          }
      }

      public function query($query)
      {
          try {
              $result = parent::query($query);
              $this->treatMedooError('001', 'query');
          } catch (Exception $e) {
              $this->treatException('002', $e, 'query');
          }

          return $result;
      }

      public function insert($table, $data)
      {
          try {
              $result = parent::insert($table, $data);
              $this->treatMedooError('003', 'insert', $table);
          } catch (Exception $e) {
              $this->treatException('004', $e, 'insert', $table);
          }

          return $result;
      }

      public function update($table, $data, $where = null)
      {
          try {
              $result = parent::update($table, $data, $where);
              $this->treatMedooError('005', 'update', $table);
          } catch (Exception $e) {
              $this->treatException('006', $e, 'update', $table);
          }
          if ($result == 0) {
              $result_get = $this->get($table, '*', $where);
              if (!$result_get) {
                  $this->throwException('007', 'update error on table '.$table.' : No entry with sent parameters to update');
              }
          }

          return $result;
      }

      public function delete($table, $where)
      {
          try {
              $result = parent::delete($table, $where);
              $this->treatMedooError('008', 'delete', $table);
          } catch (Exception $e) {
              $this->treatException('009', $e, 'delete', $table);
          }
          if ($result == 0) {
              $this->throwException('010', 'delete error on table '.$table.' : No entry with sent parameters to delete');
          }

          return $result;
      }

      public function select($table, $join, $columns = null, $where = null)
      {
          try {
              $result = parent::select($table, $join, $columns, $where);
              $this->treatMedooError('011', 'select', $table);
          } catch (Exception $e) {
              $this->treatException('012', $e, 'select', $table);
          }

          return $result;
      }

      public function get($table, $join = null, $column = null, $where = null)
      {
          try {
              $result = parent::get($table, $join, $column, $where);
              $this->treatMedooError('013', 'get', $table);
          } catch (Exception $e) {
              $this->treatException('014', $e, 'get', $table);
          }

          return $result;
      }
  }
