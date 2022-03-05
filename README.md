# Family Fund
A simple system to manage fund shares and composition.

See [V1 Specs](specs/V1.specs.md)
See [Remaining Specs](specs/V99.spec.md)

## Docker

See https://hub.docker.com/r/bitnami/laravel/

* Go to main dir

docker-compose up

* First time run composer accepting errors:

docker-compose exec myapp composer install

* First time setup database / reimport full db

mysql -h 127.0.0.1 -u famfun -p1234 familyfund < familyfund_dump.sql

* Dump dev database on Mac

cat ../../database/drop_all.sql > ../../database/familyfund_dump.sql;  
mysqldump --column-statistics=FALSE familyfund --no-tablespaces --skip-add-locks --skip-set-charset --tz-utc --routines --user=root --host=127.0.0.1 --port=3306 -p123456 | sed -e $'s/),(/),\\\n(/g' -e 's/ AUTO_INCREMENT=[0-9]*//g' >> ../../database/familyfund_dump.sql

* Create new lines to better see data changes:

sed -e $'s/),(/),\\\n(/g' familyfund_dump.sql > familyfund_dump.sql

## Reverse engineer models

tables=$(mysql -h 127.0.0.1 -u famfun -p1234 familyfund -N -e "show tables" 2> /dev/null | grep -v "+" | grep -v "failed_jobs\|migrations\|password_resets\|personal_access_tokens")

### Generate API CRUD

See https://infyom.com/open-source/laravelgenerator/docs/8.0/introduction

for t in $(echo $tables); 
    do echo $t; 
    arr=(${(s:_:)t})
    c=$(printf %s "${(C)arr}" | sed "s/ //g" | sed "s/s$//")
    docker-compose exec myapp php artisan infyom:scaffold $c --fromTable --tableName $t --skip dump-autoload
    docker-compose exec myapp php artisan infyom:api $c --fromTable --tableName $t --skip dump-autoload
    sed -i.bkp -e 's/private \($.*Repository;\)/protected \1/' coreui-generator/app/Http/Controllers/*Controller.php
done;

#### Generate from file

for t in $(echo $tables); 
    do echo $t; 
    arr=(${(s:_:)t})
    c=$(printf %s "${(C)arr}" | sed "s/ //g" | sed "s/s$//")
    docker-compose exec myapp php artisan infyom:scaffold $c --fieldsFile resources/model_schemas/$c.json --tableName $t --skip dump-autoload
    docker-compose exec myapp php artisan infyom:api $c --fieldsFile resources/model_schemas/$c.json --tableName $t --skip dump-autoload
    sed -i.bkp -e 's/private \($.*Repository;\)/protected \1/' coreui-generator/app/Http/Controllers/*Controller.php
done;
rm coreui-generator/app/Http/Controllers/*Controller.php.bkp


## Generate migrations (point to empty schema)

for t in $(echo $tables); 
    do echo $t; 
    arr=(${(s:_:)t})
    c=$(printf %s "${(C)arr}" | sed "s/ //g")
    docker-compose exec myapp php artisan infyom:scaffold $c --fieldsFile resources/model_schemas/$c.json \
        --skip model,controllers,api_controller,scaffold_controller,repository,requests,api_requests,scaffold_requests,routes,api_routes,scaffold_routes,views,tests,menu,dump-autoload
done;


php artisan infyom:scaffold Sample --fieldsFile vendor\infyom\laravel-generator\samples\fields_sample.json
### Generate UI CRUD

See https://github.com/awais-vteams/laravel-crud-generator

ex: docker-compose exec myapp php artisan make:crud account_balances

for t in $(echo $tables); 
    do echo $t; 
    docker-compose exec myapp php artisan make:crud $t; 
done;


### Add to Routes

Ex: Route::resource('account_balances', 'App\Http\Controllers\AccountBalanceController');

for t in $(echo $tables); do
    arr=(${(s:_:)t})
    c=$(printf %s "${(C)arr}" | sed "s/ //g" | sed 's/.$//')
    echo "Route::resource('${t}', 'App\Http\Controllers\\\\${c}Controller');"
done;

## PDF 

### Docker
wget https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6-1/wkhtmltox_0.12.6-1.buster_amd64.deb
sudo apt install ./wkhtmltox_0.12.6-1.buster_amd64.deb

### Laravel
https://github.com/barryvdh/laravel-snappy