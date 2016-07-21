<?php
  class wordLink extends DBObject
  {
      protected $data;
      protected static $connection;
      protected static $tableName = 'word_link';

      public function __construct($data)
      {
          /*
            self::$connection = Controller::getConnection();
            $this->data = $data;
          */
      parent::__construct($data);
      }

      public function getData()
      {
          /*
            return $this->data;
          */
      return parent::getData();
      }

      public function validate($check_not_null = true, $data = null)
      {
          /*
            if (is_null($data) && isset($this->data)) {
              $data = $this->data;
            }
            $this->data = MyValidator::validate( $data, self::$tableName, $check_not_null );
            return $this->data;
          */
      return parent::validate($check_not_null, $data);
      }

      protected function checkCanWrite()
      {
          /*
            return MyValidator::checkCanWrite( self::$tableName );
          */
      return parent::checkCanWrite();
      }

      public function insert()
      {
          /*
            $this->checkCanWrite();
            $this->validate( TRUE );
            $id = self::$connection->insert( self::$tableName, $this->data );
            return $this->fetch( ['id'=>$id] );
          */
      return parent::insert();
      }

      public function update($where = null)
      {
          /*
            $this->checkCanWrite();
            $this->validate( FALSE );
            if ( is_null($where) ) $where = ['id'=> $this->data['id'] ];
            self::$connection->update( self::$tableName, $this->data, $where );
            return $this->fetch( $where );
          */
      return parent::update($where);
      }

      public function delete()
      {
          /*
            $this->checkCanWrite();
            $this->validate( FALSE );
            $id = $this->data['id'];
            self::$connection->delete( self::$tableName, $this->data );
            return $this->fetch( ['id'=>$id] );
          */
      return parent::delete();
      }

      protected function fetch($where = null)
      {
          /*
            $this->validate( FALSE );
            if ( is_null($where) ) $where = ['AND'=>$this->data];
            $this->data = self::$connection->get( self::$tableName, '*', $where );
            return $this->getData();
          */
      return parent::fetch($where);
      }

          //STATIC PART
    public static function get($where = null)
    {
        /*
            if (count($where)>1) $where = array("AND" => $where);
            $data = self::$connection->select( self::$table_name, '*', $where );
            return $data;
          */
      return parent::get($where);
    }
  }
