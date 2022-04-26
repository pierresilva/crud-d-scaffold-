<?php

/**
 * Copyright (c) 2016 dog-ears
 *
 * This software is released under the MIT License.
 * http://dog-ears.net/
 */

namespace pierresilva\LaravelCrud\Core;

use App\Models\UserPermission;
use App\Models\UserRole;
use App\UserPermissionGroup;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Str;
use pierresilva\LaravelCrud\Commands\CrudSetupCommand;
use pierresilva\LaravelCrud\Core\StubCompiler;
use pierresilva\LaravelCrud\Core\NameResolver;
use pierresilva\LaravelCrud\MyClass\Data;

class CrudScaffold
{
  private $files;     /* Filesystem */
  private $command;   /* CrudDScaffoldSetupCommand */
  private $data;      /* Data */
  private $module;    /* Module name to generate*/

  //private $app_type;  /* 'web' or 'api' */

  /**
   * Create a new command instance.
   *
   * @param Filesystem $files
   * @param Composer $composer
   */
  public function __construct(Filesystem $files)
  {
    $this->files = $files;
  }

  public function setCommand(CrudSetupCommand $command)
  {
    $this->command = $command;
  }

  public function generate($module, $commandOptions)
  {

    $this->module = $module;

    $this->command->info('Load Data...');
    $this->data = new Data($this->command, $this->files);
    $this->data->loadData();
    $this->command->info('Now Generating...');
    // $this->stubTest();
    // $this->setupProviders();
    if (!$commandOptions['noMigrations']) {
      $this->setupMigration();
    }
    if (!$commandOptions['noSeeders']) {
      $this->setupSeeding();
    }
    if (!$commandOptions['noModels']) {
      $this->setupModel();
    }
    // $this->setupController();
    if (!$commandOptions['noControllers']) {
      $this->setupApiController();
    }
    if (!$commandOptions['noExports']) {
      $this->setupExport();
    }
    // $this->setupViewLayout();
    // $this->setupView();
    // $this->setupRoute();
    if (!$commandOptions['noRoutes']) {
      $this->setupApiRoute();
    }
    // $this->setupFrontMenu();
    // $this->setupFrontRoutes();
    if (!$commandOptions['noFrontend']) {
      $this->setupFrontPage();
    }
    if (!$commandOptions['noAcl']) {
      $this->setupACL();
    }
    /*$this->setupJsScss();*/
  }

  private function stubTest()
  {
    $this->command->info("\n" . 'Testing...');
    $stub_txt = $this->files->get(__DIR__ . '/../Stubs/test3.stub');
    $output_path = base_path() . '/app/test.php';
    $stub_obj = new StubCompiler($stub_txt, $this->data->models[1]->relations[0]);
    $output = $stub_obj->compile();
    $this->files->put($output_path, $output);
    dd('test is end');
  }

  private function setupExport()
  {

    foreach ($this->data->models as $model) {

      if (!$model->module || $this->module !== $model->module) {
        continue;
      }

      $basePath = $model->module ? app_path() . '/Modules/' . $model->module . '/Exports/' : base_path() . '/Exports/';

      if (NameResolver::solveName($model->name, 'NameName') == 'User') {
        continue;
      }

      if ($model->is_pivot) {
        continue;
      }

      $stub_txt = $this->files->get(__DIR__ . '/../Stubs/app/Exports/ModelExport.php.stub');
      $output_folder = $basePath;
      $output_path = $output_folder . '/' . NameResolver::solveName($model->name, 'NameNames') . 'Export.php';
      $stub_obj = new StubCompiler($stub_txt, $model);
      $output = $stub_obj->compile();
      $this->files->put($output_path, $output);

    }

  }

  private function setupACL()
  {

    $moduleNameForCode = Str::slug(NameResolver::solveName($this->module, 'Name Name'), '_');
    $roleAdmin = UserRole::whereCode($moduleNameForCode . '_admin')->first();
    if (!$roleAdmin) {
      $roleAdmin = UserRole::create([
        'name' => NameResolver::solveName($this->module, 'Name Name') . ' Admin',
        'code' => $moduleNameForCode . '_admin',
      ]);
    }

    $roleAdminPermissionIds = [];

    $roleEditor = UserRole::whereCode($moduleNameForCode . '_editor')->first();
    if (!$roleEditor) {
      $roleEditor = UserRole::create([
        'name' => NameResolver::solveName($this->module, 'Name Name') . ' Editor',
        'code' => $moduleNameForCode . '_editor',
      ]);
    }

    $roleEditorPermissionIds = [];

    $roleViewer = UserRole::whereCode($moduleNameForCode . '_viewer')->first();
    if (!$roleViewer) {
      $roleViewer = UserRole::create([
        'name' => NameResolver::solveName($this->module, 'Name Name') . ' Viewer',
        'code' => $moduleNameForCode . '_viewer',
      ]);
    }

    $roleViewerPermissionIds = [];

    foreach ($this->data->models as $model) {

      if (!$model->module || $this->module !== $model->module) {
        continue;
      }

      if (NameResolver::solveName($model->name, 'NameName') == 'User') {
        continue;
      }

      if ($model->is_pivot) {
        continue;
      }

      $modelNameForCode = Str::slug(NameResolver::solveName($model->name, 'Name Names'), '_');

      $permissions = [
        'index',
        'show',
        'store',
        'update',
        'delete',
        'export',
      ];

      $permissionGroup = UserPermissionGroup::where('code', $moduleNameForCode)->first();
      if (!$permissionGroup) {
        $permissionGroup = UserPermissionGroup::create([
          'name' => NameResolver::solveName($this->module, 'Name Name'),
          'code' => $moduleNameForCode
        ]);
      }

      foreach ($permissions as $permission) {
        $permissionName = NameResolver::solveName($model->name, 'Name Names') . ' ' . $permission;
        $permissionCode = $modelNameForCode . '_' . $permission;
        $permissionExists = UserPermission::where('code', $permissionCode)->first();
        if (!$permissionExists) {
          $permissionExists = UserPermission::create([
            'name' => $permissionName,
            'code' => $permissionCode,
            'user_permission_group_id' => $permissionGroup->id
          ]);
        }

        $roleAdminPermissionIds[] = $permissionExists->id;

        if ($permission == 'index') {
          $roleEditorPermissionIds[] = $permissionExists->id;
          $roleViewerPermissionIds[] = $permissionExists->id;
        }

        if ($permission == 'show') {
          $roleEditorPermissionIds[] = $permissionExists->id;
          $roleViewerPermissionIds[] = $permissionExists->id;
        }

        if ($permission == 'store') {
          $roleEditorPermissionIds[] = $permissionExists->id;
        }

        if ($permission == 'update') {
          $roleEditorPermissionIds[] = $permissionExists->id;
        }

        if ($permission == 'export') {
          $roleEditorPermissionIds[] = $permissionExists->id;
          $roleViewerPermissionIds[] = $permissionExists->id;
        }
      }

    }

    $roleAdmin->userPermissions()->sync($roleAdminPermissionIds);
    $roleEditor->userPermissions()->sync($roleEditorPermissionIds);
    $roleViewer->userPermissions()->sync($roleViewerPermissionIds);

  }

  private function setupProviders()
  {
    // app/Providers/AppServiceProvider.php
    $output_path = base_path() . '/app/Providers/AppServiceProvider.php';
    $original_src = $this->files->get($output_path);
    $output = $original_src;

    $add_src = $this->files->get(__DIR__ . '/../Stubs/app/Providers/AppServiceProvider_header.stub');
    $replace_pattern = '/(\/\/ header section)(.*)(\/\/ end header section)/sm';
    $output = preg_replace($replace_pattern, "$1\n" . $add_src . "\n$3", $output);

    $add_src = $this->files->get(__DIR__ . '/../Stubs/app/Providers/AppServiceProvider_function_boot.stub');
    $stub_obj = new StubCompiler($add_src, $this->data);
    $add_src = $stub_obj->compile();

    $replace_pattern = '/(\/\/ boot section)(.*)(\/\/ end boot section)/sm';
    $output = preg_replace($replace_pattern, "$1\n" . $add_src . "\n$3", $output);

    if (!strpos($original_src, $add_src)) {
      $this->files->put($output_path, $output);
    }
  }

  private function setupMigration()
  {

    foreach ($this->data->models as $model) {

      if (!$model->module || $this->module !== $model->module) {
        continue;
      }

      $basePath = $model->module ? app_path() . '/Modules/' . $model->module . '/Database/Migrations/' : base_path() . '/database/migrations';

      if (NameResolver::solveName($model->name, 'NameName') == 'User') {
        continue;
      }

      //table exist check

      $tableName = NameResolver::solveName($model->name, 'name_names');
      if ($model->is_pivot) {
        $tableName = NameResolver::solveName($model->name, 'name_name');
      }

      if (Schema::hasTable($tableName)) {    //table exists

        $conn = Schema::getConnection()->getDoctrineSchemaManager();

        $tableColumns = array_map(function ($key) {
          return $key->getName();

        }, $conn->listTableColumns($tableName));

        $tableColumns = array_values($tableColumns);

        $toRemove = [];
        $toAdd = [];

        foreach ($model->schemas as $schema) {

          if (
            NameResolver::solveName($schema->name, 'name_name') != 'created_at' &&
            NameResolver::solveName($schema->name, 'name_name') != 'deleted_at' &&
            NameResolver::solveName($schema->name, 'name_name') != 'updated_at' &&
            NameResolver::solveName($schema->name, 'name_name') != 'id'
          ) {

            if (!in_array(NameResolver::solveName($schema->name, 'name_name'), $tableColumns)) {
              $toAdd[] = $schema;
            }

          }
        }

        foreach ($tableColumns as $column) {

          if (
            $column != 'created_at' &&
            $column != 'deleted_at' &&
            $column != 'updated_at' &&
            $column != 'id'
          ) {
            if (!$this->findInSchemas($model->schemas, $column)) {
              $toRemove[] = $column;
            }
          }
        }

        $tableMigrationFiles = [];

        foreach ($this->getMigrationFiles($model->module) as $file) {
          if (Str::endsWith($file, NameResolver::solveName($model->name, 'name_names') . '_table')) {
            $tableMigrationFiles[] = $file;
          }

        }
        $countMigrations = count($tableMigrationFiles);

        if (count($toAdd)) {
          foreach ($toAdd as $add) {

            $stub_txt = $this->files->get(__DIR__ . '/../Stubs/database/migrations/yyyy_mm_dd_hhmmss_alter_[number]_add_column_to_[model]_table.stub');

            if ($model->is_pivot) {
              $output_path = $basePath . date('Y_m_d_His') . '_alter_' . $countMigrations . '_add_column_to_' . NameResolver::solveName($model->name, 'name_name') . '_table.php';
            } else {
              $output_path = $basePath . date('Y_m_d_His') . '_alter_' . $countMigrations . '_add_column_to_' . NameResolver::solveName($model->name, 'name_names') . '_table.php';
            }

            $data = new \stdClass();
            $data->id = $model->id;
            $data->name = $model->name;
            $data->display_name = $model->display_name;
            $data->use_soft_delete = $model->use_soft_delete;
            $data->is_pivot = $model->is_pivot;
            $data->schema_id_for_relation = $model->schema_id_for_relation;
            $data->description = null;
            $data->migrationNumber = $countMigrations;
            $data->column = null;
            $data->schemas = [$add];

            $stub_obj = new StubCompiler($stub_txt, $data);
            $output = $stub_obj->compile();
            $this->files->put($output_path, $output);

            $countMigrations++;
          }
        }

        if (count($toRemove)) {
          foreach ($toRemove as $remove) {
            $stub_txt = $this->files->get(__DIR__ . '/../Stubs/database/migrations/yyyy_mm_dd_hhmmss_alter_[number]_remove_column_to_[model]_table.stub');

            if ($model->is_pivot) {
              $output_path = $basePath . date('Y_m_d_His') . '_alter_' . $countMigrations . '_remove_column_to_' . NameResolver::solveName($model->name, 'name_name') . '_table.php';
            } else {
              $output_path = $basePath . date('Y_m_d_His') . '_alter_' . $countMigrations . '_remove_column_to_' . NameResolver::solveName($model->name, 'name_names') . '_table.php';
            }

            $data = new \stdClass();
            $data->id = null;
            $data->name = $model->name;
            $data->display_name = null;
            $data->use_soft_delete = null;
            $data->is_pivot = false;
            $data->schema_id_for_relation = null;
            $data->description = null;
            $data->migrationNumber = $countMigrations;
            $data->column = $remove;

            $stub_obj = new StubCompiler($stub_txt, $data);
            $output = $stub_obj->compile();
            $this->files->put($output_path, $output);

            $countMigrations++;
          }
        }

        // throw new \Exception('[' . NameResolver::solveName($model->name, 'name_names') . '] table is already exists. migrate:rollback and delete migration files');
      } else {
        //create migration file

        $stub_txt = $this->files->get(__DIR__ . '/../Stubs/database/migrations/yyyy_mm_dd_hhmmss_create_[model]_table.stub');

        if ($model->is_pivot) {
          $output_path = $basePath . date('Y_m_d_His') . '_create_' . NameResolver::solveName($model->name, 'name_name') . '_table.php';
        } else {
          $output_path = $basePath . date('Y_m_d_His') . '_create_' . NameResolver::solveName($model->name, 'name_names') . '_table.php';
        }
        $stub_obj = new StubCompiler($stub_txt, $model);
        $output = $stub_obj->compile();
        $this->files->put($output_path, $output);
      }
    }
  }

  public function getMigrationFiles($module)
  {
    $filesInFolder = \File::files(database_path('migrations'));
    if ($module) {
      $filesInFolder = \File::files(app_path('Modules/' . $module . '/Database/Migrations'));
    }
    $files = [];
    foreach ($filesInFolder as $path) {
      $file = pathinfo($path);
      $files[] = $file['filename'];
    }

    return $files;
  }

  public function findInSchemas($modelSchemas, $schemaName)
  {

    foreach ($modelSchemas as $schema) {

      if (NameResolver::solveName($schema->name, 'name_name') == $schemaName) {
        return true;
      }
    }

    return false;
  }

  private function setupSeeding()
  {

    // dd($this->data->models);

    foreach ($this->data->models as $model) {

      if (!$model->module || $this->module !== $model->module) {
        continue;
      }

      $basePath = $model->module ? app_path() . '/Modules/' . $model->module . '/Database/Seeds/' : base_path() . '/database/seeds';

      if ($model->name == 'comment') {
        // dd('comment', $model->schemas);
      }

      // (i) /database/seeds/DatabaseSeeder.php
      $stub_txt = $this->files->get(__DIR__ . '/../Stubs/database/seeds/DatabaseSeeder_add.stub');
      $output_path = $basePath . '/ModuleDatabaseSeeder.php';
      $stub_obj = new StubCompiler($stub_txt, $model);
      $add_src = $stub_obj->compile();

      $original_src = $this->files->get($basePath . '/ModuleDatabaseSeeder.php');
      $replace_pattern = '#(public function run\(\)\s*\{)([^\}]*)(    \})#';
      $output = preg_replace($replace_pattern, '$1$2' . $add_src . '$3', $original_src);

      if (!strpos($original_src, $add_src)) {
        $this->files->put($output_path, $output);
      }

      // (ii) /database/seeds/[Models]TableSeeder.php
      $stub_txt = $this->files->get(__DIR__ . '/../Stubs/database/seeds/[Models]TableSeeder.stub');
      if ($model->is_pivot) {
        $output_path = $basePath . NameResolver::solveName($model->name, 'NameName') . 'TableSeeder.php';
      } else {
        $output_path = $basePath . NameResolver::solveName($model->name, 'NameNames') . 'TableSeeder.php';
      }
      $stub_obj = new StubCompiler($stub_txt, $model);
      $output = $stub_obj->compile();

      //overwrite check
      /*if (!$this->command->option('force')) {   // no check if force option is selected
          if ($this->files->exists($output_path)) {
              continue;
          }
      }*/
      $this->files->put($output_path, $output);
    }
  }


  private function setupModel()
  {

    foreach ($this->data->models as $model) {

      if (!$model->module || $this->module !== $model->module) {
        continue;
      }

      $basePath = $model->module ? app_path() . '/Modules/' . $model->module . '/Models/' : app_path() . '/Models/';

      if ($model->is_pivot) {
        continue;
      }

      // case using laravel auth
      /*if ($this->data->use_laravel_auth === true && $model->name === "user") {

          $output_path = base_path() . '/app/User.php';
          $original_src = $this->files->get($output_path);
          $output = $original_src;

          $stub_txt = $this->files->get(__DIR__ . '/../Stubs/app/Auth/User01_use.stub');
          $replace_pattern = '#(class User)#';
          if (!strpos($original_src, $stub_txt)) {
              $output = preg_replace($replace_pattern, $stub_txt . '$1', $output);
          }

          $stub_txt = $this->files->get(__DIR__ . '/../Stubs/app/Auth/User02_use.stub');
          $replace_pattern = '#(use Notifiable;)#';
          if (!strpos($original_src, $stub_txt)) {
              $output = preg_replace($replace_pattern, '$1' . $stub_txt, $output);
          }

          $stub_txt = $this->files->get(__DIR__ . '/../Stubs/app/Auth/User03_mass_assignment.stub');
          $replace_pattern = '#(\'name\', \'email\', \'password\',)#';
          if (!strpos($original_src, $stub_txt)) {
              $output = preg_replace($replace_pattern, '$1' . $stub_txt, $output);
          }

          $stub_txt = $this->files->get(__DIR__ . '/../Stubs/app/Auth/User04_others.stub');
          $replace_pattern = '#(\}\s*)$#';
          if (!strpos($original_src, $stub_txt)) {
              $output = preg_replace($replace_pattern, $stub_txt . '$1', $output);
          }

          $stub_obj = new StubCompiler($output, $model);
          $output = $stub_obj->compile();

          $this->files->put($output_path, $output);

      } else {*/

      //create model file
      $stub_txt = $this->files->get(__DIR__ . '/../Stubs/app/Models/[Model].stub');
      $output_path = $basePath . NameResolver::solveName($model->name, 'NameName') . '.php';
      $stub_obj = new StubCompiler($stub_txt, $model);
      $output = $stub_obj->compile();

      //overwrite check
      if ($this->files->exists($output_path)) {

        unset($output);

        $original_src = $this->files->get($output_path);
        $replace_pattern = '/(\/\/ generated section)(.*)(\/\/ end section)/sm';

        $add_src = $this->files->get(__DIR__ . '/../Stubs/app/Models/[Model]_exists.stub');
        $stub_obj = new StubCompiler($add_src, $model);
        $add_src = $stub_obj->compile();

        preg_match($replace_pattern, $original_src, $matches);

        $output = preg_replace($replace_pattern, "$1\n\n" . $add_src . "\n\n$3", $original_src);

      }

      if (!$this->command->option('force')) {   // no check if force option is selected
        if ($this->files->exists($output_path)) {
          continue;
        }
      }

      $this->files->put($output_path, $output);
//            }
    }

  }


  private function setupController()
  {

    foreach ($this->data->models as $model) {

      $basePath = $model->module ? app_path() . '/Modules/' . $model->module . '/Http/Controllers/' : app_path() . '/Http/Controllers/';

      if ($model->is_pivot) {
        continue;
      }

      //create controller file
      $stub_txt = $this->files->get(__DIR__ . '/../Stubs/app/Http/Controllers/[Model]Controller.stub');
      $output_path = $basePath . NameResolver::solveName($model->name, 'NameName') . 'Controller.php';
      $stub_obj = new StubCompiler($stub_txt, $model);
      $output = $stub_obj->compile();

      //overwrite check
      if (!$this->command->option('force')) {
        if ($this->files->exists($output_path)) {
          continue;
        }
      }
      $this->files->put($output_path, $output);
    }
  }

  private function setupApiController()
  {

    foreach ($this->data->models as $model) {

      if (!$model->module || $this->module !== $model->module) {
        continue;
      }

      $basePath = $model->module ? app_path() . '/Modules/' . $model->module . '/Http/Controllers/Api/' : app_path() . '/Http/Controllers/Api/';

      if ($model->is_pivot) {
        continue;
      }

      //create controller file
      $stub_txt = $this->files->get(__DIR__ . '/../Stubs/app/Http/Controllers/Api/[Model]ApiController.stub');
      $output_path = $basePath . NameResolver::solveName($model->name, 'NameName') . 'Controller.php';
      $stub_obj = new StubCompiler($stub_txt, $model);
      $output = $stub_obj->compile();

      //overwrite check
      if ($this->files->exists($output_path)) {
        unset($output);

        $original_src = $this->files->get($output_path);
        $replace_pattern = '/(\/\/ generated section)(.*)(\/\/ end section)/sm';

        $add_src = $this->files->get(__DIR__ . '/../Stubs/app/Http/Controllers/Api/[Model]ApiController_exists.stub');
        $stub_obj = new StubCompiler($add_src, $model);
        $add_src = $stub_obj->compile();

        preg_match($replace_pattern, $original_src, $matches);

        $output = preg_replace($replace_pattern, "$1\n\n" . $add_src . "\n\n$3", $original_src);
      }

      //overwrite check
      if (!$this->command->option('force')) {
        if ($this->files->exists($output_path)) {
          continue;
        }
      }

      $this->files->put($output_path, $output);
    }
  }


  private function setupViewLayout()
  {

    //(i)layout --------------------------------------------------

    $stub_txt = $this->files->get(__DIR__ . '/../Stubs/resources/views/layouts/de_app.blade.stub');
    $output_folder = base_path() . '/resources/views/layouts';
    $output_path = $output_folder . '/de_app.blade.php';

    if (!$this->files->exists($output_folder)) {
      $this->files->makeDirectory($output_folder);
    }
    $this->files->put($output_path, $stub_txt);

    //(ii)alert --------------------------------------------------

    // $stub_txt = $this->files->get( __DIR__. '/../Stubs/resources/views/_common/alert.blade.stub');
    // $output_dir = base_path().'/resources/views/_common/';
    // $output_path = $output_dir.'alert.blade.php';

    // //overwrite check
    // if( !$this->command->option('force') ){
    //     if( $this->files->exists($output_path) ){
    //         throw new \Exception("Controller File is already exists![".$output_path."]");
    //     }
    // }

    // //create directory
    // if( !$this->files->exists($output_dir) ){
    //     $this->files->makeDirectory( $output_dir, $mode = 493, $recursive = false, $force = false);
    // }
    // $this->files->put($output_path, $stub_txt );

    //(iii)navi --------------------------------------------------

    $setting_array = $this->data;

    // check auth scaffold is done
    /*
            if( $this->checkAuthScaffold() ){
                $setting_array['auth'] = "true";
            }
    */
    $stub_txt = $this->files->get(__DIR__ . '/../Stubs/resources/views/layouts/de_navi.blade.stub');
    $output_path = base_path() . '/resources/views/layouts/de_navi.blade.php';
    $stub_obj = new StubCompiler($stub_txt, $setting_array);
    $output = $stub_obj->compile();

    $this->files->put($output_path, $output);

    //(iv)authview --------------------------------------------------

    // check auth scaffold is done
    if ($this->checkAuthScaffold()) {
      /* later
                  $original_path_array = [
                      base_path().'/resources/views/home.blade.php',
                      base_path().'/resources/views/auth/login.blade.php',
                      base_path().'/resources/views/auth/register.blade.php',
                      base_path().'/resources/views/auth/passwords/email.blade.php',
                      base_path().'/resources/views/auth/passwords/reset.blade.php'
                  ];

                  foreach( $original_path_array as $original_path ){
                      $original_src = $this->files->get( $original_path );
                      $replaced_src = str_replace( "@extends('layouts.app')", "@extends('layout')", $original_src );

                      //overwrite check
                      if( !$this->command->option('force') ){
                          if( $this->files->exists($original_path) ){
                              throw new \Exception("Controller File is already exists![".$original_path."]");
                          }
                      }

                      $this->files->put( $original_path, $replaced_src );
                  }
      */
    }
  }


  private function checkAuthScaffold()
  {
    if ($this->files->exists(base_path() . '/resources/views/auth/login.blade.php')) {
      return true;
    } else {
      return false;
    }
  }


  private function setupView()
  {

    $view_filename_array = ['_form.blade', '_table.blade', 'create.blade', 'duplicate.blade', 'edit.blade', 'index.blade', 'show.blade'];

//        $data = [];
//        $dataCnt = 0;
//        foreach( $this->data->models as $model ){
//            $data[$dataCnt]['model'] = [
//                'name' => $model->name,
//                'display_name' => $model->display_name
//            ];
//
//            $relationsCnt = 0;
//            foreach ($model->relations as $relation) {
//                $data[$dataCnt]['relations'][$relationsCnt]['name'] = $relation->targetModel->name;
//                $data[$dataCnt]['relations'][$relationsCnt]['type'] = $relation->type;
//                $data[$dataCnt]['relations'][$relationsCnt]['display_name'] = $relation->targetModel->display_name;
//                $relationsCnt++;
//            }
//            $dataCnt++;
//        }
//
//        dd($data);

    foreach ($this->data->models as $model) {

      if ($model->name === 'user' && $this->data->use_laravel_auth === true) {
        /* later
                        $output_path = base_path().'/resources/views/auth/register.blade.php';
                        $original_src = $this->files->get( $output_path );
                        $output = $original_src;

                        $stub_txt = $this->files->get( __DIR__. '/../Stubs/resources/views/auth/register_add.stub');
                        $replace_pattern = '#(.*)(<div class="form-group">)(.*?)(Register)#s';
                        $output = preg_replace ( $replace_pattern, '$1'.$stub_txt.'$2$3$4', $output );

                        $stub_obj = new StubCompiler( $output, $model );
                        $output = $stub_obj->compile();

                        $this->files->put($output_path, $output );
        */
      }

      foreach ($view_filename_array as $view_filename) {
        $stub_txt = $this->files->get(__DIR__ . '/../Stubs/resources/views/[models]/' . $view_filename . '.stub');
        $output_dir = base_path() . '/resources/views/' . NameResolver::solveName($model->name, 'nameNames') . '/';
        $output_filename = $view_filename . '.php';
        $output_path = $output_dir . $output_filename;
        $stub_obj = new StubCompiler($stub_txt, $model);
        $output = $stub_obj->compile();

        //overwrite check
        if (!$this->command->option('force')) {
          if ($this->files->exists($output_path)) {
            continue;
          }
        }

        //create directory
        if (!$this->files->exists($output_dir)) {
          $this->files->makeDirectory($output_dir, $mode = 493, $recursive = false, $force = false);
        }
        $this->files->put($output_path, $output);
      }
    }
  }


  private function setupRoute()
  {

    $stub_txt = $this->files->get(__DIR__ . '/../Stubs/routes/web_add.stub');
    $output_path = base_path() . '/routes/web.php';
    $stub_obj = new StubCompiler($stub_txt, $this->data);
    $output = $stub_obj->compile();
    $target_src = $this->files->get(base_path() . '/routes/web.php');

    $replace_pattern = '/(\/\/ generated section)(.*)(\/\/ end section)/sm';

    $output = preg_replace($replace_pattern, "$1\n\n" . $output . "\n\n$3", $target_src);

    if (!strpos($target_src, $output)) {
      $this->files->put($output_path, $output);
    }
  }

  private function setupApiRoute()
  {
    $routesString = '';
    $routeString = "
Route::get('{{{slug}}}/export', ['as' => 'api.{{{slug}}}.export', 'uses' => 'Api\{{{model}}}Controller@export']);
Route::put('{{{slug}}}/delete-multiple', ['as' => 'api.{{{slug}}}.delete-multiple', 'uses' => 'Api\{{{model}}}Controller@destroyMultiple']);
Route::get('{{{slug}}}/{id}', ['as' => 'api.{{{slug}}}.show', 'uses' => 'Api\{{{model}}}Controller@show']);
Route::get('{{{slug}}}', ['as' => 'api.{{{slug}}}.index', 'uses' => 'Api\{{{model}}}Controller@index']);
Route::post('{{{slug}}}', ['as' => 'api.{{{slug}}}.store', 'uses' => 'Api\{{{model}}}Controller@store']);
Route::put('{{{slug}}}/{id}', ['as' => 'api.{{{slug}}}.update', 'uses' => 'Api\{{{model}}}Controller@update']);
Route::delete('{{{slug}}}/{id}', ['as' => 'api.{{{slug}}}.destroy', 'uses' => 'Api\{{{model}}}Controller@destroy']);
    ";

    foreach ($this->data->models as $model) {

      if (!$model->module || $this->module !== $model->module) {
        continue;
      }

      $slug = Str::slug(preg_replace('/(?<=\\w)(?=[A-Z])/'," $1", Str::plural($model->name)));
      $modelName = Str::ucfirst($model->name);

      $thisRouteString = str_replace('{{{slug}}}', $slug, $routeString);
      $thisRouteString = str_replace('{{{model}}}', Str::slug($modelName), $thisRouteString);

      $routesString .= $thisRouteString;

    }

    $stub_txt = $routesString; // $this->files->get(__DIR__ . '/../Stubs/routes/api_add.stub');
    $output_path = $this->module ? app_path() . '/Modules/' . $this->module . '/Routes/api.php' : base_path() . '/routes/api.php';
    $stub_obj = new StubCompiler($stub_txt, $this->data);
    $output = $routesString; // $stub_obj->compile();
    $target_src = $this->files->get($this->module ? app_path() . '/Modules/' . $this->module . '/Routes/api.php' : base_path() . '/routes/api.php');

    $replace_pattern = '/(# generated section)(.*)(# end generated section)/sm';

    $output = preg_replace($replace_pattern, "$1\n\n" . $output . "\n\n$3", $target_src);

    if (!strpos($target_src, $output)) {
      $this->files->put($output_path, $output);
    }
  }

  private function setupFrontMenu()
  {

    $stub_txt = $this->files->get(__DIR__ . '/../Stubs/src/app/add_menu.stub');
    $output_path = base_path() . '/src/app/app.component.html';
    $stub_obj = new StubCompiler($stub_txt, $this->data);
    $output = $stub_obj->compile();
    $target_src = $this->files->get(base_path() . '/src/app/app.component.html');

    $replace_pattern = '/(<!-- generated section -->)(.*)(<!-- end section -->)/sm';

    $output = preg_replace($replace_pattern, "$1\n\n" . $output . "\n\n$3", $target_src);

    if (!strpos($target_src, $output)) {
      $this->files->put($output_path, $output);
    }
  }

  public function setupFrontPage()
  {

    foreach ($this->data->models as $model) {

      if (!$model->module || $this->module !== $model->module) {
        continue;
      }

      if (!$model->is_pivot && $model->name != '') {

        $output_dir = base_path() . '/angular/app/admin/' . NameResolver::solveName($model->name, 'name-names');

        //create directory
        if (!$this->files->exists($output_dir)) {
          $this->files->makeDirectory($output_dir, $mode = 0777, $recursive = true, $force = true);
        }

        /*if (!$this->files->exists($output_dir . '/' . NameResolver::solveName($model->name, 'name-names') . '-list')) {
            $this->files->makeDirectory($output_dir . '/' . NameResolver::solveName($model->name, 'name-names') . '-list', $mode = 0777, $recursive = false, $force = false);
        }

        // routing
        $stub_txt = $this->files->get(__DIR__ . '/../Stubs/src/app/page/routing_ts.stub');
        $output_filename = NameResolver::solveName($model->name, 'name-names') . '-routing.module.ts';
        $output_path = $output_dir . '/' . $output_filename;

        $stub_obj = new StubCompiler($stub_txt, $model);
        $output = $stub_obj->compile();

        // overwrite check
        if (!$this->files->exists($output_path)) {
            $this->files->put($output_path, $output);
        }
        if ($this->files->exists($output_path) && $this->command->option('force')) {
            $this->files->put($output_path, $output);
        }*/


        // component module
        $stub_txt = $this->files->get(__DIR__ . '/../Stubs/src/app/module/model.component.ts.stub');
        $output_filename = NameResolver::solveName($model->name, 'name-names') . '.component.ts';
        $output_path = $output_dir . '/' . $output_filename;
        $stub_obj = new StubCompiler($stub_txt, $model);
        $output = $stub_obj->compile();

        // overwrite check
        if (!$this->files->exists($output_path)) {
          $this->files->put($output_path, $output);
        }
        if ($this->files->exists($output_path) && $this->command->option('force')) {
          $this->files->put($output_path, $output);
        }

        // html module

        $stub_txt = $this->files->get(__DIR__ . '/../Stubs/src/app/module/model.component.html.stub');
        $output_filename = NameResolver::solveName($model->name, 'name-names') . '.component.html';
        $output_path = $output_dir . '/' . $output_filename;
        $stub_obj = new StubCompiler($stub_txt, $model);
        $output = $stub_obj->compile();

        // overwrite check
        if (!$this->files->exists($output_path)) {
          $this->files->put($output_path, $output);
        }
        if ($this->files->exists($output_path) && $this->command->option('force')) {
          $this->files->put($output_path, $output);
        }

        // css module

        $stub_txt = $this->files->get(__DIR__ . '/../Stubs/src/app/module/model.component.scss.stub');
        $output_filename = NameResolver::solveName($model->name, 'name-names') . '.component.scss';
        $output_path = $output_dir . '/' . $output_filename;
        $stub_obj = new StubCompiler($stub_txt, $model);
        $output = $stub_obj->compile();

        // overwrite check
        if (!$this->files->exists($output_path)) {
          $this->files->put($output_path, $output);
        }
        if ($this->files->exists($output_path) && $this->command->option('force')) {
          $this->files->put($output_path, $output);
        }

        // module
        $stub_txt = $this->files->get(__DIR__ . '/../Stubs/src/app/module/model.module.ts.stub');
        $output_filename = NameResolver::solveName($model->name, 'name-names') . '.module.ts';
        $output_path = $output_dir . '/' . $output_filename;
        $stub_obj = new StubCompiler($stub_txt, $model);
        $output = $stub_obj->compile();

        // overwrite check
        if (!$this->files->exists($output_path)) {
          $this->files->put($output_path, $output);
        }
        if ($this->files->exists($output_path) && $this->command->option('force')) {
          $this->files->put($output_path, $output);
        }

        // module routing

        $stub_txt = $this->files->get(__DIR__ . '/../Stubs/src/app/module/model-routing.module.ts.stub');
        $output_filename = NameResolver::solveName($model->name, 'name-names') . '-routing.module.ts';
        $output_path = $output_dir . '/' . $output_filename;
        $stub_obj = new StubCompiler($stub_txt, $model);
        $output = $stub_obj->compile();

        // overwrite check
        if (!$this->files->exists($output_path)) {
          $this->files->put($output_path, $output);
        }
        if ($this->files->exists($output_path) && $this->command->option('force')) {
          $this->files->put($output_path, $output);
        }

        // model

        if (!$this->files->exists($output_dir . '/../../@shared/interfaces')) {
          $this->files->makeDirectory($output_dir . '/../../@shared/interfaces', $mode = 0777, $recursive = false, $force = false);
        }

        $stub_txt = $this->files->get(__DIR__ . '/../Stubs/src/app/@shared/interfaces/model.ts.stub');
        $output_filename = NameResolver::solveName($model->name, 'name-name') . '.ts';
        $output_path = $output_dir . '/../../@shared/interfaces/' . $output_filename;
        $stub_obj = new StubCompiler($stub_txt, $model);
        $output = $stub_obj->compile();

        // overwrite check
        if (!$this->files->exists($output_path)) {
          $this->files->put($output_path, $output);
        }
        if ($this->files->exists($output_path) && $this->command->option('force')) {
          $this->files->put($output_path, $output);
        }


        // service

        //create directory
        if (!$this->files->exists($output_dir . '/services')) {
          $this->files->makeDirectory($output_dir . '/services', $mode = 0777, $recursive = false, $force = false);
        }

        $stub_txt = $this->files->get(__DIR__ . '/../Stubs/src/app/module/services/model.service.ts.stub');
        $output_filename = NameResolver::solveName($model->name, 'name-name') . '.service.ts';
        $output_path = $output_dir . '/services/' . $output_filename;
        $stub_obj = new StubCompiler($stub_txt, $model);
        $output = $stub_obj->compile();

        // overwrite check
        if (!$this->files->exists($output_path)) {
          $this->files->put($output_path, $output);
        }
        if ($this->files->exists($output_path) && $this->command->option('force')) {
          $this->files->put($output_path, $output);
        }

        //create directory
        if (!$this->files->exists($output_dir . '/components')) {
          $this->files->makeDirectory($output_dir . '/components', $mode = 0777, $recursive = false, $force = false);
        }

        // list

        //create directory
        if (!$this->files->exists($output_dir . '/components/' . NameResolver::solveName($model->name, 'name-names') . '-list')) {
          $this->files->makeDirectory($output_dir . '/components/' . NameResolver::solveName($model->name, 'name-names') . '-list', $mode = 0777, $recursive = false, $force = false);
        }

        // list html

        $stub_txt = $this->files->get(__DIR__ . '/../Stubs/src/app/module/components/model-list/model-list.component.html.stub');
        $output_filename = NameResolver::solveName($model->name, 'name-names') . '-list.component.html';
        $output_path = $output_dir . '/components/' . NameResolver::solveName($model->name, 'name-names') . '-list/' . $output_filename;
        $stub_obj = new StubCompiler($stub_txt, $model);
        $output = $stub_obj->compile();

        // overwrite check
        if (!$this->files->exists($output_path)) {
          $this->files->put($output_path, $output);
        }
        if ($this->files->exists($output_path) && $this->command->option('force')) {
          $this->files->put($output_path, $output);
        }

        // list ts
        $stub_txt = $this->files->get(__DIR__ . '/../Stubs/src/app/module/components/model-list/model-list.component.ts.stub');
        $output_filename = NameResolver::solveName($model->name, 'name-names') . '-list.component.ts';
        $output_path = $output_dir . '/components/' . NameResolver::solveName($model->name, 'name-names') . '-list/' . $output_filename;
        $stub_obj = new StubCompiler($stub_txt, $model);
        $output = $stub_obj->compile();

        // overwrite check
        if (!$this->files->exists($output_path)) {
          $this->files->put($output_path, $output);
        }
        if ($this->files->exists($output_path) && $this->command->option('force')) {
          $this->files->put($output_path, $output);
        }

        // list scss
        $stub_txt = $this->files->get(__DIR__ . '/../Stubs/src/app/module/components/model-list/model-list.component.scss.stub');
        $output_filename = NameResolver::solveName($model->name, 'name-names') . '-list.component.scss';
        $output_path = $output_dir . '/components/' . NameResolver::solveName($model->name, 'name-names') . '-list/' . $output_filename;
        $stub_obj = new StubCompiler($stub_txt, $model);
        $output = $stub_obj->compile();

        // overwrite check
        if (!$this->files->exists($output_path)) {
          $this->files->put($output_path, $output);
        }
        if ($this->files->exists($output_path) && $this->command->option('force')) {
          $this->files->put($output_path, $output);
        }

        // form

        // form directory
        if (!$this->files->exists($output_dir . '/components/' . NameResolver::solveName($model->name, 'name-names') . '-form')) {
          $this->files->makeDirectory($output_dir . '/components/' . NameResolver::solveName($model->name, 'name-names') . '-form', $mode = 0777, $recursive = false, $force = false);
        }

        // form html
        $stub_txt = $this->files->get(__DIR__ . '/../Stubs/src/app/module/components/model-form/model-form.component.html.stub');
        $output_filename = NameResolver::solveName($model->name, 'name-names') . '-form.component.html';
        $output_path = $output_dir . '/components/' . NameResolver::solveName($model->name, 'name-names') . '-form/' . $output_filename;
        $stub_obj = new StubCompiler($stub_txt, $model);
        $output = $stub_obj->compile();

        // overwrite check
        if (!$this->files->exists($output_path)) {
          $this->files->put($output_path, $output);
        }
        if ($this->files->exists($output_path) && $this->command->option('force')) {
          $this->files->put($output_path, $output);
        }

        // form ts
        $stub_txt = $this->files->get(__DIR__ . '/../Stubs/src/app/module/components/model-form/model-form.component.ts.stub');
        $output_filename = NameResolver::solveName($model->name, 'name-names') . '-form.component.ts';
        $output_path = $output_dir . '/components/' . NameResolver::solveName($model->name, 'name-names') . '-form/' . $output_filename;
        $stub_obj = new StubCompiler($stub_txt, $model);
        $output = $stub_obj->compile();

        // overwrite check
        if (!$this->files->exists($output_path)) {
          $this->files->put($output_path, $output);
        }
        if ($this->files->exists($output_path) && $this->command->option('force')) {
          $this->files->put($output_path, $output);
        }

        // form scss
        $stub_txt = $this->files->get(__DIR__ . '/../Stubs/src/app/module/components/model-form/model-form.component.scss.stub');
        $output_filename = NameResolver::solveName($model->name, 'name-names') . '-form.component.scss';
        $output_path = $output_dir . '/components/' . NameResolver::solveName($model->name, 'name-names') . '-form/' . $output_filename;
        $stub_obj = new StubCompiler($stub_txt, $model);
        $output = $stub_obj->compile();

        // overwrite check
        if (!$this->files->exists($output_path)) {
          $this->files->put($output_path, $output);
        }
        if ($this->files->exists($output_path) && $this->command->option('force')) {
          $this->files->put($output_path, $output);
        }

        // sidenav

        // sidenav directory
        if (!$this->files->exists($output_dir . '/components/' . NameResolver::solveName($model->name, 'name-names') . '-sidenav')) {
          $this->files->makeDirectory($output_dir . '/components/' . NameResolver::solveName($model->name, 'name-names') . '-sidenav', $mode = 0777, $recursive = false, $force = false);
        }

        // sidenav html
        $stub_txt = $this->files->get(__DIR__ . '/../Stubs/src/app/module/components/model-sidenav/model-sidenav.component.html.stub');
        $output_filename = NameResolver::solveName($model->name, 'name-names') . '-sidenav.component.html';
        $output_path = $output_dir . '/components/' . NameResolver::solveName($model->name, 'name-names') . '-sidenav/' . $output_filename;
        $stub_obj = new StubCompiler($stub_txt, $model);
        $output = $stub_obj->compile();

        // overwrite check
        if (!$this->files->exists($output_path)) {
          $this->files->put($output_path, $output);
        }
        if ($this->files->exists($output_path) && $this->command->option('force')) {
          $this->files->put($output_path, $output);
        }

        // sidenav ts
        $stub_txt = $this->files->get(__DIR__ . '/../Stubs/src/app/module/components/model-sidenav/model-sidenav.component.ts.stub');
        $output_filename = NameResolver::solveName($model->name, 'name-names') . '-sidenav.component.ts';
        $output_path = $output_dir . '/components/' . NameResolver::solveName($model->name, 'name-names') . '-sidenav/' . $output_filename;
        $stub_obj = new StubCompiler($stub_txt, $model);
        $output = $stub_obj->compile();

        // overwrite check
        if (!$this->files->exists($output_path)) {
          $this->files->put($output_path, $output);
        }
        if ($this->files->exists($output_path) && $this->command->option('force')) {
          $this->files->put($output_path, $output);
        }

        // sidenav scss
        $stub_txt = $this->files->get(__DIR__ . '/../Stubs/src/app/module/components/model-sidenav/model-sidenav.component.scss.stub');
        $output_filename = NameResolver::solveName($model->name, 'name-names') . '-sidenav.component.scss';
        $output_path = $output_dir . '/components/' . NameResolver::solveName($model->name, 'name-names') . '-sidenav/' . $output_filename;
        $stub_obj = new StubCompiler($stub_txt, $model);
        $output = $stub_obj->compile();

        // overwrite check
        if (!$this->files->exists($output_path)) {
          $this->files->put($output_path, $output);
        }
        if ($this->files->exists($output_path) && $this->command->option('force')) {
          $this->files->put($output_path, $output);
        }

        /* // form
        if (!$this->files->exists($output_dir . '/' . NameResolver::solveName($model->name, 'name-names') . '-form')) {
            $this->files->makeDirectory($output_dir . '/' . NameResolver::solveName($model->name, 'name-names') . '-form', $mode = 0777, $recursive = false, $force = false);
        }
        // form html
        $stub_txt = $this->files->get(__DIR__ . '/../Stubs/src/app/page/form/html.stub');
        $output_filename = NameResolver::solveName($model->name, 'name-names') . '-form.component.html';
        $output_path = $output_dir . '/' . NameResolver::solveName($model->name, 'name-names') . '-form/' . $output_filename;
        $stub_obj = new StubCompiler($stub_txt, $model);
        $output = $stub_obj->compile();

        // overwrite check
        if (!$this->files->exists($output_path)) {
            $this->files->put($output_path, $output);
        }
        if ($this->files->exists($output_path) && $this->command->option('force')) {
            $this->files->put($output_path, $output);
        }

        // form ts
        $stub_txt = $this->files->get(__DIR__ . '/../Stubs/src/app/page/form/ts.stub');
        $output_filename = NameResolver::solveName($model->name, 'name-names') . '-form.component.ts';
        $output_path = $output_dir . '/' . NameResolver::solveName($model->name, 'name-names') . '-form/' . $output_filename;
        $stub_obj = new StubCompiler($stub_txt, $model);
        $output = $stub_obj->compile();

        // overwrite check
        if (!$this->files->exists($output_path)) {
            $this->files->put($output_path, $output);
        }
        if ($this->files->exists($output_path) && $this->command->option('force')) {
            $this->files->put($output_path, $output);
        }

        // form scss
        $stub_txt = $this->files->get(__DIR__ . '/../Stubs/src/app/page/form/scss.stub');
        $output_filename = NameResolver::solveName($model->name, 'name-names') . '-form.component.scss';
        $output_path = $output_dir . '/' . NameResolver::solveName($model->name, 'name-names') . '-form/' . $output_filename;
        $stub_obj = new StubCompiler($stub_txt, $model);
        $output = $stub_obj->compile();

        // overwrite check
        if (!$this->files->exists($output_path)) {
            $this->files->put($output_path, $output);
        }
        if ($this->files->exists($output_path) && $this->command->option('force')) {
            $this->files->put($output_path, $output);
        }

        // duplicate
        if (!$this->files->exists($output_dir . '/' . NameResolver::solveName($model->name, 'name-names') . '-duplicate')) {
            $this->files->makeDirectory($output_dir . '/' . NameResolver::solveName($model->name, 'name-names') . '-duplicate', $mode = 0777, $recursive = false, $force = false);
        }
        // duplicate html
        $stub_txt = $this->files->get(__DIR__ . '/../Stubs/src/app/page/duplicate/html.stub');
        $output_filename = NameResolver::solveName($model->name, 'name-names') . '-duplicate.component.html';
        $output_path = $output_dir . '/' . NameResolver::solveName($model->name, 'name-names') . '-duplicate/' . $output_filename;
        $stub_obj = new StubCompiler($stub_txt, $model);
        $output = $stub_obj->compile();

        // overwrite check
        if (!$this->files->exists($output_path)) {
            $this->files->put($output_path, $output);
        }
        if ($this->files->exists($output_path) && $this->command->option('force')) {
            $this->files->put($output_path, $output);
        }

        // duplicate ts
        $stub_txt = $this->files->get(__DIR__ . '/../Stubs/src/app/page/duplicate/ts.stub');
        $output_filename = NameResolver::solveName($model->name, 'name-names') . '-duplicate.component.ts';
        $output_path = $output_dir . '/' . NameResolver::solveName($model->name, 'name-names') . '-duplicate/' . $output_filename;
        $stub_obj = new StubCompiler($stub_txt, $model);
        $output = $stub_obj->compile();

        // overwrite check
        if (!$this->files->exists($output_path)) {
            $this->files->put($output_path, $output);
        }
        if ($this->files->exists($output_path) && $this->command->option('force')) {
            $this->files->put($output_path, $output);
        }

        // duplicate scss
        $stub_txt = $this->files->get(__DIR__ . '/../Stubs/src/app/page/duplicate/scss.stub');
        $output_filename = NameResolver::solveName($model->name, 'name-names') . '-duplicate.component.scss';
        $output_path = $output_dir . '/' . NameResolver::solveName($model->name, 'name-names') . '-duplicate/' . $output_filename;
        $stub_obj = new StubCompiler($stub_txt, $model);
        $output = $stub_obj->compile();

        // overwrite check
        if (!$this->files->exists($output_path)) {
            $this->files->put($output_path, $output);
        }
        if ($this->files->exists($output_path) && $this->command->option('force')) {
            $this->files->put($output_path, $output);
        }*/
      }

    }

  }

  private function setupFrontRoutes()
  {

    $stub_txt = $this->files->get(__DIR__ . '/../Stubs/src/app/add_routes.stub');
    $output_path = base_path() . '/src/app/app-routing.module.ts';
    $stub_obj = new StubCompiler($stub_txt, $this->data);
    $output = $stub_obj->compile();
    $target_src = $this->files->get(base_path() . '/src/app/app-routing.module.ts');

    $replace_pattern = '/(\/\/ generated section)(.*)(\/\/ end section)/sm';

    $output = preg_replace($replace_pattern, "$1\n\n" . $output . "\n\n$3", $target_src);

    if (!strpos($target_src, $output)) {
      $this->files->put($output_path, $output);
    }
  }


  private function setupJsScss()
  {

    // js - cleanQuery
    $this->files->copy(__DIR__ . '/../Stubs/resources/js/jquery.cleanQuery.js', base_path() . '/resources/js/jquery.cleanQuery.js');
    // js - dy.js
    $this->files->copy(__DIR__ . '/../Stubs/resources/js/dog-ears.js', base_path() . '/resources/js/dog-ears.js');
    // sass - dy.scss
    $this->files->copy(__DIR__ . '/../Stubs/resources/sass/_dog-ears.scss', base_path() . '/resources/sass/_dog-ears.scss');

    // js - app.js
    $output = $this->files->get(__DIR__ . '/../Stubs/resources/js/app_add.js.stub');
    $output_path = base_path() . '/resources/js/app.js';
    $target_src = $this->files->get($output_path);
    if (!strpos($target_src, $output)) {
      $this->files->append($output_path, $output);
    }

    // sass - app.scss
    $output = $this->files->get(__DIR__ . '/../Stubs/resources/sass/app_add.scss.stub');
    $output_path = base_path() . '/resources/sass/app.scss';
    $target_src = $this->files->get($output_path);
    if (!strpos($target_src, $output)) {
      $this->files->append($output_path, $output);
    }
  }
}
