<?php
namespace Banana;

use Banana\Lib\Banana;
use Banana\Middleware\PluginMiddleware;
use Banana\Plugin\PluginLoader;
use Cake\Cache\Cache;
use Cake\Console\ConsoleErrorHandler;
use Cake\Core\Configure;
use Cake\Core\Configure\ConfigEngineInterface;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\Database\Type;
use Cake\Error\ErrorHandler;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Log\Log;
use Cake\Mailer\Email;
use Cake\Network\Request;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Utility\Security;

/**
 * Application setup class.
 *
 * This defines the bootstrapping logic and middleware layers you
 * want to use in your application.
 *
 * @todo ! Experimental ! Do not use in production !
 * @codeCoverageIgnore
 */
class MultisiteApplication extends BaseApplication
{
    /**
     * Site definitions
     */
    static protected $_sites = [];

    /**
     * @var string Path to base config dir
     */
    protected $baseConfigDir;

    /**
     * @var string Currently selected siteId
     */
    protected $siteId;

    /**
     * Add site configuration
     */
    public static function addSite($siteId, array $config = [])
    {
        $config = array_merge(['hosts' => []], $config);
        static::$_sites[$siteId] = $config;
    }

    /**
     * Return active site ID
     */
    public static function getSiteId()
    {
        // determine site id from environment
        $siteId = (defined('BC_SITE_ID')) ? constant('BC_SITE_ID') : null;
        $siteId = ($siteId === null && getenv('BC_SITE_ID')) ? getenv('BC_SITE_ID') : $siteId;
        $siteId = ($siteId === null && defined('ENV')) ? constant('ENV') : $siteId; //@deprecated @legacy
        $host = self::getSiteHost();

        // determine site id from HTTP request
        if ($siteId === null) {
            foreach (static::$_sites as $_siteId => $site) {
                $hosts = (array)static::$_sites[$_siteId]['hosts'];
                if (in_array($host, $hosts)) {
                    $siteId = $_siteId;
                    break;
                }
            }
        }

        // fallback to default
        if ($siteId === null) {
            $siteId = 'default';
        }

        defined('BC_SITE_ID') or define('BC_SITE_ID', $siteId);
        defined('BC_SITE_HOST') or define('BC_SITE_HOST', $host);
        // @TODO remove legacy constants
        defined('ENV') or define('ENV', BC_SITE_ID);
        defined('BANANA_HOST') or define('BANANA_HOST', BC_SITE_HOST);

        // fallback to default site
        return $siteId;
    }

    /**
     * Return active site host name
     */
    public static function getSiteHost()
    {
        if (defined('BC_SITE_HOST')) {
            return constant('BC_SITE_HOST');
        }

        if (getenv('BC_SITE_HOST')) {
            return getenv('BC_SITE_HOST');
        }

        //@deprecated Legacy code
        if (getenv('BANANA_HOST')) {
            return getenv('BANANA_HOST');
        }

        if (getenv('OVERRIDE_HTTP_HOST')) {
            return getenv('OVERRIDE_HTTP_HOST');
        }

        if (getenv('HTTP_HOST')) {
            return getenv('HTTP_HOST');
        }

        return (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : null;
    }

    /**
     * @param string $configDir
     */
    public function __construct($configDir)
    {
        //@TODO Remove . Dev only
        //ini_set('display_errors', 1);
        //error_reporting(E_ALL);

        $this->baseConfigDir = $configDir;
        $this->siteId = self::getSiteId();

        $configDir =  $configDir . '/sites/' . $this->siteId;
        parent::__construct($configDir);
    }

    /**
     * Load all the application configuration and bootstrap logic.
     *
     * Override this method to add additional bootstrap logic for your application.
     *
     * @return void
     */
    public function bootstrap()
    {
        /*
         * Load path definitions
         */
        require_once $this->baseConfigDir . "/paths.php"; // global
        require_once $this->configDir . "/paths.php"; // site

        /*
         * Bootstrap cake core
         */
        if (!defined('CORE_PATH')) {
            die('CORE_PATH is not defined. [SITE ID: ' . $this->siteId . ']');
        }
        require CORE_PATH . 'config' . DS . 'bootstrap.php';

        /*
         * Setup default config engine and load configs
         */
        Configure::config('default', $this->getDefaultConfigEngine());
        $this->loadConfiguration();

        /*
         * Bootstrap site
         */
        require_once $this->configDir . '/bootstrap.php';

        /*
         * Override site config
         */
        if (file_exists($this->baseConfigDir . '/' . $this->siteId . ".local.php")) {
            require $this->baseConfigDir . '/' . $this->siteId . ".local.php";
        }

        // Set the full base URL.
        // This URL is used as the base of all absolute links.
        if (!Configure::read('App.fullBaseUrl')) {
            $s = null;
            if (env('HTTPS')) {
                $s = 's';
            }

            $httpHost = env('HTTP_HOST');
            if (isset($httpHost)) {
                Configure::write('App.fullBaseUrl', 'http' . $s . '://' . $httpHost);
            }
            unset($httpHost, $s);
        }

        /*
         * Set server timezone to UTC. You can change it to another timezone of your
         * choice but using UTC makes time calculations / conversions easier.
         */
        date_default_timezone_set('UTC'); // @TODO Make default timezone configurable

        /*
         * Configure the mbstring extension to use the correct encoding.
         */
        mb_internal_encoding(Configure::read('App.encoding'));

        /*
         * Set the default locale. This controls how dates, number and currency is
         * formatted and sets the default language to use for translations.
         */
        ini_set('intl.default_locale', Configure::read('App.defaultLocale'));

        /*
         * Register application error and exception handlers.
         * @todo Inject cli configurations from banana
         */
        $isCli = php_sapi_name() === 'cli';
        if ($isCli) {
            (new ConsoleErrorHandler(Configure::read('Error')))->register();

            // Include the CLI bootstrap overrides.
            require $this->configDir . '/bootstrap_cli.php';
            //} elseif (class_exists('\Gourmet\Whoops\Error\WhoopsHandler')) {
            // Out-of-the-box support for "Whoops for CakePHP3" by "gourmet"
            // https://github.com/gourmet/whoops
            //    (new \Gourmet\Whoops\Error\WhoopsHandler(Configure::read('Error')))->register();
        } else {
            (new ErrorHandler(Configure::read('Error')))->register();
        }

        /*
         * Setup detectors for mobile and tablet.
         * @todo Remove mobile request detectors from banana. Move to site's bootstrap
         */
        Request::addDetector('mobile', function ($request) {
            $detector = new \Detection\MobileDetect();

            return $detector->isMobile();
        });
        Request::addDetector('tablet', function ($request) {
            $detector = new \Detection\MobileDetect();

            return $detector->isTablet();
        });

        /*
         * Register database types
         */
        //Type::map('json', 'Banana\Database\Type\JsonType'); // obsolete since CakePHP 3.3
        Type::map('serialize', 'Banana\Database\Type\SerializeType');

        /*
         * Enable default locale format parsing.
         * This is needed for matching the auto-localized string output of Time() class when parsing dates.
         */
        Type::build('datetime')->useLocaleParser();

        /*
         * Debug mode
         */
        $this->setDebugMode(Configure::read('debug'));

        /*
         * Consume configurations
         */
        ConnectionManager::config(Configure::consume('Datasources'));
        Cache::config(Configure::consume('Cache'));
        Log::config(Configure::consume('Log'));
        Security::salt(Configure::consume('Security.salt'));
        Email::configTransport(Configure::consume('EmailTransport'));
        Email::config(Configure::consume('Email'));

        /*
         * Initialize Banana
         */
        Plugin::load('Banana', ['bootstrap' => true, 'routes' => true]);

        // load core plugins
        //PluginLoader::load('Settings', ['bootstrap' => true, 'routes' => false]);
        //PluginLoader::load('Backend', ['bootstrap' => true, 'routes' => true]);
        //PluginLoader::load('User', ['bootstrap' => true, 'routes' => true]);

        // load configured plugins
        PluginLoader::loadAll();

        // load site theme
        if (Configure::check('Site.theme')) {
            PluginLoader::load(Configure::read('Site.theme'), ['bootstrap' => true, 'routes' => true]);
        }

        // local site settings override
        try {
            Configure::load(BC_SITE_ID, 'settings');
        } catch (\Exception $ex) {
            debug($ex->getMessage());
        }

        // Init Banana obj
        //Banana::config(Configure::consume('Banana'));
        $B = Banana::getInstance();
        $B->application($this);
    }

    /**
     * Get default config engine
     * Override in sub-classes to change default config engine
     *
     * @return ConfigEngineInterface
     */
    protected function getDefaultConfigEngine()
    {
        return new PhpConfig($this->configDir . DS);
    }

    /**
     * Sub-routine to auto-load configurations
     * Override in sub-classes to change config loading behavior
     */
    protected function loadConfiguration()
    {
        // app config
        Configure::load('app', 'default', false);
        Configure::load('site');

        // beta config overrides
        // @TODO Replace with environment configs
        if (defined('ENV_BETA')) {
            Configure::load('beta');
            Configure::write('App.beta', ENV_BETA);
        }

        // local config overrides
        try {
            Configure::load('local/app');
        } catch (\Exception $ex) {
        }
        try {
            Configure::load('local/cake-plugins');
        } catch (\Exception $ex) {
        }
    }

    /**
     * Enables / Disables debug mode
     * Override in sub-classes to change debug mode behavior
     */
    protected function setDebugMode($enabled = true)
    {
        if ($enabled) {
            // disable Mail panel by default, as it doesn't play nice with Mailman plugin
            // @TODO Play nice with DebugKit
            if (!Configure::check('DebugKit.panels')) {
                Configure::write('DebugKit.panels', ['DebugKit.Mail' => false]);
            }
            Plugin::load('DebugKit', ['bootstrap' => true]);
        } else {
            // When debug = false the metadata cache should last
            // for a very very long time, as we don't want
            // to refresh the cache while users are doing requests.
            Configure::write('Cache._cake_model_.duration', '+1 years');
            Configure::write('Cache._cake_core_.duration', '+1 years');
        }
    }

    /**
     * Setup the middleware your application will use.
     *
     * @param \Cake\Http\MiddlewareQueue $middleware The middleware queue to setup.
     * @return \Cake\Http\MiddlewareQueue The updated middleware.
     */
    public function middleware($middleware)
    {
        $middleware
            // Catch any exceptions in the lower layers,
            // and make an error page/response
            ->add(new ErrorHandlerMiddleware(Configure::read('Error.exceptionRenderer')))

            // Handle plugin/theme assets like CakePHP normally does.
            ->add(new AssetMiddleware())

            // Auto-wire banana plugins
            ->add(new PluginMiddleware())

            // Apply routing
            ->add(new RoutingMiddleware());

        return $middleware;
    }
}