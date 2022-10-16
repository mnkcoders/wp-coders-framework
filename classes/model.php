<?php namespace CODERS\Framework;
 
defined('ABSPATH') or die;
/**
 * 
 */
abstract class Model{
    
    const TYPE_CHECKBOX = 'checkbox';
    const TYPE_TEXT = 'text';
    const TYPE_EMAIL = 'email';
    const TYPE_TEXTAREA = 'textarea';
    const TYPE_PASSWORD = 'password';
    const TYPE_NUMBER = 'number';
    const TYPE_FLOAT = 'float';
    const TYPE_CURRENCY = 'currency';
    const TYPE_DATE = 'date';
    const TYPE_DATETIME = 'datetime';
    const TYPE_DROPDOWN = 'dropdown';
    const TYPE_OPTION = 'option';
    const TYPE_LIST = 'list';
    const TYPE_FILE = 'file';
    //
    
    private $_endpoint;
    /**
     * @var array
     */
    private $_content = array(
        //define model data
    );
    /**
     * @param array $data
     */
    protected function __construct( $endpoint , array $data = array( ) ) {
        
        $this->_endpoint = explode('.', $endpoint);
        
        if( count( $data ) ){
            $this->import($data);
        }
    }
    /**
     * @return string
     */
    public function __toString() {
        return $this->__class();
    }
    /**
     * Model Class Name
     * @return string
     */
    protected final function __class(){
        
        $ns = explode('\\', get_class($this));
        
        return $ns[ count($ns) - 1 ];
    }
    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        switch( TRUE ){
            case preg_match(  '/^is_/' , $name ):
                //RETURN BOOLEAN
                $is = preg_replace('/_/', '', $name);
                return method_exists($this, $is) ? $this->$is( ) : FALSE;
            case preg_match(  '/^value_/' , $name ):
                //RETURN VALUE
                $element = substr($name, strlen('value_'));
                return $this->value($element);
            case preg_match(  '/^type_/' , $name ):
                //RETURN LIST
                $type = substr($name, strlen('type_'));
                return $this->get($type, 'type', self::TYPE_TEXT);
            case preg_match(  '/^label_/' , $name ):
                //RETURN LIST
                $label = substr($name, strlen('label_'));
                return $this->get($label, 'label', $label );
            case preg_match(  '/^list_/' , $name ):
                //RETURN LIST
                $list = preg_replace('/_/', '', $name);
                return method_exists($this, $list) ? $this->$list() : array();
            case preg_match(  '/^error_/' , $name ):
                //RETURN LIST
                $element = substr($name, strlen('error_'));
                return $this->get($element, 'error', '');
            default:
                //RETURN CUSTOMIZER GETTER
                $get = sprintf('get%s',preg_replace('/_/', '', $name));
                //OR DEFAULT FALLBACK IF DEFINED IN THE DICTIONARY
                return method_exists($this, $get) ? $this->$get() : $this->value($name);
        }
    }
    /**
     * @param string $name
     * @param mixed $arguments
     * @return mixed
     */
    public function __call($name, $arguments) {
        switch( TRUE ){
            case preg_match(  '/^label_/' , $name ):
                //RETURN LIST
                $input = substr($name, strlen('label_'));
                return $this->get($input, 'label', $input);
            case preg_match(  '/^is_/' , $name ):
                //RETURN BOOLEAN
                $is = preg_replace('/_/', '', $name);
                return method_exists($this, $is) ? $this->$is( $arguments ) : FALSE;
            case preg_match(  '/^list_/' , $name ):
                //RETURN LIST
                return $this->list($name);
            case preg_match(  '/^error_/' , $name ):
                if( count( $arguments )){
                    $element = substr($name, strlen('error_'));
                    $this->set($element, 'error', $arguments[0] );
                    return TRUE;
                }
                return FALSE;
            default:
                //RETURN STRING
                $get = sprintf('get%s',preg_replace('/_/', '', $name));
                return method_exists($this, $get) ? $this->$get( $arguments ) : $this->value($name);
        }
    }
    /**
     * @return string
     */
    protected static function __ts(){
        return date('Y-m-d H:i:s');
    }
    /**
     * @return string|PATH
     */
    protected function __path(){
        // within either sub or parent class in a static method
        $ref = new \ReflectionClass(get_called_class());
        return preg_replace('/\\\\/', '/',  dirname( $ref->getFileName() ) );
        //return preg_replace('/\\\\/', '/', plugin_dir_path(__FILE__ ) );
    }
    /**
     * @param string $email
     * @return boolean
     */
    protected function __matchEmail( $email ){
         return !preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $email) ? FALSE : TRUE;
    }
    
    public final function endpoint(){

    }
    /**
     * @return string
     */
    public final function module(){
        $class = explode('\\', get_class($this));
        return count($class) > 1 ?
            $class[count($class) - 2 ] :
            $class[count($class) - 1 ]  ;
    }
    /**
     * @param string $element
     * @param string $type
     * @param array $attributes
     * @return \CODERS\Framework\Model
     */
    protected final function define( $element , $type = self::TYPE_TEXT , array $attributes = array( ) ){
        
        if( !array_key_exists($element, $this->_content)){
            $this->_content[$element] = array(
                'type' => $type,
            );
            switch($type){
                case self::TYPE_CHECKBOX:
                    $this->_content[$element]['value'] = FALSE;
                case self::TYPE_NUMBER:
                case self::TYPE_CURRENCY:
                case self::TYPE_FLOAT:
                    $this->_content[$element]['value'] = 0;
                    break;
                case self::TYPE_TEXT:
                case self::TYPE_TEXTAREA:
                    //others
                default;
                    $this->_content[$element]['value'] = '';
                    break;
            }
            foreach( $attributes as $att => $val ){
                switch( $att ){
                    case 'value':
                        //force value parsing
                        $this->change($element,$val);
                        break;
                    default:
                        $this->set($element, $att, $val);
                        break;
                }
            }
        }

        return $this;
    }
    /**
     * @param \CODERS\Framework\Model $source
     * @return \CODERS\Framework\Model
     */
    protected final function __copy(Model $source ){
        if( count( $this->_content) === 0 ){
            foreach( $source->_content as $element => $meta ){
                $this->_content[ $element ] = $meta;
            }
        }
        return $this;
    }
    /** 
     * @param string $element
     * @return boolean
     */
    public function required( $element ){
        return $this->get($element,'required',FALSE);
    }
    /**
     * @return boolean
     */
    public function validateAll(){
        foreach( $this->elements() as $element ){
            if( !$this->validate($element)){
                return FALSE;
            }
        }
        return TRUE;
    }
    /**
     * @return boolean
     */
    protected function validate( $element ){
        
        if( $this->required($element) ){
            $value = $this->value($element);
            switch( $this->type($element)){
                case self::TYPE_CHECKBOX:
                    return TRUE; //always true, as it holds FALSE vaule by default
                case self::TYPE_NUMBER:
                case self::TYPE_CURRENCY:
                case self::TYPE_FLOAT:
                    return FALSE !== $value; //check it's a number
                case self::TYPE_EMAIL:
                    //validate email
                    return preg_match( self::EmailMatch() , $value) > 0;
                //case self::TYPE_TEXT:
                default:
                    $size = $this->get($element, 'size' , 1 );
                    if( FALSE !== $value && strlen($value) <= $size ){
                        return TRUE;
                    }
                    break;
            }
            return FALSE;
        }
        return TRUE;
    }
    /**
     * Combine elements from
     * @param Array $data
     * @return \CODERS\Framework\Model
     */
    public function import( array $data ){
        
        foreach( $data as $element => $value ){
            $this->setValue($element,$value );
        }
        
        return $this;
    }
    /**
     * @param string $element
     * @return boolean
     */
    public function exists( $element ){
        //return array_key_exists($element, $this->_dictionary);
        return $this->has($element);
    }
    /**
     * @param string $element
     * @param string $attribute
     * @return boolean
     */
    public final function has( $element , $attribute = '' ){
        
        if( array_key_exists($element, $this->_content) ){
            if(strlen($attribute) ){
                return array_key_exists( $attribute, $this->_content[$element]);
            }
            return TRUE;
        }
        
        return FALSE;
    }
    /**
     * @param string $element
     * @param string $attribute
     * @param mixed $default
     * @return mixed
     */
    protected function get( $element , $attribute , $default = FALSE ){
        if( $this->has($element, $attribute)){
            return $this->_content[$element][$attribute];
        }
        return $default;
    }
    /**
     * @param string $element
     * @return mixed
     */
    public function value( $element ){
        switch( $this->type($element)){
            case self::TYPE_CHECKBOX:
                return $this->get($element, 'value' );
            case self::TYPE_CURRENCY:
            case self::TYPE_FLOAT:
            case self::TYPE_NUMBER:
                return $this->get($element, 'value' , 0 );
            default:
                return $this->get($element, 'value', '');
        }
        //return $this->get($element, 'value', $default );
    }
    /**
     * @param string $name
     * @return array
     */
    public function list( $name ){
        $list = 'list'. preg_replace('/_/', '', ucwords($name) );
        return method_exists($this, $list) ? $this->$list( ) : array();
    }
    /**
     * @param string $element
     * @return array
     */
    public function options( $element ){
        $list = $this->meta($element, 'options');
        return strlen($list) ? $this->list($list) : array();
    }

    /**
     * @param string $element
     * @param string $meta
     * @param mixed $default
     * @return mixed
     */
    public function meta( $element , $meta , $default = '' ){
        return $this->has($element) ? $this->get($element,$meta) : $default;
    }

    /**
     * @param string $element
     * @param string $attribute
     * @param mixed $value
     * @return \CODERS\Framework\Model
     */
    protected final function set( $element , $attribute , $value ){
        if(array_key_exists($element, $this->_content)){
            $this->_content[$element][ $attribute ] = $value;
        }
        return $this;
    }
    /**
     * @param string $element
     * @param mixed $value
     * @param boolean $update
     * @return \CODERS\Framework\Model
     */
    protected function change( $element , $value = FALSE , $update = FALSE ){
        $customSetter = sprintf('set%sValue',$element);
        if(method_exists($this, $customSetter)){
            //define a custom setter for a more extended behavior
            $this->$customSetter( $value );
            //$this->set($element, 'updated', true);
        }
        elseif( $this->exists($element)){
            switch( $this->type($element)){
                case self::TYPE_CHECKBOX:
                    return $this->set($element,
                            'value',
                            is_bool($value) ? $value : FALSE )
                            ->set($element, 'updated', $update);
                case self::TYPE_CURRENCY:
                case self::TYPE_FLOAT:
                    return $this->set($element,'value',floatval($value))
                        ->set($element, 'updated', $update);
                case self::TYPE_NUMBER:
                    return $this->set($element,'value',intval($value))
                        ->set($element, 'updated', $update);
                default:
                    return $this->set($element,'value',strval($value))
                        ->set($element, 'updated', $update);
            }
        }
        return $this;
    }
    /**
     * @param string $element
     * @return string|boolean
     */
    public final function type( $element ){
        return $this->has($element) ? $this->_content[$element]['type'] : FALSE;
    }
    /**
     * @return array
     */
    protected final function dictionary(){ return $this->_content; }
    /**
     * @return array
     */
    public final function elements(){ return array_keys($this->_content); }
    /**
     * @return array
     */
    public final function values(){
        $output = array();
        foreach( $this->elements() as $element ){
            $output[$element] = $this->value( $element );
        }
        return $output;
    }
    /**
     * @return \CODERS\Framework\Query
     */
    protected static final function newQuery(){
        return new Query($this->endpoint());
    }
    /**
     * @param string $request
     * @return array
     */
    private static final function package( $request ){
        $package = explode('.', $request);
        if(count($package) > 2 ){
            /**
             * Import plugin MVC local model
             */
            return array(
                'path' => sprintf('%s/modules/%s/models/%s.php',
                    strtolower($package[0]), //plugin
                    strtolower($package[1]), //module
                    strtolower($package[2])),//model
                'class' => sprintf('\CODERS\%s\%s\%sModel',
                    $package[0],
                    $package[1],
                    $package[2]),
            );
        }
        elseif( count( $package) > 1 ){
            /**
             * Import plugin logics model from components
             */
            return array(
                'path' => sprintf('%s/components/models/%s.php',
                    strtolower($package[0]), //plugin
                    strtolower($package[1])), //model
                'class' => sprintf('\CODERS\%s\%sModel',
                    $package[0],
                    $package[1]),
            );
        }
        else{
            /**
             * Import root component model
             */
            return array(
                'path' => sprintf('%s/components/models/%s.php',
                    CODERS_FRAMEWORK_BASE,strtolower( $package[0] ) ),
                'class' => sprintf('\CODERS\Framework\Models\%sModel',$package[0])
            );
        }
    }
    /**
     * @param array $route
     * @return string
     */
    private static final function __importClass( array $route ){
        //$route = explode('.', $route);
        return count($route) > 1 ?
                    sprintf('\CODERS\%s\%sModel',
                            \CodersApp::Class($route[0]),
                            \CodersApp::Class($route[1])) :
                    sprintf('\CODERS\Framework\Models\%s',
                            \CodersApp::Class($route[0]));
    }
    /**
     * @param array $route
     * @return String|PAth
     */
    private static final function __importPath( array $route ){
        return count($route) > 1 ?
                    sprintf('%s/components/models/%s.php',
                            \CodersApp::path($route[0]),
                            $route[1]) :
                    sprintf('%s/components/models/%s.php',
                            \CodersApp::path(),
                            $route[0]);
    }
    /**
     * @param string $model
     * @param array $data
     * @return \CODERS\Framework\Model | boolean
     * @throws \Exception
     */
    public static final function create( $model , $data = array() ){
        try{
            //$package = self::package($model);
            $route = explode('.', $model);
            $path = self::__importPath($route);
            $class = self::__importClass($route);

            if(file_exists($path)){
                require_once $path;
                if(class_exists($class) && is_subclass_of($class, self::class)){
                    return new $class( $route,$data );
                }
                else{
                    throw new \Exception(sprintf('Invalid Model %s',$class) );
                }
            }
            else{
                throw new \Exception(sprintf('Invalid path %s',$path) );
            }
            return FALSE;
        }
        catch (Exception $ex) {
            die( $ex->getMessage());
        }
        
        return FALSE;
    }
    /**
     * @return string
     */
    protected static final function EmailMatch(){
    
        return "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i";
    }
}
/**
 * WPDB Query Handler
 */
final class Query {
    
    private $_endpoint;
    
    /**
     * 
     * @param type $endpoint
     */
    public final function __construct( $endpoint ) {
        $this->_endpoint = $endpoint;
    }
    /**
     * @global \wpdb $wpdb
     * @return \wpdb
     */
    private static final function db(){
        global $wpdb;
        return $wpdb;
    }
    /**
     * @global string $table_prefix
     * @return string
     */
    public final function prefix(){
        global $table_prefix;
        return $table_prefix . $this->_endpoint;
    }
    /**
     * @param string $table
     * @return string
     */
    public final function table( $table ){
        return sprintf('%s_%s',$this->prefix(),$table);
    }
    
    /**
     * @param array $filters
     * @return array
     */
    private final function where(array $filters) {
        
        $where = array();

        foreach ($filters as $var => $val) {
            switch (TRUE) {
                case is_string($val):
                    $where[] = sprintf("`%s`='%s'", $var, $val);
                    break;
                case is_object($val):
                    $where[] = sprintf("`%s`='%s'", $var, $val->toString());
                    break;
                case is_array($val):
                    $where[] = sprintf("`%s` IN ('%s')", $var, implode("','", $val));
                    break;
                default:
                    $where[] = sprintf('`%s`=%s', $var, $val);
                    break;
            }
        }

        return $where;
    }

    /**
     * @param string $table
     * @param array $columns
     * @param array $filters
     * @param string $index
     * @return array
     */
    public final function select( $table, $columns = '*', array $filters = array() , $index = '' ) {
        
        $select = array();
        
        switch( TRUE ){
            case is_array($columns):
                $select[] = count($columns) ?
                    sprintf("SELECT %s"  , implode(',', $columns) ) :
                    "SELECT *";
                break;
            case is_string($columns) && strlen($columns):
                $select[] = sprintf("SELECT %s"  , $columns );
                break;
            default:
                $select[] = "SELECT *";
                break;
        }
        
        $select[] = sprintf("FROM `%s`", $this->table($table) );
        
        if (count($filters)) {
            $select[] = " WHERE " . implode(' AND ', $this->where($filters ) );
        }
        
        return $this->query( implode(' ', $select) , $index );
    }
    /**
     * @param string $table
     * @param array $data
     * @return int
     */
    public final function insert( $table , array $data ){
        
        $db = self::db();
        
        $columns = array_keys($data);

        $values = array();
        
        foreach( $data as $val ){
            if(is_array($val)){
                //listas
                $values[] = sprintf("'%s'",  implode(',', $val));
            }
            elseif(is_numeric($val)){
                //numerico
                $values[] = $val;
            }
            else{
                //texto
                $values[] = sprintf("'%s'",$val);
            }
        }
        
        $sql_insert = sprintf('INSERT INTO `%s` (%s) VALUES (%s)',
                $this->table($table),
                implode(',', $columns),
                implode(',', $values));
        
        $result = $db->query($sql_insert);
        
        //var_dump($db->last_error);
        
        return FALSE !== $result ? $result : 0;
    }
    /**
     * @param string $table
     * @param array $data
     * @param array $filters
     * @return int
     */
    public final function update( $table , array $data , array $filters ){

        $db = self::db();

        $values = array();
        
        foreach( $data as $field => $content ){
            
            if(is_numeric( $content)){
                $value = $content;
            }
            elseif(is_array($content)){
                $value = implode(',',$content);
            }
            else{
                $value = sprintf("'%s'",$content);
            }
            
            $values[] .= sprintf("`%s`=%s",$field,$value);
        }
        
        $sql_update = sprintf( "UPDATE %s SET %s WHERE %s",
                $this->table($table),
                implode(',', $values),
                $this->set_filters($filters));
        
        $result = $db->query($sql_update);
        
        return FALSE !== $result ? $result : 0;
    }
    /**
     * @param string $table
     * @param array $filters
     * @return int
     */
    public final function delete( $table, array $filters ){
               
        $db = self::db();

        $result = $db->delete($this->table($table), $filters);

        return FALSE !== $result ? $result : 0;
    }
    /**
     * @param string $SQL_QUERY
     * @param string $index
     * @return array
     */
    public final function query( $SQL_QUERY , $index = '' ){
        
        $db = self::db();

        $result = $db->get_results($SQL_QUERY, ARRAY_A );
        //var_dump($SQL_QUERY);
        
        if( strlen($index) ){
            $output = array();
            foreach( $result as $row ){
                if( isset( $row[$index])){
                    $output[ $row[ $index ] ] = $row;
                }
            }
            return $output;
        }

        return ( count($result)) ? $result : array();
    }
}


