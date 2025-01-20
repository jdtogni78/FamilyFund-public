#!/usr/bin/env bash

data_models=("Goal")
for model in $data_models; do
    echo "Generating ${model}..."
    # docker exec familyfund \
        php artisan infyom:scaffold ${model} \
        --fieldsFile resources/model_schemas/${model}.json \
        --skip=scaffold_requests,scaffold_routes,dump-autoload
        # views,menu
        # migration

    #rm ./resources/model_schemas/${model}.json
    # rm ./app/Http/Requests/API/Update${model}APIRequest.php
    # rm ./app/Http/Requests/API/Create${model}APIRequest.php
    #rm ./tests/APIs/${model}ApiTest.php
    # find . -name \*${model}\*
done

# factory has issue
