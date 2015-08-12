ThronesDB
=======

# Very quick guide on how to install a local copy

This guide assumes you know how to use the command-line and that your machine has php and mysql installed.

- install composer: https://getcomposer.org/download/
- clone the repo somewhere
- cd to it
- run `composer install` (at the end it will ask for the database configuration parameters)
- run `php app/console doctrine:database:create`
- run `php app/console doctrine:schema:create`
- import data into mysql
- run `php app/console server:run`
