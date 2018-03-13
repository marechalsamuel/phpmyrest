# phpmyrest
Php REST api generator for Mysql Database, with field validation

## Install data validator
- Run ./validator/install.php to read database and create class files matching database table

## Use the REST API
- Update config.php with your environment configurations, generate your own API_KEY
- Call GET, POST, PUT, DELETE on your project root with the table name as parameter (example : GET http://localhost/tablename/1?apikey=4p1k3y to retrieve data from tablename having id = 1 )