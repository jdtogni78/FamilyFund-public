# Family Fund

A simple system to manage fund shares and composition.

See [V1 Specs](specs/V1.spec.md)
See [Remaining Specs](specs/V99.spec.md)

## Docker

See https://hub.docker.com/r/bitnami/laravel/

* Go to main dir

docker-compose -f docker-compose.yml -f docker-compose.${MYENV}.yml up

* First time run composer accepting errors:

docker-compose exec familyfund composer install

* First time setup database / reimport full db

docker-compose exec familyfund php artisan migrate:fresh
mysql -h 127.0.0.1 -u famfun -p1234 familyfund < familyfund_dump.sql

* Dump dev database on Mac

generators/dump_ddl.sh
generators/dump_data.sh

 
* Create new lines to better see data changes:

sed -e $'s/),(/),\\\n(/g' familyfund_dump.sql > familyfund_dump.sql

## Reverse engineer models

tables=$(mysql -h 127.0.0.1 -u famfun -p1234 familyfund -N -e "show tables" 2> /dev/null | grep -v "+" | grep -v "failed_jobs\|migrations\|password_resets\|personal_access_tokens")

### Generate API CRUD

See https://infyom.com/open-source/laravelgenerator/docs/8.0/introduction
See generators/models.sh

#### Generate from file

for t in $(echo $tables); 
    do echo $t; 
    arr=(${(s:_:)t})
    c=$(printf %s "${(C)arr}" | sed "s/ //g" | sed "s/s$//")
    php artisan infyom:scaffold $c --fieldsFile resources/model_schemas/$c.json --tableName $t --skip dump-autoload
    php artisan infyom:api $c --fieldsFile resources/model_schemas/$c.json --tableName $t --skip dump-autoload
    sed -i.bkp -e 's/private \($.*Repository;\)/protected \1/' app/Http/Controllers/*Controller.php
done;
rm app/Http/Controllers/*Controller.php.bkp


## Generate migrations (point to empty schema)

for t in $(echo $tables); 
    do echo $t; 
    arr=(${(s:_:)t})
    c=$(printf %s "${(C)arr}" | sed "s/ //g")
    docker-compose exec familyfund php artisan infyom:scaffold $c --fieldsFile resources/model_schemas/$c.json \
        --skip model,controllers,api_controller,scaffold_controller,repository,requests,api_requests,scaffold_requests,routes,api_routes,scaffold_routes,views,tests,menu,dump-autoload
done;

php artisan infyom:scaffold Sample --fieldsFile vendor\infyom\laravel-generator\samples\fields_sample.json

## PDF 

### Docker
wget https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6-1/wkhtmltox_0.12.6-1.buster_amd64.deb
sudo apt install ./wkhtmltox_0.12.6-1.buster_amd64.deb

### Laravel
https://github.com/barryvdh/laravel-snappy


## Mail

We are using a separate container for MailHog, but we still need sendmail & mhsendmail on php server & local (unit tests).
See Dockerfile & docker-compose.

### Local setup
// sudo apt-get update
// sudo apt-get install -y sendmail golang-go git
go get github.com/mailhog/mhsendmail
sudo cp ~/go/bin/mhsendmail /usr/local/bin/

#### /etc/hosts 
echo "127.0.0.1 noreply.domain.com mailhog" | sudo tee -a /etc/hosts

#### Update PHP.ini
sendmail_path = "/usr/local/bin/mhsendmail --smtp-addr=mailhog:1025"

#### Test
php tests/TestEmail.php

for f in run_report.log.*.gz; do gunzip -c $f | sed '0,/^.*## Positions$/d' | sed  -n '/.*## Report/q;p'; done|grep SPXL|sort
{"timestamp": "2022-04-01 19:46:50" "source": "FFIB" "symbols": {
{"name": "SPXL" "type": "STK" "position": 41.0}
{"name": "SOXL" "type": "STK" "position": 119.0}
{"name": "TECL" "type": "STK" "position": 82.0}
{"name": "FTBFX" "type": "FUND" "position": 149.851}
{"name": "IAU" "type": "STK" "position": 85.0}
{"name": "BTC" "type": "CRYPTO" "position": 0.02229813}
{"name": "ETH" "type": "CRYPTO" "position": 0.52756584}
{"name": "FIPDX" "type": "FUND" "position": 563.964}
{"name": "LTC" "type": "CRYPTO" "position": 5.67880488}
{"name": "CASH", "type": "CSH", position: 3212.43}
}

#### Start sending reports (email)

php artisan queue:work

### Change Password Command Line

php artisan tinker
    $user = App\Models\UserExt::where('email', 'admin@dev.familyfund.local')->first();
    $user->password = Hash::make('new_password');
    $user->save();
