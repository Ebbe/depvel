<?php namespace Ebbe\Depvel;

use Illuminate\Support\ServiceProvider;

class DepvelServiceProvider extends ServiceProvider {
	protected $defer = false;

	public function boot() {
		$this->publishes([
			__DIR__ . '/../../config.php' => config_path('depvel.php')
		]);
	}

	public function register() {
		$commands = [
			'Ebbe\Depvel\Commands\Depvel'
		];
		$this->commands($commands);
	}
}
