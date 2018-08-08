<?php
namespace NineBits\Studio;

class Master {
    const DEBUG_MODE = 1;
    
    const STATUS_SUCCESS = 0;
    const STATUS_ERROR = 1;
    
    const REMOTE_ADDR = ["127.0.0.1"];
    const DIRECTORY_TEMPORARY_NAME = "tmp";
    const DIRECTORY_DEFAULT_PUBLIC = "public";
    
    const SECRET = "secret";

    public static $response = [
        'code' => 0,
        'messages' => [],
        'result' => [],
    ];
    
    public static $commands = [];
    public static $results = [];
    public static $secret;
    
    
    public static function handler($secret = null, $commands = [], $results = []){
        if(Master::DEBUG_MODE == 1){
            error_reporting(E_ALL | E_STRICT);
            ini_set('display_errors', 1);    
        }
        static::$secret = $secret;
        static::$commands = $commands;
        static::$results = $results;
        if(!static::checkCLI()) {
            header('Content-Type: application/json');
            static::write("Remote IP: ".$_SERVER['REMOTE_ADDR']);
            if($secret != static::SECRET){
                static::error('Corupted public file master.php');
            } else if(isset($_GET['token']) && $_GET['token'] == static::SECRET){
                foreach($results as $result) static::result($result);
                if(isset($_GET['artisan']) || isset($_GET['composer'])){
                    $command = ['created_at'=>date('Y-m-d H:i:s')];                    
                    if(isset($_GET['id'])) $command['id'] = $_GET['id'];
                    else $command['id'] = count($commands);
                    if(isset($_GET['artisan'])) $command['artisan'] = $_GET['artisan'];
                    if(isset($_GET['composer'])) $command['composer'] = $_GET['composer'];
                    if(isset($_GET['param'])) $command['param'] = $_GET['param'];
                    static::write("Command ID:{$command['id']} add to executable queue.");
                    $commands[$command['id']] = $command;
                    static::result($command);
                }
                $pwd = static::SECRET;
                $data = <<<DATA
<?php require_once __DIR__.'/../ninebits/Studio/Master.php'; \NineBits\Studio\Master::handler("{$pwd}", 
DATA;
                $data .= var_export($commands, true);
                $data .= <<<DATA
, array (   
));
DATA;
                $filename = realpath(dirname(static::pathBase()) . '/' . static::DIRECTORY_DEFAULT_PUBLIC) . '/studio.php';
                file_put_contents($filename, $data);
            } else static::error('Access denied.');        
            echo json_encode(static::$response);
            exit;
        }
    }

    protected static function pathBase(){
        return dirname(__DIR__);
    }
    
    protected static function checkCLI(){
        $sapi_type = php_sapi_name();
        return (substr($sapi_type, 0, 3) == 'cli');
    }
    
    protected static function error($value){
        static::$response['code'] = static::STATUS_ERROR;
        static::$response['messages'][] = $value;
    }
    protected static function write($value){
        static::$response['messages'][] = $value;
    }
    protected static function result($value){
        static::$response['result'][] = $value;
    }
}

?>
