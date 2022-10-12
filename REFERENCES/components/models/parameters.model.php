<?php namespace CODERS\Framework\Models;

defined('ABSPATH') or die;

/**
 * Gestión de parámetros de la aplicación
 */
class ParametersModel implements \CODERS\Framework\IModel{
    
    private $_parameters = array();
    
    private $_type;
    
    private final function __construct( $type, array $parameters ) {
        
        $this->_type = $type;
        
        $this->_parameters = $parameters;
    }
    /**
     * @return string
     */
    public function __toString() {
        return get_class($this);
    }
    /**
     * Obtiene la etiqueta de un parámetro
     * @param string $var
     * @param mixed $default
     * @return string
     */
    public function get($var, $default = null) {
        switch( $var ){
            case 'list_values':
                return $this->listValues();
            case 'list_parameters':
                return $this->listParameters();
        }

        return isset( $this->_parameters[$var] ) ? 
            $this->_parameters[ $var ] :
                $default;
    }
    /**
     * @return array
     */
    public final function listParameters(){
        return $this->_parameters;
    }
    /**
     * @return array
     */
    public final function listValues(){
        return array_keys($this->_parameters);
    }
    /**
     * @return string
     */
    public final function getType(){
        return $this->_type;
    }
    /**
     * @param string $type
     * @return \TripManParametersModel
     */
    public static final function loadParameters( $type ){
        
        $db = new TripManDBProvider();
        
        $data = $db->select(
                TripManDBProvider::TABLE_PARAMETERS,
                array('value','label'),array('type'=>$type),
                1,0,array('value'));
        
        if( count($data) ){
            
            $input = array();
            foreach( $data as $row ){
                $input[$row['value']] = $row['label'];
            }
            
            return new TripManParametersModel($type, $input);
        }
    }
    /**
     * Elimina un parámetro
     * @param string $parameter
     * @return boolean
     */
    public final function remove( $parameter ){
        $db = new TripManDBProvider();
        $deleted = $db->delete(
                TripManDBProvider::TABLE_PARAMETERS,
                array('type'=>'agent_group','value'=>$parameter));
        if( $deleted >  0 ){
            if( isset($this->_parameters[$parameter]) ){
                unset( $this->_parameters[$parameter] );
            }
            return true;
        }
        return false;
    }
    /**
     * Crea un nuevo parámetro
     * @param string $parameter
     * @param string $label
     * @return bool
     */
    public final function create( $parameter, $label ){
        
        if( strlen($this->_type) ){
            $db = new TripManDBProvider();

            $result = $db->insertIgnore(
                    TripManDBProvider::TABLE_PARAMETERS,
                    array('type'=>$this->_type,'value'=>$parameter,'label'=>$label));

            if( $result > 0 ){
                $this->_parameters[$parameter] = $label;
                return true;
            }
        }
        
        return false;
    }
    /**
     * @param string $param
     * @return boolean
     */
    public function has($param){
        return isset($this->_parameters[$param]);
    }
    /**
     * @return array
     */
    public function toArray(): array {
        return $this->listParameters();
    }
}


