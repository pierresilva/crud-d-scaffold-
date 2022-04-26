<?php

namespace pierresilva\LaravelCrud\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use pierresilva\LaravelCrud\Core\CrudScaffold;

class CrudSetupCommand extends Command
{
//    use DetectsApplicationNamespace, MakerTrait, NameSolverTrait;

    /**
     * The console command name.
     *
     * @var string
     */

    protected $signature = 'crud:setup
                            {module : Module name to generate code }
                            {filePath=crud-d-scaffold.json : file path of setting json file. Default: crud-d-scaffold.json }
                            {--noMigrations : no generate migrations }
                            {--noSeeders : no generate seeders }
                            {--noModels : no generate models }
                            {--noControllers : no generate controllers }
                            {--noExports : no generate exports }
                            {--noRoutes : no generate routes }
                            {--noFrontend : no generate frontend }
                            {--noAcl : no generate ACL roles and permissions }
                            {--f|force : Allow overwrite files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup crud with bootstrap 5';

    /**
     * Crud-D-Scaffold Core
     *
     * @var obj
     */
    protected $crud_d_scaffold;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * Create a new command instance.
     *
     * @param CrudScaffold $crud_d_scaffold
     * @param Composer $composer
     */
    public function __construct( CrudScaffold $crud_d_scaffold, Composer $composer )
    {
        parent::__construct();
        $this->composer = $composer;
        $this->crud_d_scaffold = $crud_d_scaffold;
        $this->crud_d_scaffold->setCommand( $this );
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $this->crud_d_scaffold->generate($this->argument('module'), $this->options());

        //Dump autoload
        $this->info('Dump-autoload...');
        $this->composer->dumpAutoloads();

        // End Message
        $this->info('Configuring is done');
    }
}
