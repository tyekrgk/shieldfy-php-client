<?php
namespace shieldfy;

use Shieldfy\Config;
use Shieldfy\Installer;
use Shieldfy\Session;
use Shieldfy\Cache\CacheManager;
use Shieldfy\Monitors\MonitorsBag;
use Shieldfy\Collectors\UserCollector;
use Shieldfy\Collectors\RequestCollector;
use Shieldfy\Collectors\ExceptionsCollector;
use Shieldfy\Collectors\CodeCollector;
use Shieldfy\Exceptions\ExceptionHandler;

class Guard
{
    /**
     * @var Singleton Reference to singleton class instance
     */
    private static $instance;

    /**
     * @var api endpoint
     */
    public $apiEndpoint = 'http://api.flash.app';

    /**
     * @var version
     */
    public $version = '2.1.0';

    /**
     * Default configurations items.
     *
     * @var array
     */
    protected $defaults = [
        'debug'          => false, //debug status [true,false]
        'action'         => 'block', //response action [block, silent]
        'headers'  	     => [ //list of available headers to expose
        	'X-XSS-Protection'       =>  '1; mode=block',
        	'X-Content-Type-Options' =>  'nosniff',
        	'X-Frame-Options'        =>  'SAMEORIGIN'
        ],
        'disable'       =>  [] //list of disabled monitors
    ];

    /**
     * @var Config $config
     * @var CacheManager $cache
     */
    protected $config;
    protected $cache;

    /**
     * Initialize Shieldfy guard.
     *
     * @param array $config
     *
     * @return object
     */
    public static function init(array $config, $cache = null)
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config,$cache);
        }
        return self::$instance;
    }

    /**
     * Create a new Guard Instance
     */
    public function __construct(array $config, $cache = null)
    {
        //set config container
        $this->config = new Config($this->defaults, array_merge($config,[
            'apiEndpoint' => $this->apiEndpoint,
            'rootDir'     => __dir__,
            'version'     => $this->version
        ]));

        //prepare the cache method if not supplied
        if ($cache === null) {
            //create a new file cache
            $cache = new CacheManager($this->config);
            $cache = $cache->setDriver('file', [
                'path'=> realpath($this->config['rootDir'].'/../tmp').'/',
            ]);
        }
        $this->cache = $cache;

        //start shieldfy guard
        $this->startGuard();

    }

    /**
      * start the actual guard
      * @return void
      */
    protected function startGuard()
    {

        $exceptionsCollector = new ExceptionsCollector($this->config);
        $requestCollector = new RequestCollector($_GET,$_POST,$_SERVER, $_COOKIE, $_FILES);
        $userCollector = new UserCollector($requestCollector);
        $codeCollector = new CodeCollector;

        //check the installation
        if(!$this->isInstalled())
        {
            $install = (new Installer($requestCollector,$this->config,$this->version))->run();
        }

        exit;

        //start new session
        $session = new Session($userCollector, $this->config, $this->cache);



        /* monitors */
        $monitors = new MonitorsBag($this->config,
                                    $this->cache,
                                    [
                                        'exceptions' => $exceptionsCollector,
                                        'request'    => $requestCollector,
                                        'user'       => $userCollector,
                                        'code'       => $codeCollector
                                    ]);
        $monitors->run();

        $this->exposeHeaders();

        echo '<br />starting the guard <br />';
    }


    public function isInstalled()
    {
        if (file_exists($this->config['rootDir'].'/data/installed')) {
            return true;
        }
        return false;
    }


    private function exposeHeaders()
    {
        if (function_exists('header_remove')) {
            header_remove('x-powered-by');
        }

        foreach ($this->config['headers'] as $header => $value) {
            if($value === false) continue;
            header($header.' : '.$value);
        }

        $signature = hash_hmac('sha256', $this->config['app_key'], $this->config['app_secret']);
        header('X-Web-Shield: ShieldfyWebShield');
        header('X-Shieldfy-Signature: '.$signature);
    }

}
