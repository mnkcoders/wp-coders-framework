<?php namespace CODERS\Framework\Models;

defined('ABSPATH') or die;

/**
 * Modelo de formulario para procesar registros de entrada de datos
 * 
 * Incluye funciones de validación de datos del form e importación directa desde los eventos generados
 * por la entrada de inputs GET y POST
 */
abstract class FormModel extends \CODERS\Framework\Dictionary implements \CODERS\Framework\IModel{
    const FIELD_TYPE_ANTISPAM = 'antispam';
    const FIELD_TYPE_PRICE_TOTAL = 'price_total';
    const FORM_FIELD_ANTISPAM = 'secret_key';
    /**
     * @param array | null $dataSet Set de datos a importar
     */
    public function __construct( array $data = array( ) ) {

        //agregar siempre antispam por defecto
        $this->addField(self::FORM_FIELD_ANTISPAM,self::FIELD_TYPE_ANTISPAM);
        
        if( !is_null($data)){

            $this->importData($data);
        }
    }
    /**
     * Define un nuevo tipo de dato
     * @param string $name
     * @param string $type
     * @param array $properties
     * @return \CODERS\Framework\Models\FormModel Instancia para chaining
     */
    protected function addField($name, $type = self::FIELD_TYPE_TEXT, array $properties = null) {
        
        switch( $type ){
            case self::FIELD_TYPE_PRICE_TOTAL:
                if( !is_null($properties)){
                    if( !isset( $properties['value']) ){
                        $properties['value'] = 0;
                    }
                }
                else{
                    $properties['value'] = 0;
                }
                $properties['local'] = true;
                break;
            case self::FIELD_TYPE_ANTISPAM:
                if( !is_null($properties)){
                    $properties['class'] = 'hidden';
                }
                else{
                    $properties = array('class'=>'hidden');
                }
                $properties['local'] = true;
                break;
        }
        
        return parent::addField($name, $type, $properties);
    }
    /**
     * Obtiene un resultado del Modelo
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function get( $name, $default = null ){

        if($this->hasField($name)){

            return $this->getValue($name,$default);
        }

        $callback = sprintf('get%s', preg_replace('/_/','',$name));
        
        return (method_exists($this, $callback)) ? $this->$callback( ) : $default;
    }
    /**
     * @param string $name
     * @return boolean
     */
    public function has($name) {
        return $this->hasField($name) || 
                method_exists($this, sprintf('get%s', preg_replace('/_/', '', $name)));
    }
    /**
     * Publica el diccionario de datos del formulario
     * @return array
     */
    public function getFields(){
        return parent::listFields();
    }
    /**
     * Devuelve el nombre o etiqueta del campo solicitado
     * @param string $field
     * @return string
     */
    public final function getLabel($field){
        return $this->getMeta($field, 'label' ,'');
    }
    /**
     * Devuelve un texto de detalle o consejo para mostrar en el formulario
     * @param string $field
     * @return string
     */
    public final function getAdvice( $field ){
        return $this->getMeta($field, 'advice', '' );
    }
    /**
     * Retorna el error de validación a mostrar en el formulario
     * @param string $field
     * @return string | ERROR
     */
    public final function getErrors( ){
        $errors = array();
        foreach ( $this->listFields() as $field ){
            if( $this->hasMeta($field, 'error')){
                $errors[$field] = $this->getMeta($field, 'error');
            }
        }
        return $errors;
    }
    /**
     * Obtiene el marcador del campo indicado
     * @param string $field
     * @return string
     */
    public final function getPlaceholder( $field ){
        return $this->getMeta($field, 'placeholder', $this->getLabel($field));
    }
    /**
     * Retorna la clase del campo solicitado, vacía si no se ha definido
     * @param string $field
     * @return string
     */
    public final function getClass( $field ){
        return $this->getMeta($field, 'class','');
    }
    /**
     * Indica si un campo del formulario es requerido a rellenar
     * @param string $field
     * @return boolean
     */
    public final function isRequired($field){
        return $this->getMeta($field, 'required',false);
    }
    /**
     * Determina si el valod de un campo ha sido actualizado
     * @param string $field
     * @return bool
     */
    public final function isUpdated( $field ){
        return $this->getMeta($field, 'updated', false);
    }
    /**
     * Valor del campo de form seleccionado
     * @param string $field
     * @param mixed $default
     * @return mixed
     */
    public function getValue( $field, $default = null ){
        return $this->getMeta($field, 'value', $default );
    }
    /**
     * Retorna una lista de opciones
     * @param string $field
     * @return array
     */
    public function getSource( $field ){
        return $this->getMeta($field, 'source', array() );
    }
    /**
     * Indica si existe un origen de datos definido para el campo
     * @param string $field
     * @return bool
     */
    public final function hasSource( $field ){
        return $this->hasMeta($field, 'source');
    }
    /**
     * Gestiona la comprobación de un campo para verificar si es isExportable
     * fuera del formulario.
     * 
     * @param type $field
     * @return boolean
     */
    protected function isExportable( $field ){
        switch( TRUE ){
            case $this->getFieldType($field) === self::FIELD_TYPE_ANTISPAM:
            case $this->getFieldType($field) === self::FIELD_TYPE_PRICE_TOTAL:
            case $this->getMeta($field, 'readonly',FALSE):
            case $this->getMeta($field, 'local',FALSE):
                return false;
        }
        
        return true;
    }
    /**
     * Define un error en el formulario
     * @param string $field
     * @param string $message
     * @param mixed $args Parámetro o parámetros a intercalar en el texto (array o cadena)
     * @return \CODERS\Framework\Models\FormModel
     */
    public function setError( $field, $message ){
        $this->setMeta( $field, 'error', $message );
        return $this;
    }
    /**
     * Importa los valores existentes de un diccionario de datos externo que coincidan con la definición local
     * @param TripManDictionary $source
     * @return \CODERS\Framework\Models\FormModel
     */
    protected final function merge(\CODERS\Framework\Dictionary $source, $updated = true ){

        return $this->importData( $source->listValues(), $updated);
    }
    /**
     * Importa directamente los datos sobre el modelo del formulario
     * @param array $data
     * @param boolean $update Marca los datos importados como valores actualizados
     * @return \CODERS\Framework\Models\FormModel
     */
    public function import( array $data, $update = false ){
        foreach( $this->listFields() as $field ){
            if( isset($data[$field]) ){
                $this->setValue($field, $data[$field]);
                $this->setMeta($field, 'updated', $update );
            }
        }
        return $this;
    }
    /**
     * @return array
     */
    public function export(){
        $output = array();
        foreach ($this->listFields() as $field ){
            if($this->isExportable($field)){
                $output[$field] = $this->getValue($field);
            }
        }
        return $output;
    }
    /**
     * @return boolean
     */
    public function validate( ){
        
        $success = true;
        
        foreach( $this->listFields() as $field ){
            switch( $this->getFieldType($field)){
                case self::FIELD_TYPE_ANTISPAM:
                    $value = $this->getValue($field,'');
                    if( strlen($value) > 0 ){
                        //si hay contenido en este campo, no admitir el registro
                        $success = false;
                    }
                    break;
                case self::FIELD_TYPE_NUMBER:
                case self::FIELD_TYPE_FLOAT:
                case self::FIELD_TYPE_PRICE:
                    $value = $this->getValue($field,0);
                    $minimum = $this->getMeta($field, 'minimum',false );
                    $maximum = $this->getMeta($field, 'maximum', false );
                    if( $minimum !== false && $value < $minimum ){
                        $this->setError($field, 'INVALID VALUE');
                        $success = false;
                    }
                    if( $maximum !== false && $value > $maximum ){
                        $this->setError($field, 'INVALID VALUE');
                        $success = false;
                    }
                    break;
                case self::FIELD_TYPE_DROPDOWN:
                    $value = $this->getValue($field,'');
                    if( $this->isRequired($field) && strlen($value) === 0 ) {
                        $this->setError($field, 'EMPTY_VALUE' );
                        $success = false;
                    }
                    break;
                case self::FIELD_TYPE_EMAIL:
                    $value = $this->getValue($field,'');
                    if( strlen($value)){
                        //validar email
                        $at = strrpos($value, '@');
                        $dot = strrpos($value, '.');
                        //hay una @ en alguna posición superior a 0 y luego hay un punto para definir el dominio
                        if( $at < 1 || $at > $dot ){
                            $this->setError($field, 'INVALID_VALUE' );
                            $success = false;
                        }
                    }
                    elseif($this->isRequired($field)){
                        $this->setError($field, 'EMPTY_VALUE' );
                        $success = false;
                    }
                    break;
                default:
                    $value = $this->getValue($field,'');
                    if( $this->isRequired($field) && strlen($value) === 0 ){
                        $this->setError($field, 'EMPTY_VALUE' );
                        $success = false;
                    }
                    break;
            }
        }
        
        return $success;
    }
    /**
     * Array asociativo de todos los valores del formulario.
     * Si se provee la lista de campos a mostrar, se exportan los que coincidan
     * con la definición del filtro.
     * @param array $filter Filtros
     * @return array
     */
    public function getValues(){
        return parent::listValues();
    }
    /**
     * @return string Nombre del Formulario
     */
    public function getHeader(){
        $class = get_class($this);
        $prefix_length = strlen(TripManager::PLUGIN_NAME);
        $suffix_length = strlen($class) - strrpos($class, 'FormModel');
        //TripMan[NOMBRE]FormModel
        return substr( strtolower( $class ), $prefix_length, $suffix_length );
    }
}

