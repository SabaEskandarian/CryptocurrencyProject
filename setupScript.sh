#!/bin/bash

PATHTOSCRIPT=/home/saba/temp

#get files
wget https://raw.githubusercontent.com/SabaEskandarian/CryptocurrencyProject/master/web/by_count.txt
wget https://raw.githubusercontent.com/SabaEskandarian/CryptocurrencyProject/master/web/by_value.txt
wget https://raw.githubusercontent.com/SabaEskandarian/CryptocurrencyProject/master/web/data.db
wget https://raw.githubusercontent.com/SabaEskandarian/CryptocurrencyProject/master/web/data.csv
wget https://raw.githubusercontent.com/SabaEskandarian/CryptocurrencyProject/master/web/index.php
wget https://raw.githubusercontent.com/SabaEskandarian/CryptocurrencyProject/master/web/scriptCounterUpdate.py

#add cron job
crontab -l > thecronfile
echo "@daily python $PATHTOSCRIPT/scriptCounterUpdate.py $PATHTOSCRIPT/data.db" >> thecronfile
crontab thecronfile
rm thecronfile
