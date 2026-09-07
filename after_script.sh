ea-php82 composer.phar update
ea-php82 artisan cache:clear
ea-php82 artisan config:clear
# ea-php82 artisan config:cache
ea-php82 artisan migrate
# ea-php84 artisan create:table

# Update version with git commit SHA
# ea-php84 artisan version:update 3.1.33

# chmod -R 777 ./public
# chmod -R 777 ./storage
# chmod -R 777 ./bootstrap/cache/
# chown www-data:www-data ./public/ -R
# Bank API use
# cd scripts/bank
# nvm use 18
# npm install
