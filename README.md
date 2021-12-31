# Family Fund
A simple system to manage fund shares and composition.

See [V1 Specs](specs/V1.specs.md)
See [Remaining Specs](specs/V99.spec.md)

## Docker

* Go to main dir

docker-compose up

* First time run composer accepting errors:

docker-compose exec myapp composer install

* First time setup database / reimport full db

mysql -h 127.0.0.1 -u famfun -p1234 familyfund < familyfund_only_dump.sql

* Dump dev database on Mac

mysqldump --column-statistics=FALSE familyfund --skip-add-locks --skip-lock-tables --user=root --host=127.0.0.1 --port=3306 -p123456 > familyfund_only_dump.sql

## Reverse engineer models

### Generate CRUD

ex: docker-compose exec myapp php artisan make:crud account_balances

tables=$(mysql -h 127.0.0.1 -u famfun -p1234 familyfund -N -e "show tables" 2> /dev/null | grep -v "+" | grep -v "failed_jobs\|migrations\|password_resets\|personal_access_tokens")
for t in $(echo $tables); do echo $t; docker-compose exec myapp php artisan make:crud $t; done;


### Add to Routes

Route::resource('account_balances', 'App\Http\Controllers\AccountBalancesController');
