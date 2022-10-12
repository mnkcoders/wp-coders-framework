<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

/**
 * Service setup
 */
abstract class Service{

    const TYPE_DEFAULT = 'default';
    /**
     * @var Services[]
     */
    private static $_services = array(
        //
    );
    /**
     * @var string
     */
    private $_type = self::TYPE_DEFAULT;
                
    private $_settings = array();
    /**
     * @param array $settings
     */
    protected function __construct( array $settings = array() ) {
        
        foreach ($settings as $var => $val ){
            switch($var ){
                case 'type':
                    $this->_type = $val;
                    break;
                default:
                    $this->_settings[$var] = $val;
                    break;
            }
        }
    }
    /**
     * @param \CODERS\Framework\Service $service
     * @return boolean
     */
    private static final function __register( Service $service ){
        self::$_services[ $service->type() ][ ] = $service ;
        return true;
    }

    /**
     * Ejecuta el servicio
     * @return bool Resultado de la ejecución del servicio
     */
    public function dispatch(){
        
        return TRUE;
        
    }
    /**
     * @return String
     */
    public function type(){
        return $this->_type;
    }
    /**
     * @param array $route
     * @return string
     */
    private static final function __importClass( array $route ){
        $namespace = \CodersApp::Class( $route );
        return count($namespace) > 1 ?
                    sprintf('\CODERS\%s\%sService', $namespace[0], $namespace[1] ) :
                    sprintf('\CODERS\Framework\Services\%s', $namespace[0] );
    }
    /**
     * @param array $route
     * @return String|PAth
     */
    private static final function __importPath( array $route ){
        return count($route) > 1 ?
                    sprintf('%s/components/services/%s.php', \CodersApp::path($route[0]), $route[1]) :
                    sprintf('%s/components/services/%s.php', \CodersApp::path(), $route[0]);
    }
    /**
     * @param string $service
     * @param array $data 
     * @return \CODERS\Framework\Service | boolean
     */
    public static final function create( $service, array $data = array() ){
        $namespace = explode('.', $service);
        $path = self::__importPath($namespace);
        if(file_exists($path)){
            require_once $path;
            $class = self::__importClass($namespace);
            if(class_exists($class) && is_subclass_of($class, self::class)){
                $svc = new $class( $data );
                if( self::__register($svc) ){
                    return $svc;
                }
            }
        }
        return false;
    }
}