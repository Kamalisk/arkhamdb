ArkhamDB
=======

# Very quick guide on how to install a local copy

This guide assumes you know how to use the command-line and that your machine has php and mysql installed.

- install composer: https://getcomposer.org/download/
- clone the repo somewhere
- cd to it
- run `composer install` (at the end it will ask for the database configuration parameters)
- run `php bin/console doctrine:database:create`
- run `php bin/console doctrine:schema:create`
- checkout the card data from https://github.com/Kamalisk/arkhamdb-json-data
- run `php bin/console app:import:std path-to-arkhamdb-json-data/` pointing to where you checked out the json data
- run `php bin/console server:run`
