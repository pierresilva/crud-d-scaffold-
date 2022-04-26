<?php

namespace pierresilva\LaravelCrud;

use Illuminate\Support\ServiceProvider;

class GeneratorsServiceProvider extends ServiceProvider {

	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerScaffoldGenerator();
	}

	/**
	 * Register the make:scaffold generator.
	 */
	private function registerScaffoldGenerator()
	{
		//Setup my scaffold
		$this->app->singleton('command.crud.setup', function ($app) {
			return $app['pierresilva\LaravelCrud\Commands\CrudSetupCommand'];
		});
		$this->commands('command.crud.setup');
	}
}
