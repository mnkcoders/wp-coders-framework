<?php namespace CODERS\Framework\Controllers;

defined('ABSPATH') or die;
/**
 * 
 */
final class Framework{
    /**
     * @var array
     */
    private $_reports = array(
        //notify content here
    );
    
    public function __construct() {
        
        //$this->execute($this->request());
    }
    /**
     * @param string $name
     * @return String
     */
    public function __get($name) {
        
        $att = sprintf('get%sAttribute', preg_replace('/_/', '', $name) );
    
        return method_exists($this, $att) ? $this->$att( ) : FALSE;
    }
    /**
     * @return array
     */
    protected function getInstancesAttribute(){
        return \CodersApp::listInstances();
    }
    /**
     * @return array
     */
    protected function getPluginDataAttribute(){
        return \CodersApp::pluginInfo();
    }
    /**
     * @return string
     */
    protected function getRepoPathAttribute(){
        return get_option('coders_root_path', '' );
    }
    /**
     * @return array
     */
    protected function getRequestAttribute(){
        return $this->request();
    }
    /**
     * 
     * @return array
     */
    private final function request(){
        
        $input = filter_input_array(INPUT_POST);
        
        return $input !== NULL ? $input : array();
    }
    /**
     * @param string $dir
     * @return boolean
     */
    private final function setPath( $dir ){
        
        $path = sprintf('%s/%s',ABSPATH,$dir);
        
        if(!file_exists($path)){

            //echo file_get_contents($path);
            return mkdir($path);
        }
        
        return TRUE;
    }

    /**
     * 
     * @param array $request
     * @return boolean
     */
    public final function execute(  ){
        
        $request = $this->request();
        
        $action = isset( $request['_action']) ? $request['_action'] : 'default';
        
        $callback = sprintf('%s_action',$action);
        
        return method_exists($this, $callback) ?
                $this->$callback( $request ) :
                        $this->default_action($request); 
    }


    
    /**
     * 
     * @param \CODERS\Framework\Request $request
     * @return boolean
     */
    private function default_action( array $request) {
        
        require sprintf('%s/view.php', __DIR__ );
        
        
        return TRUE;
    }
    /**
     * 
     * @param \CODERS\Framework\Request $request
     * @return booelan
     */
    protected final function set_root_action( array $request ){
        
        $path_option = \CodersApp::ROOT_PATH;
        
        if( isset( $request[$path_option])){
            if( $this->setPath($request[$path_option])){
                if( update_option($path_option, $request[$path_option], TRUE ) ){
                    //notify
                    $this->_reports['success'] = __('','coders_framework');
                }
                else{
                    //
                    $this->_reports['error'] = __('','coders_framework');
                }
            }
            else{
                //
                $this->_reports['error'] = __('','coders_framework');
            }
        }
        else{
            //
            $this->_reports['error'] = __('','coders_framework');
        }
        
        return $this->default_action($request);
    } 
}


