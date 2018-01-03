<?php
namespace NineBits\Studio;

class Master {
	const COMPOSER_URL = "https://getcomposer.org/composer.phar";
	const COMPOSER_NAME = "composer.phar";
	const COMPOSER_COMMAND_LIST = [
		"update",
	];
	const DIRECTORY_TEMPORARY_NAME = "tmp";
	const LARAVEL_DEFAULT_PUBLIC = "public";
	const LARAVEL_PUBLIC = "public_html";
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
	const COMMAND_CREATE_PROJECT = "php COMPOSER create-project laravel/laravel PROJECT_PATH";
	const SECRET = "Secret";
	
	public static function Init(){
		if(isset($_GET['token']) && $_GET['token']==static::SECRET){                    
                        static::CheckDependency();
			if(isset($_GET['composer'])){
				if($_GET['composer']=='create-project' && !isset($_GET['param'])){
					static::CreateProject(static::LARAVEL_PUBLIC);
				} else if(in_array($_GET['composer'], static::COMPOSER_COMMAND_LIST)){
					static::RunComposerCommand($_GET['composer'] . (isset($_GET['param'])?' ' . $_GET['param']:''));
				} else echo 'Command not found.';
			} else if(isset($_GET['artisan'])){
				if(in_array($_GET['artisan'], static::LARAVEL_ARTISAN_COMMAND_LIST)){
					static::RunArtisanCommand($_GET['artisan'] . (isset($_GET['param'])?' ' . $_GET['param']:''));
				} else echo 'Command not found.';
			} else echo 'Please enter command, example: ?token=Secret&composer=create-project.';
		} else echo 'Access denied.';
	}
	public static function BasePath(){
		return dirname(__DIR__);
	}
	public static function ComposerPath(){
		return static::BasePath() . DIRECTORY_SEPARATOR . static::COMPOSER_NAME;
	}
	public static function ArtisanPath(){
		return static::BasePath() . DIRECTORY_SEPARATOR . static::LARAVEL_ARTISAN_NAME;
	}
	public static function DownloadFile($url, $path){
		$ch = curl_init();
		$fp = fopen ($path, 'w+');
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_exec($ch);
		curl_close($ch);
		fclose($fp);
		return (file_exists($path) && filesize($path) > 0);
	}
	public static function CreateProject($publicPath = null, $tempName = null){
		if(is_null($publicPath)) $publicPath = static::LARAVEL_PUBLIC;
		if(is_null($tempName)) $tempName = static::DIRECTORY_TEMPORARY_NAME;
		static::DownloadComposer();
		$command = str_replace(['COMPOSER','PROJECT_PATH'],[static::ComposerPath(), " " . static::BasePath() . DIRECTORY_SEPARATOR . $tempName], static::COMMAND_CREATE_PROJECT);
		echo $command."<br>\n";
		static::RunCommand($command);
		static::MoveAll(static::BasePath() . DIRECTORY_SEPARATOR . $tempName, static::BasePath(), function($source, $destination, $file) use ($publicPath){
			if($file == static::LARAVEL_DEFAULT_PUBLIC) {
				static::MoveAll($source . DIRECTORY_SEPARATOR . $file, static::BasePath() . DIRECTORY_SEPARATOR . $publicPath);
			} else rename($source . DIRECTORY_SEPARATOR . $file, $destination . DIRECTORY_SEPARATOR . $file);
		});
	}
	public static function MoveAll($source, $destination, callable $callback = null){
		$files = scandir($source);	
		foreach ($files as $file){
			if($file!='.' && $file!='..') {
				if(is_null($callback)) rename($source . DIRECTORY_SEPARATOR . $file, $destination . DIRECTORY_SEPARATOR . $file);
				else $callback($source, $destination, $file);
			}
		}
		rmdir($source);
	}
	public static function DownloadComposer(){
		if(!static::CheckComposer()){
			static::DownloadFile(static::COMPOSER_URL, static::ComposerPath());
		}
	}
	public static function CheckDependency(){
		if (!version_compare(PHP_VERSION, '7.0.0','>=')) {
			echo "PHP version must be >= 7.0.0. You PHP version ".PHP_VERSION;
			exit();
		} else return;
	}
	public static function CheckComposer(){
		return file_exists(static::ComposerPath());
	}
	public static function CheckProject(){
		return file_exists(static::ArtisanPath());
	}
	public static function RunArtisanCommand($command){
		if(!static::CheckProject()) static::CreateProject(static::LARAVEL_PUBLIC);
		$cmd = "php " . static::ArtisanPath() . " " . $command;
		echo $cmd."<br>\n";
		$current = __DIR__;
		chdir(static::BasePath());
		static::RunCommand($cmd);
		chdir($current);
	}
	public static function RunComposerCommand($command){
		static::DownloadComposer();
		$cmd = "php " . static::ComposerPath() . " " . $command;
		echo $cmd."<br>\n";
		$current = __DIR__;
		chdir(static::BasePath());
		static::RunCommand($cmd);
		chdir($current);
	}
        public static function RunCommand($command){
            ob_start();
            passthru($command);
            $content = nl2br(ob_get_clean());
            echo $content;
        }
}

Master::Init();

?>
