# CryptocurrencyProject

usage: python scriptCounter.py [DB file name] [start block] [end block]

creates database in the db file name specified that has information for the desired block range. Also outputs sorted reports of the number of each type of script and how much bitcoin was sent with that type of script in by_count.txt and by_value.txt.

python scriptCounterUpdate.py [DB file name]

creates or uses existing database with the given file name and does the functionality above from where the database last left off to the most recent block
