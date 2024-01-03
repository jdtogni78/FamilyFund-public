#!/usr/bin/env bash

api_models="PriceUpdate PositionUpdate"
for model in $api_models; do
    php artisan infyom:api ${model} \
        --fieldsFile resources/api_schemas/${model}.json \
        --skip=migration,scaffold_requests,routes,scaffold_routes,views,menu,dump-autoload

    rm ./resources/model_schemas/${model}.json
    rm ./app/Http/Requests/API/Update${model}APIRequest.php
    rm ./app/Http/Controllers/API/${model}APIController.php
    find . -name \*${model}\*
done;

# factory has issue
