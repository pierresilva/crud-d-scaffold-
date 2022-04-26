# Crud-D-Scaffold for Laravel 7

  Hi, this is a scaffold generator for Laravel 7.
  You can Create Basic CRUD application by using this package.


## How to installation and execution

### Step 1: Installing laravel/ui package through Composer and create bootstrap

```
composer require laravel/ui
php artisan ui bootstrap
```
if you want to use laravel auth, use below
```
composer require laravel/ui
php artisan ui bootstrap --auth
```

### Step 2: Installing package through Composer

```
composer require pierresilva/laravel-crud
```

### Step 3: Run Artisan!

You're all set.
Run `php artisan` from the console, and you'll see the new commands below.
```
- 'crud:setup' : Setup crud with bootstrap
```

  This completes the preparation.  
  Let's register the sample.  

### Step 4: Crud

##### (i) Copy /vendor/pierresilva/laravel-crud/crud-d-scaffold_case0010.json to your laravel project root
```
cp ./vendor/pierresilva/laravel-crud/crud-d-scaffold_case0010.json ./crud-d-scaffold.json
```
##### (ii) run crud:setup
```
php artisan crud:setup -f
```
  Overwriting the file with -f option.  
  For the first time, the f option is unnecessary. (No problem with putting on)
  It is recommended to back up with git before set up scaffold.

##### (iii) run npm install and npm run dev
```
npm install
npm run dev
```

##### (iv) run migration and seeding
```
php artisan migrate
```
```
php artisan db:seed
```

  It's all over.  
  Please check your application.

  If you want to modify the application structure,
  After running [migrate: rollback], delete the migration file (/ database / migrations /) and then
  execute [ Crud-d-scaffold: setup-f ]



## How to create crud-d-scaffold.json

see crud-d-scaffold_case00XX.json

- /*...*/ It is treated as a comment.

and now you can create crud-d-scaffold.json from this site.

## Options
-f, --force Overwrite the file. (If there is an existing file in the absence of the option, processing stops there.)



## Usage notes
* You can use laravel auth. At first [ php artisan make:auth ] and run crud-d-scaffold.
* Column names, model names, etc. are automatically converted according to the convention of laravel, so it is not possible to create singular and plural models at the same time.

