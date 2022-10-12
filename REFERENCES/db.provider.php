<?php namespace \MNK\TripMan\Providers;

defined('ABSPATH') or die;

/**
 * Gestor de conexión a la base de datos WP
 * 
 * v1.1.02 - 2017-05-26
 * -> Agregado el método insertOrUpdate para facilitar acutalizaciones de datos cuando la clave primaria ya existe
 */
class DB{
    
    const PLUGIN_DB_NAMESPACE = 'tripman';
    
    const TABLE_ATTACHMENTS = 'attachments';
    const TABLE_TRIPS = 'trips';
    const TABLE_TRIPGROUP = 'tripgroup';
    const TABLE_BOOKING = 'booking';
    const TABLE_BOOKING_HISTORY = 'booking_stages';
    const TABLE_AGENTS = 'agents';
    const TABLE_WEEKLY_QUOTAS = 'quotas_weekly';
    const TABLE_DAILY_QUOTAS = 'quotas_daily';
    const TABLE_LOGS = 'logs';
    const TABLE_PARAMETERS = 'parameters';
    const TABLE_PAYMENTS = 'payments';
    const TABLE_PRICING = 'pricing';
    const TABLE_TOKENS = 'tokens';
    
    private static $_errors = array();
    
    /**
     * @var wpdb Gestor de la bd WordPress
     */
    private $_wpdb;
    
    private $_hasError = false;
    
    //private $_builder = array();
    
    public function __construct() {
        
        global $wpdb;
        
        $this->_wpdb = $wpdb;
    }
    /**
     * @return string
     */
    public final function __toString() {
        return get_class($this);
    }
    /**
     * Comprueba si ha habido errores que anotar
     * @return boolean
     */
    private final function checkErrors(){
        
        if( strlen( $this->_wpdb->last_error ) ){
            
            $this->_hasError = true;

            $hash = md5( $this->_wpdb->last_error);
            
            if( !isset( self::$_errors[ $hash ] ) ){
                self::$_errors[ $hash ] = array(
                    'error' => $this->_wpdb->last_error,
                    'query' => $this->_wpdb->last_query,
                );

                //esto para debugear
                //var_dump(self::$_errors[$hash]);
                
                return true;
            }
        }
        return false;
    }
    /**
     * Registra los errores transcurridos durante la request
     */
    public static final function logErrors(){

        foreach( self::$_errors as $error ){

            TripManLogProvider::error(
                    sprintf('%s<br/><small>%s</small>',$error['error'],$error['query']),
                    'TripManDBProvider');
        }
    }
    /**
     * Retorna la instancia del objeto WPDB global
     * @global wpdb $wpdb
     * @return wpdb
     */
    public final function wpdb(){

        return $this->_wpdb;
    }
    /**
     * Indica si ha habido un error en la última query
     * @return bool
     */
    public final function hasError(){
        return $this->_hasError;
    }
    /**
     * Prefijo para la tabla de la bd actual WP (solo para tablas externas al plugin Trip Manager)
     * @global string $table_prefix
     * @param string $table
     * @return string
     */
    public static final function prefixTable( $table ){
        
        global $table_prefix;
        
        return $table_prefix.$table;
    }
    /**
     * Retorna el nombre de la tabla del contexto de la aplicación
     * @global string $table_prefix
     * @param string $table
     * @param boolean $quote
     * @return string
     */
    public static final function getTable( $table, $quote = FALSE ){
        
        global $table_prefix;
        
        $prefixed_table = sprintf('%stripman_%s',$table_prefix, strtolower( $table ) );
        
        return $quote ? sprintf('`%s`',$prefixed_table) : $prefixed_table;
    }
    /**
     * Vacía una tabla (ojo con esto!!)
     * @param string $table
     * @return int
     */
    public final function cleanup( $table ){
        
        $sql_truncate = 'TRUNCATE ' . $this->getTable($table);
        
        $result = $this->_wpdb->query($sql_truncate);

        return $result !== false ? $this->_wpdb->rows_affected : 0;
    }
    /**
     * Por que no me fio de Truñopress
     * @param string $table
     * @param array $filters
     * @return int
     */
    public final function delete_query( $table, array $filters ){

        $where = array();
        
        foreach( $filters as $var => $val ){
            if(is_array($val)){
                $where[] = sprintf("`%s` IN ('%s')",$var,implode("','", $val));
            }
            elseif(is_numeric($val)){
                $where[] = sprintf("`%s`=%s",$var,$val);
            }
            else{
                $where[] = sprintf("`%s`='%s'",$var,$val);
            }
        }
        
        $sql_delete = sprintf('DELETE FROM %s WHERE %s',
                self::getTable($table),implode(' AND ',$where));
        
        $result = $this->_wpdb->query($sql_delete);
        
        if( $result !== false ){

            return $result;
        }
        
        $this->checkErrors();
        
        return 0;
    }
    /**
     * Borra registros según las especificaciones del filtro
     * @param string $table
     * @param array $filters
     * @return int Num de registros afectados
     */
    public final function delete( $table, array $filters ){
        
        $result = $this->_wpdb->delete($this->getTable($table), $filters);
        
        if( $result !== false ){

            return $result;
        }
        
        $this->checkErrors();
        
        return 0;
    }
    /**
     * Mucho ojo, este método utiliza insert de wpdb, es decir, puede no hacer nada
     * en absoluto y no retornar ningún error. PAra temas CRITICOS, utilizar insert_query
     * @param string $table
     * @param mixed $columns
     * @param mixed $filters
     * @return int Número de registros asignados
     */
    public final function insert( $table, array $columns ){
        
        $result = $this->_wpdb->insert($this->getTable($table), $columns);

        if( $result === false ){
            $this->checkErrors();
            return 0;
        }

        //return $this->_wpdb->insert_id;
        return $result;
    }
    /**
     * Inserta valores en una tabla pasandose por el forro el método insert de wordpress
     * @param string $table Tabla de destino
     * @param array $data Valores a insertar 
     * @return int Número de registros insertados
     */
    public final function insert_query( $table, array $data ){
        
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
                $this->getTable($table),
                implode(',', $columns),
                implode(',', $values));
        
        $result = $this->_wpdb->query($sql_insert);
        
        if( $result === false ){

            $this->checkErrors();
            
            return 0;
        }
        
        return $result;
    }
    /**
     * 
     * @param string $table
     * @param array $data
     * @return int Número de registros insertados
     */
    public final function insertIgnore( $table, array $data ){
        
        $columns = array_keys($data);
        
        $values = array();
        
        foreach( $data as $val ){
            if(is_string($val)){
                $values[] = "'{$val}'";
            }
            elseif(is_array($val)){
                $values[] = sprintf("'%s'",implode(',',$val));
            }
            else{
                $values[] = $val;
            }
        }
        
        $sql_ignore = sprintf('INSERT IGNORE INTO %s (%s) VALUES (%s)',
                self::getTable($table),implode(',',$columns),implode(',', $values));
        
        $result = $this->_wpdb->query($sql_ignore);
        
        if( $result === false ){
            $this->checkErrors();
            return 0;
        }
        
        return $result;
        //return $this->_wpdb->insert_id;
    }
    /**
     * Inserta los valores proveidos a la tabla, si ya existe el índice lo intenta actualizar
     * 
     * @param type $table
     * @param array $insert
     * @param array $update
     * @return int Resultado, valores creados/actualizados
     */
    public final function insertOrUpdate( $table, array $insert, array $update ){
        
        /**
         * INSERT INTO table (id, name, age) VALUES(1, "A", 19) ON DUPLICATE KEY UPDATE    
         * name="A", age=19
         */
        $columns = array();
        $values = array();
        foreach( $insert as $var => $val ){
            $columns[] = sprintf('`%s`',$var);
            
            if(is_array($val)){
                $values[] = sprintf("'%s'",implode(",", $val));
            }
            elseif(is_numeric($val)){
                $values[] = $val;
            }
            else{
                $values[] = "'{$val}'";
            }
        }

        $data = array();
        
        foreach( $update as $var => $val ){
            if(is_array($val)){
                $data[] = sprintf("`%s`='%s'",$var, implode(",", $val));
            }
            elseif(is_numeric($val)){
                $data[] = sprintf("`%s`=%s",$var, $val );
            }
            else{
                $data[] = sprintf("`%s`='%s'",$var, $val );
            }
        }
        
        $sql_insert_update = sprintf( 'INSERT INTO `%s` (%s) VALUES (%s)',
              $this->getTable($table),implode(',',$columns),  implode(',', $values))
            . sprintf(' ON DUPLICATE KEY UPDATE %s', implode(',', $data));
        
        //var_dump($sql_insert_update);
        
        $result = $this->_wpdb->query($sql_insert_update);
        
        if( $result === false ){
            
            $this->checkErrors();
            
            //var_dump($this->_wpdb->last_error);
            
            return 0;
        }
        
        return $result;
    }
    /**
     * 
     * @param string $table
     * @param mixed $id
     * @param string $index
     * @return array
     */
    public final function getRecord( $table, $id, $index = 'id' ){
        
        $output = $this->select( $table, null, array($index=>$id), 0 , 1 );

        return !is_null($output) && count($output) ? $output[0] : array();
    }
    /**
     * Retorna el último identificador numérico insertado (marcado como AutoIncrement en la bd)
     * @return int
     */
    public final function getId(){
        return $this->_wpdb->insert_id;
    }
    /**
     * Extrae un valor de la bd directamente
     * @param string $table Tabla
     * @param string $column Columna o campo a extraer
     * @param $filters Filtros en formato array asociativo o cadena SQL para la cláusula WHERE
     * @param mixed $default Valor p275or omisión si no hay un resultado
     * @return mixed
     */
    public final function get( $table, $column, $filters, $default = null ){
        
        $result = $this->select($table,array($column),$filters,1,1);
        
        return count($result) ? $result[0][$column] : $default;
    }
    /**
     * Método de query generales
     * @param SQL $sql_query
     * @return array
     */
    public final function getResults( $sql_query, $index = null ){
        
        $result = $this->_wpdb->get_results($sql_query,'ARRAY_A');
        
        if( !is_null($result) ){
            
            if( !is_null($index)){
                //retorna el resultado indexando por el indice (UNICO)
                $output = array();

                foreach( $result as $row ){
                    if( isset($row[$index])){
                        $output[$row[$index]] = $row;
                    }
                }
                
                return $output;
            }
            //retorna el resultado a pelo
            return $result;
        }

        $this->checkErrors();
        
        return array();
    }
    /**
     * Query Básica
     * @param string $table nombre de la tabla objetivo
     * @param array $columns array simple de columnas
     * @param mixed $filters Array asociativo de valores o cadena SQL de filtros para usar en la cláusula WHERE
     * @param array $order Ordenar por
     * @return array
     */
    public final function select( $table, array $columns = null, $filters = null, $page = 1, $limit = 0, array $order = null ){
        
        $sql_query = sprintf('SELECT %s FROM %s',
                !is_null($columns) && count($columns) ? implode(',',$columns) : '*',
                $this->getTable($table));
        
        if( !is_null($filters)){
            //si los filtros son un array asociativo, conviertelo, sino, usar a pelo
            if(is_array($filters)){
                $sql_query .= ' WHERE ' . $this->set_filters($filters);
            }
            elseif(is_string($filters)){
                $sql_query .= ' WHERE ' . $filters;
            }
        }
        
        if( !is_null($order)){
            
            $sql_query .= ' ORDER BY '. implode(',', $order);
        }
        
        if( $limit > 0 ){
            $sql_query .= sprintf(' LIMIT %s,%s',($page > 0 ? $page-1 : 0) * $limit, $limit );
        }
        
        $output = $this->_wpdb->get_results($sql_query, 'ARRAY_A');
        
        if( !$this->checkErrors() ){
            return $output;
        }
        
        return array();
    }
    /**
     * Actualiza los valores de la tabla seleccionada, pasando olimpicamente del truño de
     * método update de wordpress que falla sin avisar
     * @param type $table
     * @param array $data
     * @param array $filters
     * @return int Registros afectados
     */
    public final function update_query( $table, array $data, array $filters ){
        
        $value_list = array();
        
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
            
            $value_list[] .= sprintf("`%s`=%s",$field,$value);
        }
        
        $sql_update = sprintf( "UPDATE %s SET %s WHERE %s",
                self::getTable($table),
                implode(',', $value_list),
                $this->set_filters($filters));
        
        $result = $this->_wpdb->query($sql_update);

        if( $result !== false ){
            return $result;
        }
        else{
            $this->checkErrors();
        }
        return 0;
    }
    /**
     * Actualiza los valores de la tabla seleccionada
     * @param string $table
     * @param array $values
     * @param array $filters
     * @return int Registros actualizados
     */
    public final function update( $table, array $values , array $filters = null ){

        $result = $this->_wpdb->update( $this->getTable($table) , $values, $filters ); 
        
        if( $result !== false ){
            //return $this->_wpdb->rows_affected;
            return $result;
        }
        else{
            $this->checkErrors();
        }

        return 0;
    }
    /**
     * Actualiza un valor concreto en la tabla seleccionada
     * @param string $table
     * @param string $column
     * @param mixed $value
     * @param mixed $filters
     * @return int Registros actualizados
     */
    public final function set( $table, $column, $value, $filters ){
        
        return $this->update_query( $table, array($column=>$value ), $filters);
    }
    /**
     * @param array $filters
     * @return SQL
     */
    private final function set_filters( array $filters, $operator = 'AND' ){
        
        $filter_list = array();
        
        foreach( $filters as $col=>$value){

            /**
             * Se podría agregar un nuevo nivel de filtrado
             * AND , OR
             * 
             * 'AND' => array(campo=>valor)
             */
            
            
            if( is_array($value)){
                
                $formatted_value_list = array();
                
                foreach( $value as $data){
                    if(is_numeric($data)){
                        $formatted_value_list[] = $data;
                    }
                    else{
                        $formatted_value_list[] = sprintf("'%s'",$data);
                    }
                }
                
                $filter_list[] = sprintf("`%s` IN (%s)",$col,implode(",",$formatted_value_list));
            }
            elseif(is_string($value)){
                $filter_list[] = sprintf("`%s`='%s'",$col,$value);
            }
            else{
                $filter_list[] = sprintf("`%s`=%s",$col,$value);
            }
        }
        
        $joint = sprintf(' %s ', $operator);
        
        return implode( $joint , $filter_list );
    }
}


