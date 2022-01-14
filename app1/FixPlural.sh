
for f in $(ls coreui-generator/app/Http/Controllers/*sController.php); do
    rm $f 
done

for f in $(ls coreui-generator/app/Http/Controllers/API/*sAPIController.php); do
    rm $f 
done

for f in $(ls coreui-generator/app/Http/Requests/*sRequest.php); do
    rm $f 
done

for f in $(ls coreui-generator/app/Http/Requests/API/*sAPIRequest.php); do
    rm $f 
done

for f in $(ls coreui-generator/app/Http/Resources/*sResource.php); do
    rm $f 
done

for f in $(ls coreui-generator/app/Models/*s.php); do
    rm $f 
done

for f in $(ls coreui-generator/app/Repositories/*sRepository.php); do
    rm $f 
done

for f in $(ls coreui-generator/database/Factories/*sFactory.php); do
    rm $f 
done

for f in $(ls coreui-generator/resources/model_schemas/*s.json); do
    rm $f 
done

for f in $(ls coreui-generator/tests/APIs/*sApiTest.php); do
    rm $f 
done

for f in $(ls coreui-generator/tests/Repositories/*sRepositoryTest.php); do
    rm $f 
done

