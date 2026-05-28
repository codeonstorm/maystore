```bash
composer require bagisto/rest-api

# Replace with your actual domain in .env
SANCTUM_STATEFUL_DOMAINS=http://localhost/public

# Step 3: Run Installation Command
Configure the L5-Swagger documentation:

php artisan bagisto-rest-api:install


# Access
http://localhost/api/admin/documentation
http://localhost/api/shop/documentation


curl -H "Authorization: Bearer YOUR_TOKEN_HERE" \
     -H "Accept: application/json" \
     http://localhost/public/api/v1/admin/get

```