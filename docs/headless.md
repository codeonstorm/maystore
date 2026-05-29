
# Step 1: Install Bagisto Engine

```bash
composer create-project bagisto/bagisto my-bagisto-store

php artisan bagisto:install

php artisan serve
```


# Step 2: Install Bagisto API
```bash
composer require bagisto/bagisto-api

php artisan bagisto-api-platform:install

php artisan bagisto-api:generate-key --name="My App2" --rate-limit=null
```

# NOTE

Running the command:

php artisan bagisto-api:generate-key --name="My App2" --rate-limit=null
will create your storefront key with unlimited rate limiting. This means there will be no restrictions on the number of API requests your storefront can make.

# Step 3: Install Headless Storefront
npx -y @bagisto-headless/create your-storefront