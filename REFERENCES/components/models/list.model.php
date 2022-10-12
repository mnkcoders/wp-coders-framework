<?php namespace CODERS\Framework\Models;

defined('ABSPATH') or die;
/**
 * Modelo de listado de registros para gestionar colecciones
 */
abstract class ListModel extends \CODERS\Framework\Dictionary implements \CODERS\Framework\IModel{

    private $_rows = array();
    
    private $_cursor = -1;
    
    private $_table = null;
    private $_index = null;
    private $_identifier = null;
    
    public function __construct( array $data = array( ) ) {
        
    }
    /**
     * @return string
     */
    public function __toString() {
        return get_class($this);
    }
    /**
     * 
     * @param string $name
     * @param string $type
     * @param array $properties
     * @return boolean
     */
    protected final function addField($name, $type = parent::FIELD_TYPE_TEXT, array $properties = null) {
        if( parent::addField($name, $type, $properties) ){
            
            switch( $type ){
                case parent::FIELD_TYPE_ID:
                    if( is_null( $this->_index ) ){
                        $this->_index = $name;
                    }
                    break;
            }
            return true;
        }
        
        return false;
    }
    /**
     * Entrada de datos
     * @param array $row
     */
    protected final function addRow( $row ){
        
        $input = array();
        
        foreach( $row as $field => $value ){
            if( $this->hasField( $field ) ){
                switch( $this->getFieldType($field)){
                    case self::FIELD_TYPE_ID:
                    case self::FIELD_TYPE_NUMBER:
                        $input[$field] = intval($value);
                        break;
                    case self::FIELD_TYPE_FLOAT:
                    case self::FIELD_TYPE_PRICE:
                        $input[$field] = floatval($value);
                        break;
                    default:
                        $input[$field] = $value;
                        break;
                }
            }
        }
        
        if( !is_null($this->_index) && isset($input[$this->_index]) ){
            $id = $input[$this->_index];
            $this->_rows[$id] = $input;
        }
        else{
            $this->_rows[] = $input;
        }
    }
    /**
     * Define el origen de datos, indice y campo principal para etiquetar
     * @param string $table
     * @param string $index
     * @param string $title
     */
    protected final function registerDataSource( $table, $index, $title = null ){
        $this->_table = $table;
        $this->_index = $index;
        $this->_identifier = !is_null($title) ? $title : $this->_index;
    }
    /**
     * 
     * @param int $rowId
     * @return array | null
     */
    public function getRow( $rowId ){
        return count( $this->_rows) > $rowId ?
            $this->_rows[ $rowId ] : null;
    }
    /**
     * Obtiene el valor de un registro
     * @param int $rowId
     * @param string $field
     * @param mixed $default
     * @return mixed
     */
    public function getRowValue( $rowId , $field, $default = null ){
        
        $row = $this->getRow($rowId);
        
        if( !is_null($row) ){
            return isset( $row[ $field ]) ? $row[ $field ] : $default;
        }
        
        return $default;
    }
    /**
     * Valores del modelo
     * @param string $var
     * @param mixed $default
     * @return mixed
     */
    public function get($var, $default = null) {
        
        if(is_numeric($var)){
            if( count($this->_rows) > $var ){
                return $this->_rows[$var];
            }
        }
        elseif(is_string($var)){
            switch( $var ){
                case 'table':
                    return $this->_table;
                case 'index':
                    return $this->_index;
                case 'title':
                    return $this->_identifier;
                case 'list':
                case 'rows': //esta quedarà obsoleta
                    return $this->_rows;
            }
        }
        
        $method = sprintf('get_%s_data',  strtolower($var));
        
        return method_exists($this,$method) ? $this->$method() : $default;
    }
    /**
     * Obtiene el origen o listado de parámetros de un campo con lista de valores
     * @param string $field
     * @return array
     */
    public function getSource( $field ){
        return $this->getMeta($field, 'source', array());
    }
    /**
     * Siguiente registro
     * @return array | null
     */
    public function next(){
        if( count( $this->_rows ) ){
            if( $this->_cursor < count( $this->_rows)-1){
                $this->_cursor++;
                return $this->_rows[$this->_cursor];
            }
        }
        else{
            $this->_cursor = -1;
        }
        return null;
    }
    /**
     * Último registro
     * @return array | null
     */
    public function last(){
        if( count( $this->_rows ) ){
            $this->_cursor = count($this->_rows) - 1;
            return $this->_rows[$this->_cursor];
        }
        else{
            $this->_cursor = -1;
        }
        return null;
    }
    /**
     * Registro previo
     * @return array | null
     */
    public function prev(){
        if( count( $this->_rows ) ){
            if( $this->_cursor > 0 ){
                $this->_cursor--;
                return $this->_rows[$this->_cursor];
            }
        }
        else{
            $this->_cursor = -1;
        }
        return null;
    }
    /**
     * Primer registro
     * @return array | null
     */
    public function first(){
        if( count( $this->_rows ) ){
            $this->_cursor = 0;
            return $this->_rows[$this->_cursor];
        }
        else{
            $this->_cursor = -1;
        }
        return null;
    }
    /**
     * @param int $page Página
     * @param mixed $order Orden de los recursos
     * @param mixed $filters Filtros
     */
    abstract public function paginate( $page = 1 , $order = null, $filters = null );
}