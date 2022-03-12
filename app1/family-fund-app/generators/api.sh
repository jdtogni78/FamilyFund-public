#!/usr/bin/env bash

api_models="PriceUpdate PositionUpdate"
for model in $api_models; do
    php artisan infyom:api ${model} \
        --fieldsFile resources/api_schemas/${model}.json \
        --skip=migration,scaffold_requests,routes,scaffold_routes,views,menu,dump-autoload

    rm ./app/Http/Requests/API/Update${model}APIRequest.php
    rm ./app/Http/Controllers/API/${model}APIController.php
    rm ./resources/model_schemas/${model}.json
    find . -name \*${model}\*
done;


data_models="SymbolPrice SymbolPosition"
for model in $data_models; do
    php artisan infyom:api ${model} \
        --fieldsFile resources/api_schemas/${model}.json \
        --skip=migration,api_controller,api_routes,controller,scaffold_controller,scaffold_requests,routes,scaffold_routes,views,menu,dump-autoload

    rm ./app/Http/Requests/API/Create${model}APIRequest.php
    rm ./app/Http/Requests/API/Update${model}APIRequest.php
    rm ./tests/APIs/${model}ApiTest.php
    rm ./resources/model_schemas/${model}.json
    find . -name \*${model}\*
done

# factory has issue
