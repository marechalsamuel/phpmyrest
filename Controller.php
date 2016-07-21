<?php
    require 'exceptions/PublicPrivateException.php';
    require 'params/Params.php';
    require 'medoo/MyMedoo.php';
    require 'validator/requirements.php';

    class Controller
    {
        private static $connection;

        public static function init()
        {
            self::$connection = new MyMedoo();
        }

        public static function getConnection()
        {
            return self::$connection;
        }

        public static function log($code, $message)
        {
            $datetime = date('Y-m-d H:i:s');
            $line = '[ '
                  .$datetime
                  .' ] : '
                  .$message
                  .' ( code : '
                  .$code
                  .' ) '
                  ."\n";
            file_put_contents('errors.log', $line, FILE_APPEND);
        }

        public static function response($http_code, $data = null, $type = 'json')
        {
            if ($http_code > 599 || $http_code < 100) {
                $http_code = 500;
            }
            if ($type == 'json') {
                Flight::json($data, $http_code);
            }
            Flight::halt($http_code, $data);
            Flight::stop();
        }

        public static function checkAPIKey()
        {
            if (Params::get('apikey') != API_KEY) {
                Flight::notFound();

                return false;
            }
            Params::remove('apikey');

            return true;
        }

        public static function setParam($key, $value)
        {
            Params::set($key, $value);
        }

        public static function callRESTFunction($class_name)
        {
            $method = strtolower($_SERVER['REQUEST_METHOD']);
            switch ($method) {
                case 'post':
                    $action_name = 'create';
                    break;
                case 'put':
                    $action_name = 'edit';
                    break;
                default:
                    $action_name = $method;
                    break;
            }
            $response = self::$action_name($class_name);
            self::response($response['http_status'], $response['data']);
        }

        public static function get($class_name, $where = null)
        {
            if (is_null($where)) {
                $where = Params::get();
            }
            $data = $class_name::get($where, $class_name);

            return array(
                'data' => $data,
                'http_status' => HTTP_STATUS_OK,
            );
        }

        public static function create($class_name, $data = null)
        {
            if (is_null($data)) {
                $data = Params::get();
            }
            $class_instance = new $class_name($data);
            $data = $class_instance->insert();

            return array(
                'data' => $data,
                'http_status' => HTTP_STATUS_CREATED,
            );
        }

        public static function edit($class_name, $data = null, $where = null)
        {
            if (is_null($data)) {
                $data = Params::get();
            }
            $class_instance = new $class_name($data);
            $data = $class_instance->update($where);

            return array(
                'data' => $data,
                'http_status' => HTTP_STATUS_OK,
            );
        }

        public static function delete($class_name, $data = null)
        {
            if (is_null($data)) {
                $data = Params::get();
            }
            $class_instance = new $class_name($data);
            $data = $class_instance->delete();

            return array(
                'data' => $data,
                'http_status' => HTTP_STATUS_OK,
            );
        }
    }
    Controller::init();
