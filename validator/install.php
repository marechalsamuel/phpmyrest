<html>
<?php

    require '../medoo/medoo.php';

    function eraseClasses()
    {
        $files = scandir('.');
        foreach ($files as $key => $file) {
            $do_not_delete = array('DBObject.php', '__template__.php', 'MyValidator.php', 'install.php');
            if (is_file($file) && !in_array($file, $do_not_delete)) {
                unlink($file);
                //echo "<p>File $file deleted</p>";
            }
        }
        file_put_contents('requirements.php', "<?php\n");
        file_put_contents('requirements.php', "\trequire_once 'MyValidator.php';\n", FILE_APPEND);
        file_put_contents('requirements.php', "\trequire_once 'DBObject.php';\n", FILE_APPEND);
    }

    function createClassFile($class_name, $table_name)
    {
        $template = file_get_contents('__template__.php');
        $translates = array('{{CLASS_NAME}}' => $class_name, '{{TABLE_NAME}}' => $table_name);
        $class_content = strtr($template, $translates);
        file_put_contents($class_name.'.php', $class_content);
        file_put_contents('requirements.php', "\trequire_once '$class_name.php';\n", FILE_APPEND);
        //echo "<p>Class $class_name for table $table_name created in $class_name.php</p>";
    }

    function toCamelCase($string)
    {
        return preg_replace_callback('/(_|-|\s)(.?)/', function ($m) { return strtoupper($m[2]); }, $string);
    }

    function createValidatorsJson($connection, $db_name)
    {
        $result = $connection->query('SELECT C.TABLE_NAME, C.COLUMN_NAME, C.COLUMN_COMMENT, C.COLUMN_DEFAULT, C.COLUMN_TYPE, C.IS_NULLABLE, C.DATA_TYPE, C.CHARACTER_MAXIMUM_LENGTH, C.NUMERIC_PRECISION, C.NUMERIC_SCALE, C.DATETIME_PRECISION, C.EXTRA, T.TABLE_COMMENT, T.TABLE_TYPE, K.CONSTRAINT_NAME, K.REFERENCED_TABLE_NAME, K.REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS AS C LEFT JOIN INFORMATION_SCHEMA.TABLES as T ON C.TABLE_NAME = T.TABLE_NAME AND C.TABLE_SCHEMA = T.TABLE_SCHEMA LEFT JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE as K ON C.TABLE_NAME = K.TABLE_NAME AND C.TABLE_SCHEMA = K.TABLE_SCHEMA AND C.COLUMN_NAME = K.COLUMN_NAME WHERE C.TABLE_SCHEMA = \''.$db_name.'\' ORDER BY C.TABLE_NAME, C.ORDINAL_POSITION');
        $tables = array();
        $desired_keys = array('COLUMN_NAME', 'COLUMN_COMMENT', 'COLUMN_DEFAULT', 'COLUMN_TYPE', 'IS_NULLABLE', 'DATA_TYPE', 'CHARACTER_MAXIMUM_LENGTH', 'NUMERIC_PRECISION', 'NUMERIC_SCALE', 'DATETIME_PRECISION', 'EXTRA', 'CONSTRAINT_NAME', 'REFERENCED_TABLE_NAME', 'REFERENCED_COLUMN_NAME');

        while ($row = $result->fetch()) {
            $table_name = $row['TABLE_NAME'];
            $class_name = toCamelCase($table_name);
            if (!array_key_exists($table_name, $tables)) {
                createClassFile($class_name, $table_name);
                $tables[$table_name] = array();
                $tables[$table_name]['table_name'] = $table_name;
                $tables[$table_name]['table_class'] = $class_name;
                $tables[$table_name]['table_type'] = $row['TABLE_TYPE'];
                $tables[$table_name]['table_comment'] = $row['TABLE_COMMENT'];
                $tables[$table_name]['table_columns'] = array();
            }
            $tables[$table_name]['table_columns'][$row['COLUMN_NAME']] = array();
            foreach ($desired_keys as $desired_key) {
                if (array_key_exists($desired_key, $row)) {
                    $tables[$table_name]['table_columns'][$row['COLUMN_NAME']][$desired_key] = $row[$desired_key];
                }
            }
        }
        file_put_contents('MyValidator.json', json_encode($tables, JSON_PRETTY_PRINT));

        return $tables;
    }

    function connect()
    {
        return new medoo([
            'database_type' => 'mysql',
            'server' => $_POST['db_host'],
            'port' => $_POST['db_port'],
            'database_name' => $_POST['db_name'],
            'username' => $_POST['db_user'],
            'password' => $_POST['db_password'],
            'charset' => 'utf8',
            'option' => [PDO::ATTR_CASE => PDO::CASE_NATURAL],
        ]);
    }

    function display_form()
    {
        $db_host = isset($_POST['db_host']) ? $_POST['db_host'] : '127.0.0.1';
        $db_port = isset($_POST['db_port']) ? $_POST['db_port'] : '3306';
        $db_name = isset($_POST['db_name']) ? $_POST['db_name'] : 'db_name';
        $db_user = isset($_POST['db_user']) ? $_POST['db_user'] : 'root';
        ?>
        <form method="post">
        <p><label for="db_host">Host : </label><input type="text" name="db_host" id="db_host" value="<?php echo $db_host ?>"/></p>
        <p><label for="db_port">Port : </label><input type="text" name="db_port" id="db_port" value="<?php echo $db_port ?>"/></p>
        <p><label for="db_name">Database : </label><input type="text" name="db_name" id="db_name" value="<?php echo $db_name ?>"/></p>
        <p><label for="db_user">User : </label><input type="text" name="db_user" id="db_user" value="<?php echo $db_user ?>"/></p>
        <p><label for="db_password">Password : </label><input type="text" name="db_password" id="db_password" value=""/></p>
        <p><input type="submit" name="db_connect" id="db_connect" value="Connect"/></p>
        </form>
        <?php

    }

    $connected = false;

    eraseClasses();
    
    if (isset($_POST['db_connect'])) {
        try {
            $connection = connect();
            $connected = true;
        } catch (Exception $e) {
            echo 'ERROR : UNABLE TO CONNECT DATABASE ( '.$e->getMessage().' )';
        }
    }

    if ($connected) {
        $MyValidator = createValidatorsJson($connection, $_POST['db_name']);
        file_put_contents('requirements.php', '?>', FILE_APPEND);
        ?>
        <pre>
        <?php
            echo json_encode($MyValidator, JSON_PRETTY_PRINT);
        ?>
        </pre>
        <?php

    } else {
        display_form();
    }
?>
</html>
