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
		'make:auth',
		'migrate'
	];
	const COMMAND_CREATE_PROJECT = "php COMPOSER create-project laravel/laravel PROJECT_PATH";
	const SECRET = "Secret";
	
	public static function Init(){
		if(isset($_GET['token']) && $_GET['token']==static::SECRET){
			if(isset($_GET['composer'])){
				if($_GET['composer']=='create-project'){
					static::CreateProject(static::LARAVEL_PUBLIC);
				} else if(in_array($_GET['composer'], static::COMPOSER_COMMAND_LIST)){
					static::RunComposerCommand($_GET['composer']);
				} else echo 'Command not found.';
			} else if(isset($_GET['artisan'])){
				if(in_array($_GET['artisan'], static::LARAVEL_ARTISAN_COMMAND_LIST)){
					static::RunArtisanCommand($_GET['artisan']);
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
		if(is_null($publicPath)) $publicPath = static::LARAVEL_DEFAULT_PUBLIC;
		if(is_null($tempName)) $tempName = static::DIRECTORY_TEMPORARY_NAME;
		static::DownloadComposer();
		$command = str_replace(['COMPOSER','PROJECT_PATH'],[static::ComposerPath(), " " . static::BasePath() . DIRECTORY_SEPARATOR . $tempName], static::COMMAND_CREATE_PROJECT);
		echo $command."<br>\n";
		shell_exec($command);
		static::MoveAll(static::BasePath() . DIRECTORY_SEPARATOR . $tempName, static::BasePath(), function($source, $destination, $file) use ($publicPath){
			if($file == static::LARAVEL_DEFAULT_PUBLIC && $publicPath != $file) {
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
		if(!file_exists(static::ComposerPath())){
			static::DownloadFile(static::COMPOSER_URL, static::ComposerPath());
		}
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
		echo shell_exec($cmd);
		chdir($current);
	}
	public static function RunComposerCommand($command){
		if(!static::CheckProject()) static::CreateProject(static::LARAVEL_PUBLIC);
		$cmd = "php " . static::ComposerPath() . " " . $command;
		echo $cmd."<br>\n";
		$current = __DIR__;
		chdir(static::BasePath());
		echo shell_exec($cmd);
		chdir($current);
	}
}

Master::Init();

?>