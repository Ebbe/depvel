<?php
namespace Ebbe\Depvel\Commands;

use Illuminate\Console\Command;
use Collective\Remote\Connection;

class Depvel extends Command {
    protected $signature = 'depvel {subcommand=help} {environment?}';

    protected $description = 'Main entrance to Depvel';

    public function fire() {
        if($this->argument('subcommand')=='help') {
            $this->line('√ Help is on its way');

            $this->line('Available subcommands: help,deploy');
        }
        $this->list_configurations();

        $n = new Connection('name', 'test.bonsailog.com', 'ebbe', array('key'=>$_SERVER['HOME'].'/.ssh/id_rsa'));
        $n->run('ls', function($line,$connection) {$this->info($line);});
    }


    private function list_configurations() {
        $this->line('Configurations:');
        foreach(array_keys(config('depvel.configurations')) as $configuration) {
            $this->line('  '.$configuration);
        }
    }
}