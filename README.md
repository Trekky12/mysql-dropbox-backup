Create MySQL database backup and upload it automatically to dropbox

# Installation
* Install the composer dependencies with `composer install`
* Create a Dropbox application and save the client_id, client_secret and access_token in the configuration file (`config.php`)
* Save the database credentials in the configuration file
* Set a zip password in the configuration file

# Configuration as command line argument
You can set different configurations by setting a command line argument and name the corresponding configuration like `config.<name>.php` and call the php script with `php backup.php <name>`