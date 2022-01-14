
for f in $(ls \
        coreui-generator/app/Models/*.php \
        coreui-generator/app/Repositories/*Repository.php \
        coreui-generator/database/Factories/*Factory.php); do
    for c (Account Portfolio Fund Transaction); do
        echo sed -e "s/\(App\\Models\\${c}\)\(\[ ;\]\)/\1Ext\2/g" -e "s/${c}::class/${c}Ext::class/g" $f 
    done
done
