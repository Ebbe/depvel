<?php
namespace Ebbe\Depvel\Commands;

use Illuminate\Console\Command;

class Depvel extends Command {
    protected $signature = 'depvel {subcommand=help} {environment?}';

    protected $description = 'Main entrance to Depvel';

    public function fire() {
        if($this->argument('subcommand')=='help') {
            $this->line('√ Help is on its way');

            $this->line('Available subcommands: help,deploy');
        }
        $this->list_configurations();
    }

    private function list_configurations() {
        $this->line('Configurations:');
        foreach(array_keys(config('depvel.configurations')) as $configuration) {
            $this->line('  '.$configuration);
        }
    }
}