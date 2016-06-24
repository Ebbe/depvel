<?php
namespace Ebbe\Depvel\Commands;

use Ebbe\Depvel\Deploy;
use Illuminate\Console\Command;

class Depvel extends Command {
    protected $signature = 'depvel {subcommand=help} {configuration?}';

    protected $description = 'Main entrance to Depvel';

    public function fire() {
        switch($this->argument('subcommand')) {
            case 'help':
                return $this->sub_help();
                break;
            case 'deploy':
                return $this->sub_deploy();
                break;
        }

        return 1;
    }

    private function sub_help() {
        $this->line('√ Help is on its way');
        $this->line('Available subcommands: help,deploy'."\n");
        $this->list_configurations();

        return 0;
    }

    private function sub_deploy() {
        if($this->argument('configuration') == null) {
            $this->warn('You did not specify which configuration to use.');
            $this->list_configurations();
            return;
        }
        $configurations = config('depvel.configurations');
        if(array_has($configurations,$this->argument('configuration'))==false) {
            $this->warn('I could not find that configuration. Did you misspell it?');
            $this->list_configurations();
            return;
        }
        $configuration = $configurations[$this->argument('configuration')];
        $configuration = array_merge(config('depvel.common_config'), $configuration);

        $servers = config('depvel.servers');
        if(array_has($servers,$configurations[$this->argument('configuration')]['server'])==false) {
            $this->error('Error in configuration file. Configurations server did not match anything.');
            return;
        }
        $configuration['server'] = $servers[$configuration['server']];

        $this->info('Deploying to '.$this->argument('configuration'));
        $deploy = new Deploy($this->argument('configuration'), $configuration, $this);
        return $deploy->run();
    }

    private function list_configurations() {
        $this->line('Configurations:');
        foreach(array_keys(config('depvel.configurations')) as $configuration) {
            $this->line('  '.$configuration);
        }
    }
}