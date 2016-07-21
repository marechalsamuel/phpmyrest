<?php
    class MyValidatorException extends PublicPrivateException
    {
        public function __construct($public_message, $private_message)
        {
            parent::__construct($public_message, $private_message, HTTP_STATUS_DATA_ERROR);
        }
    }

    class MyValidator
    {
        private static $validationJson;

        public static function init()
        {
            try {
                $json_file = file_get_contents(realpath(dirname(__FILE__)).'/MyValidator.json');
                self::$validationJson = json_decode($json_file, true);
            } catch (Exception $e) {
                throw new MyValidatorException('Error', 'Unable to get MyValidator checking file content - Error ( MYVALID000 )');
            }
        }

        public static function getTables($filter = 'public')
        {
            $input_tables = self::$validationJson;
            $output_tables = array();
            foreach ($input_tables as $table_name => $input_table) {
                $output_table = array();
                $table_comment_array = explode($input_table['table_comment'], ';');
                $filter_in_table_comment_array = in_array($filter, $table_comment_array);
                if ($in_array || ($filter == 'private' && !$in_array)) {
                    $input_columns = $input_table['table_columns'];
                    $output_columns = array();
                    foreach ($input_columns as $column_name => $column) {
                        $column_comment_array = explode($column['column_comment'], ';');
                        $filter_in_column_comment_array = in_array($filter, $column_comment_array);
                        if ($filter_in_column_comment_array || ($filter == 'private' && !$filter_in_column_comment_array)) {
                            $output_columns[$column_name] = $column;
                        }
                    }
                    $output_table['table_name'] = $intput_table['table_name'];
                    $output_table['class_name'] = $intput_table['class_name'];
                    $output_table['table_type'] = $intput_table['table_type'];
                    $output_table['table_comment'] = $intput_table['table_comment'];
                    $output_table['table_columns'] = $output_columns;
                    $output_tables[$table_name] = $output_table;
                }
            }

            return $output_tables;
        }

        private static function throwException($myValidator_code, $details = '')
        {
            $public_message = $details;
            $private_message = $public_message
                             .' - Validation error - '
                             .'Error ( MYVALID'
                             .$myValidator_code
                             .' )';
            throw new MyValidatorException($public_message, $private_message);
        }

        public static function checkCanWrite($table_name)
        {
            if (self::$validationJson[$table_name]['table_type'] == 'BASE TABLE') {
                return true;
            } else {
                self::throwException('002', 'Impossible to write into view '.$table_name);

                return false;
            }
        }

        public static function validate($data_array, $table_name, $check_not_null = true)
        {
            $validData = array();
            if (!array_key_exists($table_name, self::$validationJson)) {
                self::throwException('003', 'Impossible to validate '.$table_name.' data.');

                return false;
            }

            $table_columns = self::$validationJson[$table_name]['table_columns'];
            $err_msgs = array();
            foreach ($table_columns as $field_name => $field_infos) {
                try {
                    if (isset($data_array[$field_name])) {
                        $validData[$field_name] = self::validateField($data_array[$field_name], $field_name, $field_infos, $check_not_null);
                    } else {
                        $not_null = $field_infos['IS_NULLABLE'] == 'NO';
                        $no_default = is_null($field_infos['COLUMN_DEFAULT']);
                        $not_ai = $field_infos['EXTRA'] != 'auto_increment';
                        if ($check_not_null && $not_null && $no_default && $not_ai) {
                            self::throwException('004', 'Field '.$field_name.' has to be set.');
                        }
                    }
                } catch (MyValidatorException $e) {
                    array_push($err_msgs, $e->getMessage());
                }
            }
            if (count($err_msgs) > 0) {
                $msg = implode($err_msgs, "\n");
                self::throwException('016', $msg);
            }

            return $validData;
        }

        private static function validateField($value, $field_name, $field_infos, $check_not_null)
        {
            $handled_types = ['int', 'tinyint', 'varchar', 'text', 'decimal', 'datetime', 'enum'];//TODO DO NOT DIE IF NOT HANDLED
            if (!in_array(strtolower($field_infos['DATA_TYPE']), $handled_types)) {
                self::throwException('005', 'Field '.$field_name.' is expect to be of a type ( '.$field_infos['DATA_TYPE'].' ) that is not supported by this API.');
            }
            $function_name = 'validate'.ucfirst(strtolower($field_infos['DATA_TYPE'])).'Field';

            return self::$function_name($value, $field_name, $field_infos);
        }

        private static function validateEnumField($value, $field_name, $field_infos)
        {
            $column_type = $field_infos['COLUMN_TYPE'];
            preg_match('#\((.*?)\)#', $column_type, $match);
            $enums = $match[1];
            $tmps = explode(',', $enums);
            foreach ($tmps as $key => $val) {
                $tmp = str_replace("'", '', $val);
                if (strtolower($tmp) == strtolower($value)) {
                    return $tmp;
                }
            }
            self::throwException('006', 'Field '.$field_name.' has to be one of those values : '.$enums.'.');
        }

        private static function validateTinyintField($value, $field_name, $field_infos)
        {
            if (!empty($field_infos['COLUMN_TYPE'])
                && $field_infos['COLUMN_TYPE'] == 'tinyint(1) unsigned') {
                return self::validateBooleanField($value, $field_name, $field_infos);
            } else {
                if ((string) intval($value) != (string) $value) {
                    self::throwException('007', 'Field '.$field_name.' has to be an integer.');
                } else {
                    if (!empty($field_infos['COLUMN_TYPE'])
                        && strpos($field_infos['COLUMN_TYPE'], 'unsigned')
                        && intval($value) < 0) {
                        self::throwException('008', 'Field '.$field_name.' has to be a positive integer.');
                    }
                }
            }

            return intval($value);
        }

        private static function validateIntField($value, $field_name, $field_infos)
        {
            if ((string) intval($value) != (string) $value) {
                self::throwException('009', 'Field '.$field_name.' has to be an integer.');
            } else {
                if (!empty($field_infos['COLUMN_TYPE'])
                    && strpos($field_infos['COLUMN_TYPE'], 'unsigned')
                    && intval($value) < 0) {
                    self::throwException('010', 'Field '.$field_name.' has to be a positive integer.');
                }
            }

            return intval($value);
        }

        private static function validateBooleanField($value, $field_name, $field_infos)
        {
            $boolean_values = ['0', '1', 'TRUE', 'FALSE', 'true', 'false', 0, 1, true, false, true, false, 'yes', 'no', 'YES', 'NO', 'on', 'off', 'ON', 'OFF'];
            if (!in_array($value, $boolean_values)) {
                self::throwException('011', 'Field '.$field_name.' has to be a boolean.');
            }

            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        private static function validateEmailField($value, $field_name, $field_infos)
        {
            $string_value = (string) $value;
            if (!filter_var($string_value, FILTER_VALIDATE_EMAIL)) {
                self::throwException('012', 'Field '.$field_name.' has to be an e-mail.');
            }

            return $string_value;
        }

        private static function validateTextField($value, $field_name, $field_infos)
        {
            return (string) $value;
        }

        private static function validateVarcharField($value, $field_name, $field_infos)
        {
            $string_value = $value;
            if (!empty($field_infos['CHARACTER_MAXIMUM_LENGTH'])
                && strlen($string_value) > intval($field_infos['CHARACTER_MAXIMUM_LENGTH'])) {
                self::throwException('013', 'Field '.$field_name.' has to be a string of no more than '.$field_infos['CHARACTER_MAXIMUM_LENGTH'].' characters.');
            }
            if ($field_name == 'email') {
                return self::validateEmailField($value, $field_name, $field_infos);
            }

            return $string_value;
        }

        private static function validateDecimalField($value, $field_name, $field_infos)
        {
            if ((string) floatval($value) != (string) $value) {
                self::throwException('014', 'Field '.$field_name.' has to be an float.');
            }

            return floatval($value);
        }

        private static function validateDatetimeField($value, $field_name, $field_infos)
        {
            if (!date_parse($value)) {
                self::throwException('015', 'Field '.$field_name.' has to be a datetime (ex: YYYY-MM-DD HH:MM:SS).');
            }

            return $value;
        }
    }
    MyValidator::init();
