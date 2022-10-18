<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

/**
 * Request and Response manager
 * 
 * 
 */
class Request{
    
    const INPUT_GET = INPUT_GET;
    const INPUT_POST = INPUT_POST;
    const INPUT_SERVER = INPUT_SERVER;
    const INPUT_COOKIE = INPUT_COOKIE;
    const INPUT_SESSION = INPUT_SESSION;
    const INPUT_REQUEST = 10;
    
    private $_endpoint = '';
    private $_context = 'main';
    private $_action = 'default';
    private $_ts;

    /**
     * @param string $endpoint
     * @param string $route
     */
    protected function __construct( $endpoint , $route = '' ) {
        $this->_ts = time();
        $this->_endpoint = $endpoint;
        
        $request = explode('.', strlen($route) ? $route : '' );
        if( strlen($request[0]) ){
            $this->_context = $request[0];
        }
        if( count($request) > 1 ){
            $this->_context =  $request[1];
        }
        if(count($request) > 2){
            $this->_action =  $request[2];
        }
        //var_dump(strval($this));
    }
    /**
     * @return string
     */
    public function __toString() {
        return sprintf('[%s] %s', $this->_ts , $this->route( TRUE ) );
    }
    /**
     * @param string $name
     * @param array $arguments
     * @return bool
     */
    public final function __call($name, $arguments) {
        
        $call = sprintf('%s_action',$name);
        
        return method_exists($this, $call) ?
                $this->$call( $arguments ) : 
                $this->error_action( $arguments );
    }
    /**
     * Obtiene un valor de la Request
     * @param string $name
     * @return string
     */
    public final function __get($name) {
        return $this->get($name,'');
    }
    /**
     * Importa un parámetro del evento
     * @param string $input
     * @param mixed $default
     * @return mixed
     */
    public final function get( $input, $default = FALSE , $type = INPUT_REQUEST ){
        
        switch( $type ){
            case self::INPUT_GET:
            case self::INPUT_POST:
            case self::INPUT_COOKIE:
            case self::INPUT_SERVER:
                $input = filter_input( $type, $input );
                return !is_null($input) ? $input : $default;
            case self::INPUT_REQUEST: default:
                $get = $this->get($input,FALSE,self::INPUT_GET);
                return $get !== FALSE ? $get : $this->get($input,FALSE,self::INPUT_POST);
        }
    }
    /**
     * @param int $type
     * @return array
     */
    public final function list( $type = self::INPUT_REQUEST ){
        
        switch( $type ){
            case self::INPUT_REQUEST:
                return array_merge(self::__INPUT(self::INPUT_GET),self::__INPUT(self::INPUT_POST));
            case self::INPUT_GET:
            case self::INPUT_POST:
                return self::__INPUT( $type );
            default:
                //hide other imputs atm
                return array();
        }
    }
    /**
     * @param $full
     * @return string
     */
    public final function route( $full = FALSE ){
        return $full ?
            $this->endPoint() . '.' . $this->action( true ) :
            $this->action( true ) ;
    }

    /**
     * Retorna un valor numérico
     * @param string $property
     * @param int $default
     * @return int
     */
    public final function getInt( $property, $default = 0 ){
        return intval( $this->get($property, $default ) );
    }
    /**
     * Retorna una lista de valores serializados
     * @param string $property Propiedad a extraer
     * @param string $separator Separador de los valores serializados
     * @return array
     */
    public final function getArray( $property, $separator = ',' ){
        return explode($separator, $this->get($property, ''));
    }
    /**
     * Establece una cookie en WP agregando el prefijo de la aplicación para evitar colisiones
     * 
     * @param string $cookie
     * @param mixed $value
     * @param int $time
     * @return bool
     */
    public final function setCookie( $cookie, $value = null, $time = 10 ){
        
        if(current_filter() === 'wp' ){
            
            $maximum = 10;

            if( $time > $maximum ){
                //máximo a 50 minutos
                $time = $maximum;
            }

            return setcookie(
                    self::attachPrefix($cookie,$this->_endpoint), $value,
                    time() + ( $time  * 60) );
        }

        return false;
    }
    /**
     * @return int WP User ID
     */
    public final function UID(){ return get_current_user_id(); }
    /**
     * @return string
     */
    public final function SID(){ return wp_get_session_token(); }
    /**
     * @return string|NULL Dirección remota del cliente
     */
    public static final function remoteAddress(){
        
        return filter_input(self::INPUT_SERVER, 'REMOTE_ADDR');
    }
    /**
     * @return string Event Type
     */
    public final function action( $full = FALSE ){
        
        return $full ?
                $this->_context.'.'.$this->_action :
                $this->_action;
    }
    /**
     * @param bool $cc Camel Case
     * @return String
     */
    private final function context( $cc = FALSE ){ return $cc ? ucfirst($this->_context) : $this->_context; }
    /**
     * @param bool $cc Camel Case
     * @return String
     */
    public final function endPoint( $cc = FALSE ){
        return $cc ?
                preg_replace('/ /','', ucwords(preg_replace('/-/',' ', $this->_endpoint ))) :
                $this->_endpoint;
    }
    /**
     * @return boolean
     */
    //public final function isAdmin(){ return is_admin(); }
    /**
     * @param array $args
     * @return String
     */
    public static final function parseUrlArgs( array $args ){
        
        $output = array();
        
        foreach( $args as $var => $val ){
            $output[] = sprintf('%s=%s',$var,$val);
        }
        
        return implode('&', $output);
    }
    /**
     * @param array $request
     * @param boolean $is_admin
     * @return string|URL
     */
    public function getUrl( ){

        $request = array();
        
        $is_admin = is_admin();
        $url = $is_admin ? admin_url() : get_site_url();
        
        if( $is_admin ){
            // admin-page = endpoint-controller
            $request['page'] = $this->endPoint() .'-'. $this->context();
            $request['action'] = $this->action();
            $url .=  'admin.php';
        }
        else{
            $route = array();
            if( $this->action() !== 'default' ){
                $route[] = $this->context();
                $route[] = $this->action();
            }
            elseif($this->context() !== 'main' ){
                $route[] = $this->context();
            }
            $url .= sprintf( '/%s/%s/' , $this->endPoint() , implode('-' , $route ) );
        }
        
        return self::URL( $request , $url );
    }
    /**
     * @param string $action
     * @param array $args
     * @return string
     */
    public static final function createLink( $action , array $args = array() ){
        $route = explode('.', $action);
        $endpoint_url = get_site_url() . '/' . $route[0];
        if(count($route) > 1 ){
            for( $i = 1 ; $i < count( $route ) ; $i++ ){
                $endpoint_url .= ($i === 1 ) ? '/' . $route[$i] : '-' .$route[$i];
            }
        }
        //var_dump($endpoint_url);
        return self::URL($args,$endpoint_url);
    }
    /**
     * @param array $params
     * @param string $url
     * @return String|URL
     */
    public static final function URL( array $params , $url = '' ){

        $serialized = array();
        if(strlen($url) === '' ){
            $url = get_site_url();
        }
        foreach( $params as $var => $val ){
            $serialized[] = sprintf('%s=%s',$var,$val);
        }
        
        return count( $serialized ) ? $url . '?' . implode('&', $serialized ) : $url;
    }
    /**
     * @param int $input
     * @return array
     */
    private static final function __INPUT( $input = self::INPUT_GET ){
        $vars = filter_input_array($input);
        return !is_null($vars) ? $vars : array();
    }
    /**
     * @param string $endpoint
     * @param string $route
     * @return \CODERS\Framework\Request
     */
    public static final function import( $endpoint , $route = '' ){
        return new Request( $endpoint , $route );
    }
    /**
     * @param string $endpoint
     * @param string $route
     * @return \CODERS\Framework\Request
     */
    public static final function ajax( $endpoint , $route = 'default' ){
        return new Request( $endpoint , 'ajax.'.$route );
    }

    /**
     * @param string $route
     * @param string $endpoint (target endpoint, self by default)
     * @return \CODERS\Framework\Request
     */
    public final function redirect( $route  = '' , $endpoint = '' ){
        
        return new Request(strlen($endpoint) ? $endpoint : $this->endPoint() , $route );
    }
    /**
     * @return String
     */
    private final function __contextPath(){
            return sprintf('%s/components/controllers/%s.php',
                    \CodersApp::path($this->endPoint()) ,
                    $this->context() );
    }
    /**
     * @return String
     */
    private final function __contextClass(){
            return sprintf('\CODERS\%s\%sController',
                    $this->endPoint(TRUE),
                    $this->context(TRUE));
    }
    /**
     * @return \CODERS\Framework\Request 
     */
    public final function response( ){
        
        switch( true ){
            //do not allow to execute admin controllers out of context
            case is_admin() && $this->context() !== 'admin':
                return false;
            case !is_admin() && $this->context() === 'admin':
                return false;
        }
        
        $path = $this->__contextPath();
        if(file_exists($path)){
            require_once $path;
            $class = $this->__contextClass();
            if(class_exists($class) && is_subclass_of($class, self::class)){
                $controller = new $class( $this->endPoint() ,$this->route());
                $action = $this->action();
                return $controller->$action( $this->list());
            }
            elseif (\CodersApp::debug()) {
                \CodersApp::notice(sprintf('Invalid context class %s', $class), 'error');
            }
        }
        elseif (\CodersApp::debug()) {
            \CodersApp::notice(sprintf('Invalid context path %s', $path), 'error');
        }
        return FALSE;
    }
    /**
     * @param string $model
     * @return \CODERS\Framework\Model | boolean
     */
    protected final function importModel( $model ){
        return  class_exists('\CODERS\Framework\Model') ?
            \CODERS\Framework\Model::create( sprintf('%s.%s',$this->endPoint(),$model) ) :
                null;
    }
    /**
     * @param string $view
     * @return \CODERS\Framework\View | boolean
     */
    protected final function importView( $view ){
        return  class_exists('\CODERS\Framework\View') ?
            \CODERS\Framework\View::create( sprintf('%s.%s',$this->endPoint(),$view) ) :
                null;
    }
    /**
     * @param array $args
     * @return boolean
     */
    protected function error_action( array $args = array( ) ){
        var_dump($args);
        return FALSE;
    }
    /**
     * @param array $args
     * @return boolean
     */
    protected function default_action( array $args = array() ){
        
        $view = $this->importView('main');
        if( !is_null($view) ){
            $view->show();
            return TRUE;
        }
        else{
            return $this->error_action( array(
                'error' => sprintf('invalid view for [%s]', $this->route(true)),
            ) );
        }

        return FALSE;
    }
}


