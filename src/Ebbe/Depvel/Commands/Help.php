<?php
namespace Ebbe\Depvel\Commands;

use Illuminate\Console\Command;

class Help extends Command {
    //protected $name = 'depvel';
    protected $signature = 'depvel:help';

    protected $description = 'Main entrance to Depvel. Run without arguments to get help.';

    public function fire() {
        $this->info('Im comming to help');
        print_r($this->argument());
    }
}