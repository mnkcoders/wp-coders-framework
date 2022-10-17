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
     * @var \CodersApp | boolean
     */
    private static $_instance = FALSE;
    /**
     * @var array
     */
    private static $_endpoints = array( );
    /**
     * INSTANCE COMPONENTS
     * @var array
     */
    private $_components = array(
        'request',
    );
    /**
     * 
     */
    protected function __construct( $admin = FALSE  ) {

        if( !$admin ){
            $this->importComponents( );        
        }
    }
    /**
     * @return array
     */
    protected function setupAdminMenu(){
        $menu = self::setupFrameworkMenu();
        $menu['name'] = $this->endPoint();
        $menu['title'] = $this->endPoint();
        $menu['slug'] = $this->endPoint();
        return $menu;
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
                'position' => 100,
            );
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
    protected final function __path(){
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
     * @return string
     */
    protected static final function __pluginsDir(){
        return preg_replace('/\\\\/','/', plugin_dir_path(__DIR__ ) );
    }
    /** 
     * @return string
     */
    protected static final function __rootDir(){
        return preg_replace('/\\\\/','/', plugin_dir_path( __FILE__ ) );
    }
    /**
     * @return string
     */
    protected final function __endpoint() {
        
        $dir = explode('/', $this->__path());
        return $dir[ count( $dir ) - 1 ];
    }
    /**
     * @return string
     */
    public final function __toString() {
        return $this->__endpoint();
    }
    /**
     * @param string $endpoint
     * @return string
     */
    private static final function __callable( $endpoint ){
        return preg_replace('/-/', '_', $endpoint) . '_application';
    }
    /**
     * @param string $endpoint
     * @return boolean
     */
    public static final function exists($endpoint){
        return array_key_exists($endpoint, self::$_endpoints);
    }
    /**
     * @param string $endpoint
     * @return string
     */
    private static final function __type($endpoint) {
        $function = self::__callable($endpoint);
        switch (true) {
            case $endpoint === 'coders-framework': return 'system';
            case function_exists( $function ): return 'callable';
            default: return 'application';
        }
    }

    /**
     * @return array
     */
    public static final function list( $showContents = FALSE ){
        return $showContents ? self::$_endpoints : array_keys(self::$_endpoints);
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
        //return preg_replace('/\s/', '', ucwords( preg_replace('/_/', ' ', $name ) ) );
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
                        return static::$call( $arguments[0] );
                    }
                }
                return false;
            case preg_match('/^service_/', $name):
                return $this->__runService(substr($name, 8 , strlen($name) - 8), $arguments );
        }
    }
    /**
     * @param string $action
     * @param array $args
     * @return array
     */
    protected function __ajax( $action = 'default'){
        if(strlen($action)){
            $ajax = $action . '_ajax';
            if(method_exists($this, $ajax)){
                return $this->$ajax( );
            }
        }
        return array();
    }
    /**
     * 
     * @param string|path $plugin
     * @param boolean $useAjax
     * @param array $data
     */
    public static final function register( $plugin , $useAjax = false ){
        $path = explode('/',  preg_replace('/\\\\/', '/', $plugin ) );
        $endpoint = $path[count($path)-1];
        if( !array_key_exists($endpoint, self::$_endpoints)){
            self::$_endpoints[$endpoint] = array(
                'ajax' => $useAjax,
                'type' => self::__type($endpoint),
            );
        }
    }
    /**
     * @return string
     */
    public static final function appRoot( $endpoint ){
       
        return (array_key_exists($endpoint, self::$_endpoints)) ?
                self::__pluginsDir() . $endpoint :
                '' ;
    }
    /**
     * @return String
     */
    public static function path( $endpoint = '' ){
        return strlen($endpoint) ?
                self::appRoot($endpoint) :
                self::__pluginsDir();
    }
    /**
     * Ruta URL de contenido de la aplicación
     * @return string
     */
    public final function appURL( ){
        
        return preg_replace( '/plugins/coders-framework/',
                sprintf('/plugins/%s/', $this->endPoint() ),
                plugin_dir_url(__FILE__) );
    }
    /**
     * Preload all core and instance components
     * @return CodersApp
     */
    private final function importComponents( ){
        
        foreach( $this->_components as $component ){
            
            $path = $this->componentPath( $component );
            
            if( FALSE !== $path && file_exists($path)){
                require_once $path;
            }
            else{
                //throw new Exception( sprintf( 'INVALID_COMPONENT [%s]',$component ) );
            }
        }
        return $this;
    }
    /**
     * 
     * @return string
     */
    public final function endPoint(){ return $this->__endpoint(); }
    /**
     * @param String $route 
     */
    protected function response($route = '') {
        //if (!is_admin()) { }
        $request = \CODERS\Framework\Request::import(
                        $this->__endpoint(),
                        preg_replace('/-/', '.', $route));
        
        //var_dump($request);
        
        return $request->response();
    }
    /**
     * @return int
     */
    public final function countComponents(){
       
        return count( $this->_components );
    }
    /**
     * @param string $component
     * @return \CodersApp
     */
    protected function import( $component ){
        
        if( !in_array( $component ,$this->_components ) ){
                $this->_components[ ] = $component;
        }
        
        return $this;
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
     * @param array $input
     * @return array
     */
    private static final function __runAjax( $endpoint ){
        if( self::exists($endpoint)){
            $setup = self::$_endpoints[$endpoint];
            if( $setup['ajax'] ){
                $app = self::load($endpoint, 'application' );
                $input = filter_input_array(INPUT_POST);
                $route = !is_null($input) ? $input['route'] : 'default';
                $response = $app !== false ? $app->__ajax( $route , !is_null($input) ? $input : array() ) : array( 'invalid_endpoint' => $endpoint);

                print json_encode($response);
                //wp_die();
            }
        }
        wp_die();
    }
    /**
     * @param string $endpoint
     */
    protected static final function __runAdmin( $endpoint ){
        if( is_admin() && self::exists($endpoint)){
            if( $endpoint === 'coders-framework' ){
                //$appList = self::list(true);
                require sprintf('%s/admin/admin.php',self::__rootDir());
            }
            else{
                $list = self::list(true);
                $app = $list[$endpoint]['app'];
                $app->importComponents();
                var_dump($app);
            }
        }        
    }
    /**
     * @param String $endpoint
     * @return boolean
     */
    protected static final function __runEndpoint( $endpoint ){
        if ( self::exists($endpoint)) {
            $action = get_query_var( $endpoint , '' );
            //$setup = self::$_endpoints[$endpoint];
            $call = self::__callable($endpoint);
            //$setup = self::$_endpoints[$endpoint];
            if( $endpoint === self::class ){
            }
            elseif(function_exists ($call) ){
                $call( $action );
            }
            else{
                $app = self::load($endpoint,'application');
                if( false !== $app ){
                    $app->response($action);
                }
                elseif(self::debug()){
                    printf('<p>Invalid App loader %s</p>',$endpoint);
                }
            }
        }
    }
    /**
     * @param string $endpoint
     */
    protected static final function __registerAjax( $endpoint ){
        $action = preg_replace('/-/', '_', $endpoint);
        $hooks = array(
            sprintf('wp_ajax_%s', $action),
            sprintf('wp_ajax_nopriv_%s', $action),
        );
        foreach ($hooks as $hook) {
            add_action($hook, function() use($endpoint) {
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
                if ( self::__type($endpoint) === 'applicaton' ) {
                    $app = self::load($endpoint, 'application');
                    if( FALSE !== $app ){
                        self::registerAdminMenu($app->setupAdminMenu());    
                        self::$_endpoints[$endpoint]['app'] = $app;
                    }
                }
            }
        }
    }
    /**
     * @param array $menu
     * @return empty
     */
    private static final function registerAdminMenu( array $menu ){
       add_action('admin_menu', function() use( $menu ) {
            $endpoint = $menu['slug'];
            if (strlen($menu['parent']) === 0) {
                add_menu_page(
                        $menu['name'], $menu['title'],
                        $menu['capability'], $menu['slug'],
                        function() use($endpoint) { CodersApp::run_admin($endpoint); },
                        $menu['icon'], $menu['position']);

                $children = isset($menu['children']) ? $menu['children'] : array();
                foreach ($children as $child) {
                    $child_slug = $endpoint . '-' . $child['slug'];
                    add_submenu_page(
                            $menu['name'], $child['name'], $child['title'],
                            $child['capability'], $child['slug'],
                            function() use( $child_slug ) { CodersApp::run_admin($child_slug); },
                            $child['position']);
                }
            }
            else {
                add_submenu_page(
                        $menu['parent'], $menu['name'], $menu['title'],
                        $menu['capability'], $menu['slug'],
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
        
        if(is_array($input)){
            //fix this mess ¬_¬
            return explode(' ',
                    ucwords(preg_replace('/\~/',' ',(preg_replace('/\s/','',
                            ucwords(preg_replace('/\-/',' ',
                                    implode('~', $input ) ) ) ) ) ) ) );
        }

        return preg_replace('/ /','', ucwords(preg_replace('/-/',' ', $input ))) ;
    }
    /**
     * @author Coder01 <coder01@mnkcoder.com>
     * @param string $endpoint
     * @param string $file
     * @return \CodersApp|Bool
     */
    private static final function load( $endpoint , $file = 'application'  ){
        if ( strlen($endpoint) ){
            $path = sprintf('%s%s/%s.php' ,
                    self::__pluginsDir(),
                    strtolower( $endpoint ),
                    strlen($file) ? $file : 'application' );
                
            if(file_exists($path)){
                require_once($path);
                return self::create($endpoint );
            }
            else{
                self::notice(sprintf('invalid endpoint path [%s]',$path));
            }
        }
        else{
            self::notice(sprintf('invalid endpoint [%s]',$endpoint));
        }
        return FALSE;
    }
    /**
     * @author Coder01 <coder01@mnkcoder.com>
     * @param string $endpoint
     * @return \CodersApp|Bool
     */
    private static final function create( $endpoint){
        if ( !is_null(self::$_instance) && strlen($endpoint) ){
            $class = self::Class($endpoint);
            if (class_exists($class) && is_subclass_of($class, self::class, TRUE)) {
                self::$_instance = new $class( is_admin()  );
            }
        }
        return self::$_instance;
    }
    /**
     * @param String $component
     * @return String | boolean
     */
    protected final function componentPath( $component ){

        $type = explode('.', $component);
        
        switch( count($type) ){
            case 3:
                return sprintf('%s%s/components/%s/%s.php',
                        self::__pluginsDir(),
                        $type[0] === 'local' ? $this->endPoint() : $type[0],
                        $type[1], $type[2]);
            case 2:
                return sprintf('%s/components/%s/%s.php',
                        CODERS_FRAMEWORK_BASE,
                        $type[0],$type[1]);
            case 1:
                return sprintf('%s/classes/%s.php',
                        CODERS_FRAMEWORK_BASE,
                        $type[0]);
        }
            
        return FALSE;
    }
    /**
     * @param string $plugin
     * @param callable $callback
     * @param string $key 
     */
    public static final function __install( $plugin ){
        
        if(!is_admin()){
            return;
        }
        
        self::notice( self::path($plugin ) );
    }
    /**
     * @param string $plugin
     * @param callable $callback
     * @param string $key
     */
    public static final function __uninstall( $plugin ){
        
        if(!is_admin()){
            return;
        }
       
        self::notice( self::path($plugin) );
    }
    /**
     * @param string $message
     * @param string $class
     */
    public static final function notice( $message , $class = '') {
        if(is_admin()){
            add_action('admin_notices', function() use( $message , $class ) {
                printf('<div class="notice is-dimissible %s"><p>%s</p></div>',$class,$message );
            });            
        }
        elseif(self::debug ()){
            printf('<p class="notify %s">%s</p>' ,$class, $message );
        }
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
                    foreach (CodersApp::list(true) as $endpoint => $setup) {
                        if( $setup['ajax'] ){
                            CodersApp::register_ajax( $endpoint );
                        }
                    }
                }
                elseif(wp_doing_cron()){
                    //setup cronjobs if required
                }
                elseif(is_admin()){
                    //admin
                    //CodersApp::initAdminMenu();
                    CodersApp::register_admin('coders-framework');
                }
                else{
                    //public
                    global $wp, $wp_rewrite;
                    $list = CodersApp::list(true);
                    foreach ($list as $endpoint => $setup) {
                        add_rewrite_endpoint($endpoint, EP_ROOT);
                        $wp->add_query_var($endpoint);
                        $wp_rewrite->add_rule("^/$endpoint/?$", 'index.php?' . $endpoint . '=$matches[1]', 'top');
                    }
                    if( count( $list ) ){
                        $wp_rewrite->flush_rules();
                    }                    
                    /*SETUP RESPONSE*/
                    add_action( 'template_redirect', function(){
                        $endpoint = (function(){
                            global $wp_query;
                            foreach (CodersApp::list() as $endpoint ){
                                if(array_key_exists($endpoint, $wp_query->query ) ){
                                    $wp_query->set('is_404', FALSE);
                                    return $endpoint;
                                }
                            }
                            return '';
                        })();
                        
                        if (strlen($endpoint) > 0) {
                            CodersApp::run_endpoint($endpoint);
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



