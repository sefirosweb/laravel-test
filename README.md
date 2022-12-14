# Larave test

[!["Buy Me A Coffee"](https://www.buymeacoffee.com/assets/img/custom_images/orange_img.png)](https://www.buymeacoffee.com/sefirosweb)

Bundle of packages for laravel, this is a test repository and showoff the packages works

# Start develop

```
git submodule init
git submodule update
cp .env.example .env
```

Start devcontainer from vs code

```
composer install
php artisan migrate
php artisan optimize
```

## Start develop submodules with react

```
cd packages/laravel-access-list/
npm install
npm run watch
```

## Generate type models:

```php
# LaravelMailing
php artisan types:generate --noKebabCase --outputDir=packages/laravel-mailing/resources/js/types/Models/ && \
php artisan types:generate --noKebabCase --modelDir=packages/laravel-mailing/src/Http/Models --outputDir=packages/laravel-mailing/resources/js/types/Models/

# LaravelAccessList
php artisan types:generate --noKebabCase --outputDir=packages/laravel-access-list/resources/js/types/Models/ && \
php artisan types:generate --noKebabCase --modelDir=packages/laravel-access-list/src/Http/Models --outputDir=packages/laravel-access-list/resources/js/types/Models/


```

# Init submodules

```
git submodule init
```

# Update (clone) submodules

```
git submodule update
```

# Add submodules

```
git submodule add git@github.com:sefirosweb/laravel-mailing.git ./packages/laravel-mailing
```

## New package

1º Create folders

2º Add into composer.json:

```
...
"autoload": {
    "psr-4": {
        ...
        "Sefirosweb\\LaravelMailing\\": "packages/laravel-mailing/src"
        ...
    }
},
```

3º Add into app.php service providers

```
Sefirosweb\LaravelMailing\LaravelMailingServiceProvider::class,
```

4º Execute composer

```
sail composer dump-autoload
```
