<?php
    error_reporting(-1);

    function serverErrorHandler($code, $message, $file, $line)
    {
        $public_message = 'Error'
                        .' ( SERVER'
                        .$code
                        .' )';
        $private_message = $public_message
                         .' - PHP error - '
                         .$message
                         .' Line '
                         .$line
                         .' in '
                         .$file;

        Controller::log($code, $private_message);

        if (DEBUG_MODE) {
            $public_message = $private_message;
        }

        Controller::response(HTTP_STATUS_SERVER_ERROR, $public_message);
    }

    function serverFatalErrorShutdownHandler()
    {
        $last_error = error_get_last();
        if ($last_error['type'] === E_ERROR) {
            serverErrorHandler($last_error['type'], $last_error['message'], $last_error['file'], $last_error['line']);
        }
    }

    set_error_handler('serverErrorHandler');
    register_shutdown_function('serverFatalErrorShutdownHandler');

    try {
        require 'config.php';
        require 'flight/Flight.php';
        require 'Controller.php';

        Flight::set('flight.log_errors', true);

        /*Flight::route( '*', function()
            {
                return Controller::checkAPIKey();
            }
        );*/

        Flight::route('GET|POST|PUT /@class_name:[a-z]+', function ($class_name) {
                Controller::callRESTFunction($class_name);
            }
        );

        Flight::route('GET|PUT|DELETE /@class_name:[a-z]+/@id:[0-9]+', function ($class_name, $id) {
                Controller::setParam('id', $id);
                Controller::callRESTFunction($class_name);
            }
        );

        Flight::route('GET /@class_name:[a-z]+/@key:[a-z]+/@value', function ($class_name, $key, $value) {
                Controller::setParam($key, $value);
                Controller::callRESTFunction($class_name);
            }
        );

        Flight::start();
    } catch (PublicPrivateException $e) {
        $public_message = $e->getMessage();
        $private_message = $e->getPrivateMessage();
        $error_code = $e->getCode();

        Controller::log($error_code, $private_message);

        if (DEBUG_MODE) {
            $public_message = $private_message;
        }

        Controller::response($error_code, $public_message);
    } catch (Exception $e) {
        $public_message = $e->getMessage();
        $error_code = $e->getCode();
        $error_file = $e->getFile();
        $error_line = $e->getLine();

        $private_message = $public_message
                         .' ( code '
                         .$error_code
                         .' ) in '
                         .$error_file
                         .' line '
                         .$error_line;

        Controller::log($error_code, $private_message);

        if (DEBUG_MODE) {
            $public_message = $private_message;
        }

        Controller::response(HTTP_STATUS_SERVER_ERROR, $public_message);
    }
