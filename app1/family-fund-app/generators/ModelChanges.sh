
for f in $(ls \
        app/Models/*.php \
        app/Repositories/*Repository.php \
        database/Factories/*Factory.php); do
    for c (Account Portfolio Fund Transaction Asset); do
        sed -i.bkp -e "s/\(App\\Models\\${c}\)\([ ;]\)/\1Ext\2/g" -e "s/${c}::class/${c}Ext::class/g" $f
        rm $f.bkp
    done
done

# do not mess with auto generated fields from db
# do not set random value for foreign keys
for f in $(ls \
        database/factories/*Factory.php); do
    for c (deleted_at created_at updated_at); do
        sed -i.bkp -e "s/\([']$c\)/\/\/\1/" $f
        rm $f.bkp
    done
    sed -i.bkp -e "s/\(['].*_id[']\)/\/\/\1/" $f
    rm $f.bkp
done

for f in $(ls \
        app/Models/*.php); do
    sed -i.bkp "s/'created_at' => 'required'/'created_at' => 'nullable'/" $f
    rm $f.bkp
done
