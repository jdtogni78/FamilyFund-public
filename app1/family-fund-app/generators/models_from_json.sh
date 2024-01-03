#!/usr/bin/env bash

data_models="$*"
for model in $data_models; do
    docker exec familyfund \
        php artisan infyom:api ${model} \
        --fieldsFile resources/model_schemas/${model}.json \
        --skip=scaffold_requests,routes,scaffold_routes,views,menu,dump-autoload,api_controller,api_routes,controller,scaffold_controller
        # migration

    #rm ./resources/model_schemas/${model}.json
    rm ./app/Http/Requests/API/Update${model}APIRequest.php
    rm ./app/Http/Requests/API/Create${model}APIRequest.php
    #rm ./tests/APIs/${model}ApiTest.php
    find . -name \*${model}\*
done

# factory has issue
