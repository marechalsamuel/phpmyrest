<?php
  class Params {
    private static $params = Array();
    private static $method = null;

    /**
      * @brief Lookup request params
      * @param string $name Name of the argument to lookup
      * @param mixed $default Default value to return if argument is missing
      * @returns The value from the GET/POST/PUT/DELETE value, or $default if not set
      */
    public static function get($name = null, $default = null) {
      if (empty($name)) return self::$params;
      if (empty(self::$params[$name])) return $default;
      return self::$params[$name];
    }

    public static function remove($name) {
        unset(self::$params[$name]);
        switch (self::$method) {
          case 'get':
            unset($_GET[$name]);
            break;
          case 'post':
            unset($_POST[$name]);
            break;
          default://put & delete
            unset($GLOBALS['_'.self::$method][$name]);
            unset($_REQUEST[$name]);
            break;
        }
    }

    public static function set($name, $value) {
      self::$params[$name] = $value;
      switch (self::$method) {
        case 'get':
          $_GET[$name] = $value;
          break;
        case 'post':
          $_POST[$name] = $value;
          break;
        default://put & delete
          $GLOBALS['_'.self::$method][$name] = $value;
          $_REQUEST[$name] = $value;
          break;
      }
    }

    public static function init(){
      self::$method = strtolower($_SERVER['REQUEST_METHOD']);
      switch (self::$method) {
        case 'get':
          self::$params = $_GET;
          break;
        case 'post':
          self::$params = $_POST;
          break;
        default://put & delete
          parse_str(file_get_contents('php://input'), self::$params);
          $GLOBALS['_'.self::$method] = self::$params;
          // Add these request vars into _REQUEST, mimicing default behavior, PUT/DELETE will override existing COOKIE/GET vars
          $_REQUEST = self::$params + $_REQUEST;
          break;
      }
    }
  }

  Params::init();
?>
