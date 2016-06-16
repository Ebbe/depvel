<?php
namespace Ebbe\Depvel;

use Collective\Remote\Connection;
use phpseclib\Net\SFTP;

class Deploy {
    private $name, $configuration, $command, $connection;
    private $root_path, $release_path;

    function __construct($_name, $_configuration, $_command) {
        $this->name = $_name;
        $this->configuration = $_configuration;
        $this->command = $_command;

        $this->root_path = $this->configuration['path']. '/' . $this->name . '/';
        $this->release_path = $this->root_path. 'releases/'. date("Ymd_His"). '/';
    }

    public function run() {
        try {
            $this->_connect_to_server();
            $path_exists = $this->_does_path_exist();
            if ($path_exists == false) {
                if ($this->command->confirm('The path on the server does not exist yet. Should I set everything up?') == false)
                    return;
                else {
                    $this->command->line(' * Running first time run');
                    $this->_first_time_run();
                }
            }

            $this->command->line(' * Checking environment');
            $this->_check_environment();
            $this->command->line(' * Checking out code');
            $this->_checkout_code();
            $this->command->line(' * Setting up Laravel');
            $this->_setup_laravel();

            $this->command->line(' * Setting current to this release');
            $this->_run('unlink current; ln -s '.$this->release_path.' current');

            $this->command->line(' * Managing releases');
            $this->_manage_release_paths();
        } catch(\Exception $e) {
            $this->command->error('Deployment failed!');
            $this->command->error($e->getMessage());
            // TODO: Clean up failed folder
        }
    }

    /**
     * Runs $command in root path, or specified path.
     * @param $command string
     * @param string|null $base_dir
     * @return string Output
     */
    private function _run($command, $base_dir = null, &$status_code = null) {
        // TODO: Clean up this mess and just use http://phpseclib.sourceforge.net/ssh/intro.html directly
        $raw_connection = $this->connection->getGateway()->getConnection();
        if($base_dir==null)
            $base_dir = $this->root_path;
        $output = $raw_connection->exec('cd '.$base_dir.'; '.$command);
        $status_code = intval($raw_connection->getExitStatus());
        return $output;
    }

    private function _run_release($command, &$status_code = null) {
        return $this->_run($command, $this->release_path, $status_code);
    }

    /**
     * It's the first time this is run, so set everything up.
     */
    private function _first_time_run() {
        $status = null;
        $this->_run('mkdir -p ' . $this->root_path.'releases', null, $status);
        if($status > 0)
            throw new \Exception('Could not create folders in '.$this->root_path.'. Do you have the nessesary rights?');
        $this->_run('mkdir -p ' . $this->root_path.'shared', null, $status);
        if($status > 0)
            throw new \Exception('Could not create folders in '.$this->root_path.'. Do you have the nessesary rights?');
    }

    private function _connect_to_server() {
        # Do we connect with password or key?
        if($this->configuration['server']['password'] == '' or $this->configuration['server']['password'] == null) {
            # Use keyfile
            $auth = array('key'=>$_SERVER['HOME'].'/.ssh/id_rsa');
            if($this->configuration['server']['custom_key'])
                $auth = array('key'=>$this->configuration['server']['custom_key']);
        }
        // TODO: Actually use password if needed

        // This throws an error if it cannot connect to server
        $this->connection = new Connection('name', $this->configuration['server']['host'], $this->configuration['server']['user'], $auth, null, 99999);
    }

    private function _does_path_exist() {
        $this->_run('cd ' . $this->root_path.'releases');
        return $this->connection->status()==0;
    }

    private function _manage_release_paths() {
        $current_number_of_releases = intval($this->_run('ls releases| wc -l'));
        if($current_number_of_releases > $this->configuration['keep_releases']) {
            $this->command->line('   - Removing old releases. Keeping '.$this->configuration['keep_releases'].' of total '.$current_number_of_releases);
            for($i=$current_number_of_releases; $i > $this->configuration['keep_releases']; $i--) {
                $oldest_dir = $this->_run('ls releases|head -n 1');
                $this->_run('rm -rf releases/'.$oldest_dir);
            }
        }
    }

    private function _checkout_code() {
        $git_command = 'git clone -b '.$this->configuration['git_branch'].' '.$this->configuration['git_location'].' '.$this->release_path;
        $this->command->line('   - Running: '.$git_command);
        $this->_run($git_command);

        $this->_setup_shared_folder();
        $this->_run_release('chmod a+wr -R public'); // TODO: Should probably not be done this way..
        $this->_run_release('chmod a+wr -R storage');
    }

    private function _setup_laravel() {
        $status = null;
        $composer_command = 'composer install';
        if($this->configuration['development']==false)
            $composer_command .= ' --no-dev';
        $this->command->line('   - Running: '.$composer_command);
        $result = $this->_run_release($composer_command, $status);
        if($status > 0) {
            throw new \Exception('"'.$composer_command.'" failed with: '.$result);
        }

        $this->command->line('   - Running: php artisan migrate --force');
        $result = $this->_run_release('php artisan migrate --force', $status);
        if($status > 0) {
            throw new \Exception('"php artisan migrate --force" failed with: '.$result);
        }
    }

    private function _setup_shared_folder() {
        foreach ($this->configuration['shared_dirs'] as $dir) {
            $this->_run('mkdir -p ' . $this->root_path . 'shared/' . $dir); # Make sure it exists
            $this->_run_release('ln -s '.$this->root_path.'shared/'.$dir.' '. $dir);
        }
        foreach ($this->configuration['shared_files'] as $file) {
            if (str_contains($file, '/')) {
                // Contains folders. First create those
                $dir = substr($file, 0, strrpos($file, '/'));
                $this->_run('mkdir -p ' . $this->root_path . 'shared/' . $dir);
                $this->_run_release('mkdir -p '. $dir);
            }
            $this->_run('touch ' . $this->root_path . 'shared/' . $file); # Make sure it exists

            $this->_run_release('ln -s '.$this->root_path.'shared/'.$file.' '. $file);
        }
    }

    private function _check_environment()
    {
        $status_code = null;
        $this->_run('php -v', null, $status_code);
        if(intval($status_code)>0)
            throw new \Exception('Could not find php. Check your environment.');
        $this->_run('composer -V', null, $status_code);
        if(intval($status_code)>0)
            throw new \Exception('Could not find composer. Check your environment.');
    }
}
