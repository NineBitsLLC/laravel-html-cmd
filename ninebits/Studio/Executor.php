<?php
namespace NineBits\Studio;

class Executor extends Master {    
    const COMPOSER_URL = "https://getcomposer.org/composer.phar";
    const COMPOSER_NAME = "composer.phar";
    const COMPOSER_COMMAND_LIST = [
        "update",
        "install",
        "create-project"
    ];
    const LARAVEL_PUBLIC = "public";
    const LARAVEL_ARTISAN_NAME = "artisan";
    const LARAVEL_ARTISAN_COMMAND_LIST = [
            '-V',
            'clear-compiled',
            'down',
            'env',
            'help',
            'inspire',
            'list',
            'migrate',
            'optimize',
            'preset',
            'serve',
            'tinker',
            'up',
            'app:name',
            'auth:clear-resets',
            'controller',
            'cache:clear',
            'cache:forget',
            'cache:table',
            'config:cache',
            'config:clear',
            'db:seed',
            'event',
            'event:generate',
            'key',
            'key:generate',
            'make',
            'make:auth',
            'make:command',
            'make:controller',
            'make:event',
            'make:exception',
            'make:factory',
            'make:job',
            'make:listener',
            'make:mail',
            'make:middleware',
            'make:migration',
            'make:model',
            'make:notification',
            'make:policy',
            'make:provider',
            'make:request',
            'make:resource',
            'make:rule',
            'make:seeder',
            'make:test',
            'model',
            'migrate',
            'migrate:fresh',
            'migrate:install',
            'migrate:refresh',
            'migrate:reset',
            'migrate:rollback',
            'migrate:status',
            'notifications',
            'notifications:table',
            'package',
            'package:discover',
            'queue',
            'queue:failed',
            'queue:failed-table',
            'queue:flush',
            'queue:forget',
            'queue:listen',
            'queue:restart',
            'queue:retry',
            'queue:table',
            'queue:work',
            'route',
            'route:cache',
            'route:clear',
            'route:list',
            'schedule',
            'schedule:run',
            'session',
            'session:table',
            'storage',
            'storage:link',
            'vendor',
            'vendor:publish',
            'view',
            'view:clear',
    ];
    const COMMAND_CREATE_PROJECT = "COMPOSER create-project laravel/laravel PROJECT_PATH";

    public static function handler($secret = null, $commands = [], $results = []){
        if(Master::DEBUG_MODE == 1){
            //echo "<!-- Memory: ".memory_get_usage(true) . "-->\n"; 
            //echo "<!-- Memory peak: ".memory_get_peak_usage(true) . "-->\n"; 
            //echo "<!-- Memory limit: ".ini_get('memory_limit') . "-->\n"; 
            //phpinfo();
            //exit();
            error_reporting(E_ALL | E_STRICT);
            ini_set('display_errors', 1);    
        }
        $filename = dirname(static::pathBase()) . '/' . static::DIRECTORY_DEFAULT_PUBLIC . '/studio.php';
        $pwd = static::SECRET;
        if(static::checkPublicFile($filename)){
            require_once $filename;
            foreach (\NineBits\Studio\Master::$commands as $id => $item) {
                $item['executed_at'] = date('Y-m-d H:i:s');
                var_dump($item);
                \NineBits\Studio\Master::$results[] = $item;
            }
            $data = <<<DATA
<?php require_once __DIR__.'/../ninebits/Studio/Master.php'; \NineBits\Studio\Master::handler("{$pwd}", array (
), 
DATA;
            $data .= var_export(\NineBits\Studio\Master::$results, true);
            $data .= <<<DATA
);
DATA;
            file_put_contents($filename, $data);
        } else {
            $data = <<<DATA
<?php require_once __DIR__.'/../ninebits/Studio/Master.php'; \NineBits\Studio\Master::handler("{$pwd}", array (
), array (
    0 => 'Security violation. An attempt was made to change studio.pxp, the file was overwritten.'
));
DATA;
            file_put_contents($filename, $data);
        }
    }

    protected static function composerCreateProject($command = null, $param = null){
        $publicPath = static::LARAVEL_PUBLIC;
        $tempName = static::DIRECTORY_TEMPORARY_NAME;
        static::downloadComposer();
        $command = str_replace(['COMPOSER','PROJECT_PATH'],[static::pathComposer(), static::pathBase() . DIRECTORY_SEPARATOR . $tempName], static::COMMAND_CREATE_PROJECT);
        static::write($command);
        static::runCommand($command);
        static::moveAll(static::pathBase() . DIRECTORY_SEPARATOR . $tempName, static::pathBase(), function($source, $destination, $file) use ($publicPath){
                if($file == static::DIRECTORY_DEFAULT_PUBLIC) {
                        static::moveAll($source . DIRECTORY_SEPARATOR . $file, static::pathBase() . DIRECTORY_SEPARATOR . $publicPath);
                } else rename($source . DIRECTORY_SEPARATOR . $file, $destination . DIRECTORY_SEPARATOR . $file);
        });
    }
    
    protected static function runComposerCommand($command, $param = null){
        static::downloadComposer();
        $cmd = static::pathComposer() . " " . $command . (isset($param) ? ' ' . $param : '');
        static::write($cmd);
        $current = __DIR__;
        chdir(static::pathBase());
        static::runCommand($cmd);
        chdir($current);
    } 
    protected static function runArtisanCommand($command){
            if(!static::checkProject()) static::CreateProject(static::LARAVEL_PUBLIC);
            $cmd = static::pathArtisan() . " " . $command;
            static::write($cmd);
            $current = __DIR__;
            chdir(static::pathBase());
            static::runPHP($cmd);
            chdir($current);
    }
    protected static function runPHP($command){
            ob_start();
            $_SERVER['argv'] = preg_split("/[\s]+/", $command);
            $_SERVER['argc'] = count($_SERVER['argv']);
            require_once $_SERVER['argv'][0];
            $content = nl2br(ob_get_clean());
            static::write($content);
    }
    protected static function runMethod($type, $command, $params){
        $method_name = explode('-', $command);
        $method_name = array_map(function($str){ return ucfirst($str); }, $method_name);
        $method_name = strtolower($type) . implode('', $method_name);
        if(method_exists(static::class, $method_name)){
            static::{$method_name}($command, $params);
            return true;
        }
        return false;
    }
    protected static function runCommand($command){
        ob_start();
        passthru($command);
        $content = nl2br(ob_get_clean());
        static::result($content);
    }

    protected static function checkComposer(){
        return file_exists(static::pathComposer());
    }
    protected static function checkDependency(){
        if (!version_compare(PHP_VERSION, '7.0.0','>=')) {
            static::write("PHP version must be >= 7.0.0. You PHP version ".PHP_VERSION);
            exit();
        } else return;
    }
    protected static function checkProject(){
        return file_exists(static::pathArtisan());
    }
    protected static function checkPublicFile($filename){
        $pwd = static::SECRET;
        $handle = fopen($filename, "r");
        $line = fread($handle, strlen($pwd)+105);
        fclose($handle);
        $line_etalon = <<<DATA
<?php require_once __DIR__.'/../ninebits/Studio/Master.php'; \NineBits\Studio\Master::handler("{$pwd}", array (
DATA;
        return $line == $line_etalon;
    }

    protected static function downloadComposer(){
        if(!static::checkComposer()){
            static::downloadFile(static::COMPOSER_URL, static::pathComposer());
        }
    }
    protected static function downloadFile($url, $path){
        $ch = curl_init();
        $fp = fopen ($path, 'w+b');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        chmod($path, 0777);
        return (file_exists($path) && filesize($path) > 0);
    }

    protected static function moveAll($source, $destination, callable $callback = null){
        $files = scandir($source);	
        foreach ($files as $file){
            if($file!='.' && $file!='..') {
                if(is_null($callback)) rename($source . DIRECTORY_SEPARATOR . $file, $destination . DIRECTORY_SEPARATOR . $file);
                else $callback($source, $destination, $file);
            }
        }
        rmdir($source);
    }    
    
    protected static function pathComposer(){
        return static::pathBase() . DIRECTORY_SEPARATOR . static::COMPOSER_NAME;
    }
    protected static function pathArtisan(){
        return static::pathBase() . DIRECTORY_SEPARATOR . static::LARAVEL_ARTISAN_NAME;
    }
}

