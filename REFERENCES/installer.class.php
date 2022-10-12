<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

/**
 * 
 */
abstract class Installer{
    
    private $_name, $_key;
    
    private $_output = array( /* record messages here */ );
    /**
     * @param string $name
     * @param string $key
     */
    protected final function __construct( $name , $key ) {
        
        $this->_key = $key;
        
        $this->_name = $name;
    }
    /**
     * @return string
     */
    protected final function getKey(){
        return $this->_key;
    }
    /**
     * @return string
     */
    protected final function getName(){
        return $this->_name;
    }
    /**
     * Register a message to show up in activation/deactivation header
     * @param string $message
     * @return \CODERS\Framework\Installer
     */
    protected final function log( $message ){
        
        $this->_output[] = $message;
        
        return $this;
    }
    /**
     * Create all app data
     * @return boolean
     */
    public function install(){
        
        $this->_output[] = __('Setup complete!','coders_framework');
        
        return TRUE;
    }
    /**
     * Remove all app data
     * @return boolean
     */
    public function uninstall(){
        
        $this->_output[] = __('Uninstall complete!','coders_framework');
        
        return TRUE;
    }
    /**
     * @return array
     */
    public final function report(){
        
        return $this->_output;
    }
    /**
     * @param string $app
     * @param string $key
     * @return boolean|\CODERS\Framework\Installer
     */
    public static final  function create( $app , $key ){
            
        $path = sprintf('%s/wp-content/plugins/%s/setup/installer.php',ABSPATH,$app);
            
            if(file_exists($path)){

                require_once $path;
                
                $class = '\CODERS\Framework\Setup';

                if( class_exists($class) && is_subclass_of($class, self::class)){
                    return new $class( $app , $key );
                }
            }
            
            return FALSE;
    }
}


