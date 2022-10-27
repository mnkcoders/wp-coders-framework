<?php defined('ABSPATH') or die;
/*******************************************************************************
 * Plugin Name: Coders Framework
 * Plugin URI: https://coderstheme.org
 * Description: Framework Prototype
 * Version: 0.1.2
 * Author: Coder01
 * Author URI: 
 * License: GPLv2 or later
 * Text Domain: coders_framework
 * Domain Path: lang
 * Class: CodersApp
 * 
 * @author Coder01 <coder01@mnkcoder.com>
 ******************************************************************************/
abstract class CodersApp{
    /**
     * @var boolean
     */
    private static $_debug = TRUE;
    /**
     * @var \CodersApp
     */
    private static $_instance = null;
    /**
     * @var array
     */
    private static $_endpoints = array( );
    /**
     * @var array
     */
    private static $_messages = array();
    /**
     * INSTANCE COMPONENTS
     * @var array
     */
    private $_components = array(
        'request',
        'strings',
    );
    /**
     * 
     */
    protected function __construct(  ) {

    }
    /**
     * @return string
     */
    public final function __toString() {
        return $this->endPoint();
    }
    /**
     * @param string $path
     * @return string
     */
    private static final function __extract( $path ){
        $route = explode('/',  preg_replace('/\\\\/', '/', $path ) );
        return $route[count($route)-1];
    }
    /**
     * @return array
     */
    protected final function __components(){
        return $this->_components;
    }
    /**
     * Ruta local de contenido de la aplicación
     * @return string
     */
    protected final function __root(){
        // within either sub or parent class in a static method
        $ref = new ReflectionClass(get_called_class());
        return preg_replace('/\\\\/', '/',  dirname( $ref->getFileName() ) );
    }
    /**
     * @return array
     */
    protected function __pluginData(){
        //return array();
         return json_encode( get_plugin_data(__FILE__) );
    }

    /** 
     * @param boolean $framework
     * @return string
     */
    private static final function __pluginDir( $framework = false ){
        return preg_replace('/\\\\/','/', plugin_dir_path( $framework ? __FILE__  : __DIR__ ) );
    }
    /**
     * @param string $endpoint
     * @return string
     */
    private static final function __callable( $endpoint ){
        return preg_replace('/-/', '_', $endpoint) . '_application';
    }
    /**
     * @param mixed $string
     * @return mixed
     */
    public static final function __cc( $string ){
        if(is_array($string)){
            $output = array();
            foreach( $string as $str ){
                $output[] = self::__cc($str);
            }
            return $output;
        }
        return preg_replace('/\s/', '', ucwords(preg_replace('/[\-\_]/', ' ' , $string)));
    }
    /**
     * @param string|array $string
     * @return string|array
     */
    public static final function __sc( $string ){
        if(is_array($string)){
            $output = array();
            foreach( $string as $str ){
                $output[] = camelToUnderscore($str);
            }
            return $output;
        }

        return strtolower(preg_replace(
            '/(?<=\d)(?=[A-Za-z])|(?<=[A-Za-z])(?=\d)|(?<=[a-z])(?=[A-Z])/', $us, $string));
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return string
     */
    public final function __call($name, $arguments) {
        switch( TRUE ){
            case preg_match('/^ajax_/', $name):
                return $this->__ajax( substr( $name, 5 ), $arguments );
            default:
                return '';
        }
    }
    /**
     * @param string $name
     * @param array $arguments
     */
    public static final function __callStatic($name, $arguments) {
        switch( true ){
            case preg_match('/^register_/', $name):
                if( count($arguments )){
                    $call = '__register' . self::__cc(substr($name ,9 , strlen($name)-9 ));
                    if(method_exists(self::class, $call)){
                        static::$call( $arguments[0] );
                    }
                }
                break;
            case preg_match('/^run_/', $name):
                if( count($arguments )){
                    $call = '__run' . self::__cc(substr($name ,4 , strlen($name)-4 )) ;
                    if(method_exists(self::class, $call)){
                        return static::$call(
                                $arguments[0] ,
                                count($arguments) > 1 ? $arguments[1] : '' );
                    }
                }
                return false;
            case preg_match('/^service_/', $name):
                return $this->__runService(substr($name, 8 , strlen($name) - 8), $arguments );
            case $name === 'uninstall':
                if( count($arguments)){
                    self::__uninstall( self::__extract( $arguments[0]) );
                }
                break;
            case $name === 'install':
                if( count($arguments)){
                    self::register($arguments[0]);
                    self::__install( self::__extract( $arguments[0] ) , true);
                }
                break;
        }
    }
    /**
     * @param string $service
     * @param array $args
     * @return boolean
     */
    private static final function __runService( $service , array $args = array( ) ){

        \CODERS\Framework\Service::create($service, $args);
        
        return false;
    }
    /**
     * @param string $endpoint
     * @param array $context
     * @return array
     */
    private static final function __runAjax( $endpoint , $context = '' ){
        if( self::hasAjax($endpoint)){
            $setup = self::$_endpoints[$endpoint];
            if( $setup['ajax'] && is_null(self::$_instance)){
                self::$_instance = self::load($endpoint, 'application' );
                $input = filter_input_array(INPUT_POST);
                $route = !is_null($input) ? $input['route'] : 'default';
                $response = !is_null(self::$_instance) ?
                        self::$_instance->__ajax( $route , !is_null($input) ? $input : array() ) :
                                array( 'invalid_endpoint' => $endpoint);

                print json_encode($response);
                //wp_die();
            }
        }
        wp_die();
    }
    /**
     * @param string $endpoint
     * @param string $context
     */
    private static final function __runAdmin( $endpoint , $context = '' ){
        if( is_admin()){
            if( $endpoint === 'coders-framework' ){
                require sprintf('%s/components/views/admin/html/admin.php',self::__pluginDir(true));
            }
            elseif(self::exists($endpoint)){
                self::$_instance = self::importAdminApp($endpoint);
                if( !is_null(self::$_instance)){
                    self::$_instance->response( $context );
                }
                else{
                    self::notify(sprintf('Invalid Endpoint Boot [%s]',$endpoint), 'error');
                }
            }
            else{
                self::notify(sprintf('Invalid Endpoint Menu [%s]',$endpoint), 'error');
            }
        }        
    }
    /**
     * @param String $endpoint
     * @param String $context
     * @return boolean
     */
    private static final function __runEndpoint( $endpoint , $context = '' ){
        if ( self::exists($endpoint)) {
            $action = get_query_var( $endpoint , '' );
            switch( self::type($endpoint)){
                case 'system':
                    if( self::debug()){
                        require sprintf('%s/components/views/public/html/list.php',self::__pluginDir(true));
                    }
                    break;
                case 'callable':
                    $call = self::__callable($endpoint);
                    $call( $action );
                    break;
                case 'application':
                    if(is_null(self::$_instance)){
                        self::$_instance = self::load($endpoint,'application');
                        if( !is_null(self::$_instance) ){
                            self::$_instance->response($action);
                        }
                    }
                    break;
            }
        }
    }
    
    protected static final function __registerRest( $endpoint ){

        add_action('rest_api_init', function () use($endpoint) {
            register_rest_route( sprintf('sprintf/%s/v1',$endpoint), '/context/action', array(
                'methods' => 'GET',
                'callback' => function( $data ){
                    return array(
                        'data' => $data,
                        'ts' => time(),
                        'response' => 'ok',
                    );
                },
            ));
        });

    }

    /**
     * @param string $endpoint
     */
    protected static final function __registerAjax( $endpoint ){
        if( self::hasAjax($endpoint)){
            $action = preg_replace('/-/', '_', $endpoint);
            add_action(sprintf('wp_ajax_%s', $action), function() use($endpoint) {
                CodersApp::run_ajax($endpoint);
                wp_die();
            });
            add_action(sprintf('wp_ajax_nopriv_%s', $action), function() use($endpoint) {
                CodersApp::run_ajax($endpoint);
                wp_die();
            });
        }
    }
    /**
     * Register an application instance into the admin menu
     * @param string $endpoint
     */
    protected static final function __registerAdmin( $endpoint ){
        if(is_admin()) {
            self::registerAdminMenu( self::setupFrameworkMenu() );

            foreach (self::list() as $endpoint) {
                if ( self::type($endpoint) === 'application' ) {
                    $app = self::load($endpoint, 'application');
                    if( !is_null($app) ){
                        self::registerAdminApp($app);
                        self::registerAdminMenu($app->setupAdminMenu());
                    }
                }
            }
        }
    }
    /**
     * @param string $action
     * @param array $args
     * @return array
     */
    protected function __ajax( $action = 'default' , array $args = array()){
        if(strlen($action)){
            $ajax = $action . '_ajax';
            if(method_exists($this, $ajax)){
                return $this->$ajax( $args );
            }
        }
        return array();
    }
    
    
    
    /**
     * @param string $endpoint
     * @return boolean
     */
    private static final function exists($endpoint){
        return array_key_exists($endpoint, self::$_endpoints);
    }
    /**
     * @param string $endpoint
     * @return string
     */
    private static final function type($endpoint) {
        $function = self::__callable($endpoint);
        switch (true) {
            case $endpoint === 'coders-framework': return 'system';
            case function_exists( $function ): return 'callable';
            default: return 'application';
        }
    }
    /**
     * @param string $endpoint
     * @return boolean
     */
    private static final function hasAjax( $endpoint ){
        return self::exists($endpoint) && self::$_endpoints[$endpoint]['ajax'];
    }
    /**
     * @return array
     */
    public static final function list( $showContents = FALSE ){
        return $showContents ? self::$_endpoints : array_keys(self::$_endpoints);
    }

    
    /**
     * 
     * @param string|path $plugin
     * @param boolean $useAjax
     * @param array $data
     */
    public static final function register( $plugin , $useAjax = false ){
        //$path = explode('/',  preg_replace('/\\\\/', '/', $plugin ) );
        $endpoint = self::__extract($plugin);
        //$endpoint = $path[count($path)-1];
        if( !array_key_exists($endpoint, self::$_endpoints)){
            self::$_endpoints[$endpoint] = array(
                'ajax' => $useAjax,
                'type' => self::type($endpoint),
            );
        }
    }
    /**
     * @return String
     */
    public static function path( $endpoint = '' ){
        return strlen($endpoint) ?
                ( self::exists($endpoint) ? self::__pluginDir() . $endpoint : '' ) :
                self::__pluginDir(true);
    }
    /**
     * Ruta URL de contenido de la aplicación
     * @return string
     */
    public final function url( ){
        return preg_replace( '/plugins/coders-framework/',
                sprintf('/plugins/%s/', $this->endPoint() ),
                plugin_dir_url(__FILE__) );
    }
    /**
     * @param string $endpoint
     * @param bool $asUrl
     * @return string
     */
    public static final function storage( $endpoint = '' , $asUrl = false ){
        if( strlen($endpoint) === 0 && !is_null(self::$_instance)){
            $endpoint = self::$_instance->endPoint();
        }
        $route = preg_replace('/\./', '/', $endpoint);
        $path = $asUrl ?
                get_site_url() . '/' :
                preg_replace('/\\\\/','/',ABSPATH );
        return sprintf('%swp-content/uploads/coders/%s',
                $path,
                strlen($route) ? $route . '/' : '' );
    }
    /**
     * Preload all core and instance components
     * @return CodersApp
     */
    private final function preload( ){
        
        foreach( $this->_components as $component ){
            if(strlen($component)){
                $route = explode('.', $component);
                $path = '';
                switch( count($route) ){
                    case 3: //load endpoint custom component
                        $path = sprintf('%s/components/%s/%s.php',
                                self::path( strlen($route[0]) > 0 ? $route[0] : $this->endPoint() ),
                                $route[1], $route[2]);
                        break;
                    case 2: //load framework component
                        $path = sprintf('%s/components/%s/%s.php',
                                CODERS_FRAMEWORK_BASE,
                                $route[0],$route[1]);
                        break;
                    case 1: //load framework classes
                        $path = sprintf('%s/classes/%s.php',
                                CODERS_FRAMEWORK_BASE,
                                $route[0]);
                        break;
                }
                if( strlen($path) && file_exists($path)){
                    require_once $path;
                }
                else{
                    self::notify( sprintf('Invalid component path [%s]',$path),'error');
                }
            }
        }
        //reset after loading
        $this->_components = array();

        return $this;
    }
    /**
     * @return string
     */
    public final function endPoint(){
        return $this->__extract($this->__root());
        //$dir = explode('/', $this->__root());
        //return $dir[ count( $dir ) - 1 ];
    }
    /**
     * @param String $route 
     */
    protected function response($route = '') {
        
        $this->preload();

        $context = is_admin() && strlen($route) === 0 ? 'admin' : preg_replace('/-/', '.', $route);

        \CODERS\Framework\Request::import(
                $this->endPoint(),
                $context)->route()->response();
    }
    /**
     * @param string $component
     * @return \CodersApp
     */
    protected final function require( $component ){
        if( !in_array( $component ,$this->_components ) ){
                $this->_components[ ] = $component;
        }
        return $this;
    }
    /**
     * @return array
     */
    protected function listAdminOptions(){
        $menu = $this->setupAdminMenu();
        $output = array($menu['slug']);
        if(array_key_exists('children', $menu)){
            foreach( $menu['children'] as $subMenu ){
                $output[] = $menu['slug'] . '-' . $subMenu['slug'];
            }
        }
        return $output;
    }
    /**
     * @return array
     */
    protected function setupAdminMenu(){
        return array();
    }
    /**
     * @return array
     */
    protected static final function setupFrameworkMenu(){
        return  array(
                //framework menu setup
                'parent' => '',
                'name' => __('Coders Framework','coders_framework'),
                'title' => __('Coders Framework','coders_framework'),
                'capability' => 'administrator',
                'slug' => 'coders-framework',
                'icon' => 'dashicons-grid-view',
                //'children' => array(),
                'position' => 100,
            );
    }
    /**
     * @param string $endpoint
     * @return \CodersApp
     */
    private static final function importAdminApp( $endpoint ){
        if( self::exists($endpoint) && array_key_exists('app', self::$_endpoints[$endpoint])){
            return self::$_endpoints[$endpoint]['app'];
        }
        return null;
    }
    /**
     * @param CodersApp $app
     * @return boolean
     */
    private static final function registerAdminApp( CodersApp $app ){
        $endpoint = $app->endPoint();
        if( is_admin() && self::exists($endpoint)){
            self::$_endpoints[$endpoint]['app'] =  $app;
            return true;
        }
        return false;
    }
    /**
     * @param array $menu
     * @return empty
     */
    private static final function registerAdminMenu( array $menu ){
       if( count( $menu ) === 0 ){
           return;
       }
       add_action('admin_menu', function() use( $menu ) {
            $endpoint = $menu['slug'];
            if (strlen($menu['parent']) === 0) {
                add_menu_page(
                        $menu['name'], $menu['title'],
                        $menu['capability'], $endpoint,
                        function() use($endpoint) { CodersApp::run_admin($endpoint); },
                        $menu['icon'], $menu['position']);

                $children = array_key_exists('children', $menu ) ? $menu['children'] : array();
                foreach ($children as $subMenu) {
                    $context = $subMenu['slug'];
                    add_submenu_page(
                            $endpoint,
                            $subMenu['name'],
                            $subMenu['title'],
                            $subMenu['capability'],
                            $endpoint . '-' . $context,
                            function() use( $endpoint, $context) {
                                CodersApp::run_admin( $endpoint, $context);
                            },
                            $subMenu['position']);
                }
            }
            else {
                add_submenu_page(
                        $menu['parent'], $menu['name'], $menu['title'],
                        $menu['capability'], $endpoint,
                        function() use($endpoint) { CodersApp::run_admin($endpoint); },
                        $menu['position']);
                }
        });
    }
    /** 
     * @param string|array $input
     * @return string|array
     */
    public static final function Class( $input ){
        
        return self::__cc($input);
    }
    /**
     * @author Coder01 <coder01@mnkcoder.com>
     * @param string $endpoint
     * @param string $file
     * @return \CodersApp
     */
    private static final function load( $endpoint , $file = 'application'  ){
        if ( strlen($endpoint) ){
            $path = sprintf('%s%s/%s.php' ,
                    self::__pluginDir(),
                    strtolower( $endpoint ),
                    strlen($file) ? $file : 'application' );
                
            if(file_exists($path)){
                require_once($path);
                return self::create($endpoint);
            }
            else{
                self::notify(sprintf('invalid endpoint path [%s]',$path));
            }
        }
        else{
            self::notify(sprintf('invalid endpoint [%s]',$endpoint));
        }
        return null;
    }
    /**
     * @param string $endpoint
     * @return \CodersApp
     */
    private static final function create( $endpoint ){
        if(strlen($endpoint)){
            $class = self::Class($endpoint);
            if (class_exists($class) && is_subclass_of($class, self::class, TRUE)) {
                return new $class( ) ;
            }
        }
        return null;
    }
    /**
     * @param string $endpoint
     * @param bool $rewrite
     */
    private static final function __install( $endpoint , $rewrite = false ){

        if( $rewrite ){
            global $wp_rewrite, $wp;
            foreach (CodersApp::list() as $ep) {
                $wp_rewrite->add_endpoint($ep,EP_ROOT);
                $wp->add_query_var($ep);
                $wp_rewrite->add_rule("^/$ep/?$", 'index.php?' . $ep . '=$matches[1]', 'top');
                $wp_rewrite->flush_rules( );
            }
        }
        
        $app = self::load($endpoint);
        if( !is_null($app)){
            $app->require('model')->preload()->install();
        }

        self::notify( self::path($endpoint ) , 'info' , true ,  $endpoint.'_install');
    }
    /**
     * 
     * @return \CodersApp
     */
    protected function install(){
        
        //fill in all install routines

        return $this;
    }
    /**
     * @param string $endpoint
     */
    private static final function __uninstall( $endpoint ){
        
        $app = self::load($endpoint);
        if (!is_null($app)) {
            $app->require('model')->preload()->uninstall();
        }

        self::notify( self::path($endpoint ) , 'info' , true , $endpoint.'_uninstall');
    }
    /**
     * @return \CodersApp
     */
    protected function uninstall(){

        //     * Fill in all uninstall routines
        
        return $this;
    }
    /**
     * @param string $message
     * @param string $type
     * @param boolean $admin
     */
    public static final function notify( $message , $type = '' , $admin = false , $register = false ) {
        
        if( $register ){
            if( !array_key_exists($type, self::$_messages)){
                self::$_messages[$type] = array();
            }
            self::$_messages[$type][] = $message;
        }
        else{
            if( $admin && is_admin()){
                $contents = array('message' => $message,'class' => $type);
                add_action('admin_notices', function() use( $contents ) {
                    printf('<div class="notice is-dimissible %s"><p>%s</p></div>',$contents['class'],$contents['message'] );
                });            
            }
            else{
                printf('<p class="notify is-dimissible %s">%s</p>' ,$type, $message );
            }
        }
    }
    /**
     * @param string $type
     * @return array
     */
    public static final function inbox( $type = '' ){
        if(strlen($type)){
            $output = array();
            foreach( self::$_messages as $type => $content ){
                foreach( $content as $msg ){
                    $output[] = $msg;
                }
            }
            return $output;
        }
        return self::$_messages;
    }
    /**
     * 
     */
    public static final function init(){
        if( !defined('CODERS_FRAMEWORK_BASE')){
            //first instance to call
            define('CODERS_FRAMEWORK_BASE', preg_replace('/\\\\/','/', __DIR__ ) );
            if( is_admin() || self::debug()){
                self::$_endpoints['coders-framework'] = array('ajax'=>false);
            }
            /* SETUP ROUTE | URL */
            add_action( 'init' , function(){
                if(wp_doing_ajax()){
                    foreach (CodersApp::list() as $endpoint) {
                        CodersApp::register_ajax( $endpoint );
                    }
                }
                elseif(wp_doing_cron()){
                    //setup cronjobs if required
                }
                elseif(is_admin()){
                    //admin
                    CodersApp::register_admin('coders-framework');
                }
                else{
                    //public
                    global $wp;
                    foreach (CodersApp::list() as $endpoint) {
                        $wp->add_query_var($endpoint);
                    }
                    /*SETUP RESPONSE*/
                    add_action( 'template_redirect', function(){
                        $route = (function(){
                            global $wp_query;
                            foreach (CodersApp::list() as $endpoint ){
                                if(array_key_exists($endpoint, $wp_query->query ) ){
                                    $wp_query->set('is_404', FALSE);
                                    return $endpoint;
                                }
                            }
                            return '';
                        })();
                        
                        if (strlen($route) > 0) {
                            CodersApp::run_endpoint($route);
                            exit;
                        }
                    } , 10 );
                }
            } , 10 );
        }
    }
    /**
     * @return boolean
     */
    public static final function debug(){
        return self::$_debug;
    }
    /**
     * @return \CodersApp | Boolean
     */
    public static final function instance(){
        return self::$_instance;
    }
}

CodersApp::init();

