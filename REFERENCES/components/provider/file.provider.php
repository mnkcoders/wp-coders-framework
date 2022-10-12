<?php namespace CODERS\Framework\Providers;

defined('ABSPATH') or die;

/**
 * Gestor de adjuntos y subida de archivos
 */
final class File extends TripManDictionary implements TripManIModel{
   
    const FILE_ID = 'id';
    const FILE_ATTACHMENT = 'attachment';
    const FILE_NAME = 'name';
    const FILE_TYPE = 'type';
    const FILE_SIZE = 'size';
    const FILE_RELATED_ID = 'related_id';
    const FILE_RELATED = 'related_to';
    const FILE_DATE_CREATED = 'date_created';
    
    const FILE_TYPE_CSV = 'csv';
    const FILE_TYPE_TXT = 'txt';

    const MAX_FILE_SIZE = 'MAX_FILE_SIZE';    
    const UPLOAD_PATH = 'uploads';
    
    private final function __construct( array $data ) {

        $this->addField('id',parent::FIELD_TYPE_ID);
        $this->addField('attachment',parent::FIELD_TYPE_TEXT,array('size'=>32));
        $this->addField('name',parent::FIELD_TYPE_TEXT,array('size'=>48));
        $this->addField('type',parent::FIELD_TYPE_TEXT,array('size'=>4));
        $this->addField('size',parent::FIELD_TYPE_NUMBER);
        $this->addField('related_to',parent::FIELD_TYPE_TEXT,array('size'=>24));
        $this->addField('relted_id',parent::FIELD_TYPE_NUMBER);
        $this->addField('date_created',parent::FIELD_TYPE_DATETIME);
        
        foreach( $data as $field => $val ){
            if( $this->hasField($field) ){
                switch($this->getFieldType($field)){
                    case parent::FIELD_TYPE_ID:
                    case parent::FIELD_TYPE_NUMBER:
                        $this->setMeta($field, 'value', intval($val));
                        break;
                    case parent::FIELD_TYPE_FLOAT:
                    case parent::FIELD_TYPE_PRICE:
                        $this->setMeta($field, 'value', floatval($val));
                        break;
                    default:
                        $this->setMeta($field, 'value', $val);
                        break;
                }
            }
        }
    }
    /**
     * Convierte el contenido a escribir en el fichero en un formato de texto.
     * 
     * Si es un array, lo separa linea a linea, si contiene mas arrays dentro del array
     * se concatena con comas.
     * 
     * Si es un objeto se obtiene el valor textual del objeto (toString)
     * 
     * @param mixed $content
     * @return string
     */
    private final function parseContentInput( $content ){
        
        if(is_object($content)){
            return strval($content);
        }
        elseif(is_array($content)){
            
            $output = '';
            
            foreach( $content as $data ){
                if(is_array($data)){
                    $output .= implode("\t", $data) . "\n";
                }
                else{
                    $output .= $data . "\n";
                }
            }
            
            return $output;
        }
        else{
            return $content;
        }
    }
    /**
     * Notifica un error de subida de fichero al diario de anotaciones
     * @param array $file_data Datos del archivo subido
     * @param boolean $required Indica si es obligatorio el adjunto. Registra un error si no se sube nada.
     * @return boolean Resultado
     */
    private static final function checkUploadErrors( array $file_data, $required = false ){
        
        $message = '';
        
        $error_code = intval($file_data['error']);
        
        switch( $error_code ){
            case UPLOAD_ERR_OK:
                return true;
            case UPLOAD_ERR_INI_SIZE:
                $message = TripManStringProvider::__(
                        'El adjunto excede el l&iacute;mite de tamaño definido en php.ini: %s bytes',
                        $file_data['size']);
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = TripManStringProvider::__(
                        'El adjunto excede el l&iacute;mite de tamaño definido en el formulario: %s bytes',
                        $file_data['size']);
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = TripManStringProvider::__(
                        'La subida del fichero se ha interrumpido: %s',
                        $file_data['name']);
                break;
            case UPLOAD_ERR_NO_FILE:
                if( !$required ){
                    //no se requiere un adjunto, por tanto no registrar ningún error
                    return false;
                }
                $message = TripManStringProvider::__(
                        'No se ha subido ning&uacute;n fichero: %s',
                        $file_data['name']);
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = TripManStringProvider::__(
                        'La carpeta temporal no es accesible: %s',
                        $file_data['tmp_name']);
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = TripManStringProvider::__(
                        'No se pudo escribir el fichero: %s',
                        $file_data['name']);
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = TripManStringProvider::__(
                        'La carga del fichero ha sido bloqueada por otro componente php: %s',
                        $file_data['name']);
                break;
            default:
                $message = TripManStringProvider::__(
                        'Error indeterminado al subir el fichero: %s',
                        $file_data['name']);
                break;
        }
        
        TripManLogProvider::warning($message,$this);
        
        return false;
    }
    /**
     * @return string
     */
    public final function __toString() {
        return get_class($this);
    }
    /**
     * Establecer relación de adjunto
     * @param string $module
     * @param int $id
     * @return \TripManFileProvider
     */
    public function relatedTo( $module, $id ){
        $this->setMeta('relateed_to', 'value', $module);
        $this->setMeta('related_id', 'value', $id);
        
        $db = new TripManDBProvider();

        $result = $db->update(TripManDBProvider::TABLE_ATTACHMENTS, array(
            'related_to' => $module,
            'related_id' => $id
        ),array('id'=>$this->getId()));
        
        return $result > 0;
    }
    /**
     * Escribe contenido en un fichero truncando todo su contenido original.
     * Si el fichero no existe, lo crea
     * 
     * @param string $input
     * @return boolean
     */
    public final function write( $input ){
        
        $content = $this->parseContentInput( $input );
        
        if(strlen($content)){

            $handle = fopen($this->getPath(),'w');

            if( $handle ){

                if( fwrite($handle, $content) == 0 ){
                    //no se pudo escribir nada?
                }

                return fclose($handle);
            }
            else{
                //no se ha podido abrir
                TripManLogProvider::error(
                    TripManStringProvider::__('No se pudo abrir el fichero %s',$this->getName()),
                        $this);
            }
        }
        else{
            //nada que escribir
            TripManLogProvider::debug(
                TripManStringProvider::__('No hay nada que escribir en %s',$this->getName()),
                    $this);
        }
        
        
        return false;
    }
    /**
     * Nombre del archivo
     * @param boolean $filename Indica si se muestra el nombre completo del archivo (archivo + extension)
     * @return string
     */
    public final function getName( $filename = FALSE ){
        
        if( $filename ){
            return sprintf('%s.%s',
                    $this->getMeta('name', 'value',''),
                    $this->getMeta('type', 'value',''));
        }
        
        return $this->getMeta('name', 'value','');
    }
    /**
     * Tamaño del archivo
     * @return string
     */
    public final function getSize(){
        return $this->getMeta('size', 'value',0);
    }
    /**
     * Id de archivo
     * @return int
     */
    public final function getId(){
        return $this->getMeta('id', 'value',0);
    }
    /**
     * HASH del adjunto
     * @return string
     */
    public final function getAttachment(){
        return $this->getMeta('attachment', 'value', '');
    }
    /**
     * Tipo de archivo
     * @return int
     */
    public final function getType(){
        return $this->getMeta('type', 'value',0);
    }
    /**
     * Tipo de contenido mime
     * @return string
     */
    public final function getMime(){
        switch( $this->getType()){
            case 'csv':
                return 'text/csv';
        }
        return $this->getType();
    }
    /**
     * Carga el contenido del archivo
     * @return string
     */
    public final function getContent(){
        
        $path = $this->getPath();
        
        return file_exists($path) ? file_get_contents($path) : '';
    }
    /**
     * URL público de acceso al archivo
     * @return URL
     */
    public final function getUrl(){
        return sprintf('%s/%s/%s/%s',
                get_site_url(),
                TripManager::PLUGIN_NAME,
                self::UPLOAD_PATH,
                $this->getAttachment());
    }
    /**
     * Ruta del adjunto en el sistema de archivos
     * @return URI
     */
    public final function getPath(){
        return self::path($this->getAttachment());
    }
    /**
     * Fecha de creación
     * @return string
     */
    public final function getDate(){
        return $this->getMeta('date_created', 'value','');
    }
    /**
     * ID del elemento relacionado
     * @return int
     */
    public final function getRelatedID(){
        return $this->getMeta(self::FILE_RELATED_ID, 0);
    }
    /**
     * Nombre del elemento relacionado
     * @return string
     */
    public final function getRelated(){
        return $this->getMeta(self::FILE_RELATED, '');
    }
    /**
     * Muestra una etiqueta del elemento relacionado para opciones de presentación
     * @return string
     */
    public final function getRelatedLabel(){
        
        return self::displayRelated($this->getRelated(), $this->getRelatedID());
    }
    /**
     * Retorna la url de acceso al recurso para la administración (SOLO ADMIN!!!)
     * @return URL
     */
    public final function getRelatedUrl(){
        
        return self::generateRelatedUrl($this->getRelated(), $this->getRelatedID());
    }
    /**
     * Propiedades del archivo
     * @param string $var
     * @param mixed $default
     * @return mixed
     */
    public function get($var, $default = null) {
        
        switch( $var ){
            case 'related_label':
                return $this->getRelatedLabel();
            case 'file_name':
                return $this->getName(TRUE);
            case 'url':
                return $this->getUrl();
            default:
                return $this->getMeta($var, 'value', $default);
        }
    }
    /**
     * URL a un recurso
     * @param string $file
     * @return URL
     */
    public static final function url( $file ){
        
        $base_path = self::path($file);
        
        if(file_exists($base_path)){
    
            
            $base_url = sprintf('%s/%s/%s',
                get_site_url(),
                TripManager::PLUGIN_NAME,
                self::UPLOAD_PATH);
        
            return sprintf('%s/%s',$base_url,$file);
        }

        return '';
    }
    /**
     * Ruta completa de destino del archivo
     * @param string $file
     * @return URI
     */
    public static final function path( $file = null ){
        
        $base_path = sprintf('%s/%s/%s',ABSPATH,  TripManager::PLUGIN_NAME, self::UPLOAD_PATH );
        
        return !is_null($file) ? sprintf('%s/%s',$base_path,$file) : $base_path;
    }
    /**
     * @return string HASH del adjunto a registrar
     */
    private static final function generateAttachmentId( $file ){

        //return md5(uniqid(date('YmdHis'),true));
        
        $ext = strrpos($file, '.') + 1;

        $timestamp = date('YmdHis');

        if( $ext > 0 && $ext < strlen($file)){

            $fileName = substr($file, 0, $ext - 1);

            $fileExt = substr($file, $ext, strlen($file) - $ext);

            return sprintf('%s_%s.%s', $fileName, $timestamp , $fileExt);
        }

        return sprintf('%s_%s',$timestamp,$file);
    }
    /**
     * Registra un adjunto en la bd
     * @param string $att_id
     * @param string $att_name
     * @param string $att_type
     * @param int $att_size
     * @return array
     */
    private static final function registerFile( $att_id, $att_name, $att_type, $att_size = 0, $related_to = NULL, $related_id = 0 ){

        $fileMeta = array(
            self::FILE_ID => 0,
            self::FILE_ATTACHMENT => $att_id,
            self::FILE_NAME => $att_name,
            self::FILE_TYPE => $att_type,
            self::FILE_SIZE => $att_size,
            self::FILE_DATE_CREATED => TripManager::getTimeStamp(),
        );
        
        if( !is_null($related_to) ){

            $fileMeta[self::FILE_RELATED] = $related_to;
            
            if( $related_id > 0 ){
                $fileMeta[self::FILE_RELATED_ID] = $related_id;
            }
        }

        $db = new TripManDBProvider();

        $result = $db->insert_query(TripManDBProvider::TABLE_ATTACHMENTS, $fileMeta);

        if( $result  > 0 ){

            $fileMeta[self::FILE_ID] = $db->getId();
        }
        else{
            TripManLogProvider::warning(
                TripManStringProvider::__('Hubo un problema al registrar el fichero %s', $att_name),
                    $this);
        }
        
        return $fileMeta;
    }
    /**
     * Importa un archivo subido, lo guarda a su ubicación de destino y lo registra en la bd
     * @param string $name
     * @param string $related_to Relacionado con que modulo
     * @param int $related_id ID del elemento relacionado
     * @return \TripManFileProvider | null
     */
    public static final function importFileUpload( $name, $related_to = NULL, $related_id = 0 ){
        
        if( isset($_FILES[$name]) ){
            
            if( !self::checkUploadErrors( $_FILES[$name] ) ){
                
                return null;
            }

            $tmp_path = $_FILES[$name]['tmp_name'];
            $file_name = $_FILES[$name]['name'];
            $file_type = $_FILES[$name]['type'];
            $file_size = $_FILES[$name]['size'];
            $attachment = self::generateAttachmentId( $file_name );
            //$error = $_FILES[$name]['error'];
            
            $target_path = self::path($attachment);
            
            if( move_uploaded_file($tmp_path, $target_path) ){
                
                $fileMeta = self::registerFile(
                        $attachment,
                        $file_name,
                        $file_type,
                        $file_size,
                        $related_to, $related_id );
                
                if( isset($fileMeta[self::FILE_ID]) && $fileMeta[self::FILE_ID] > 0 ){
                    
                    return new TripManFileProvider($fileMeta);
                }
                else{
                    TripManLogProvider::warning(
                        TripManStringProvider::__('Hubo un problema al subir el fichero %s', $file_name),
                            $this);
                }
            }
            else{
                TripManLogProvider::warning(
                    TripManStringProvider::__( 'No pudo moverse el fichero %s',$name ),
                        $this);
            }
        }
        return null;
    }
    /**
     * Crea un nuevo archivo en el repositorio
     * @param string $file_name
     * @param string $file_type
     * @param mixed $content
     * @param string $related_to
     * @param int $related_id
     * @return \TripManFileProvider
     */
    public static final function createFile( $file_name, $file_type = self::FILE_TYPE_TXT, $content = '', $related_to = NULL, $related_id = 0 ){
        
        $attachment_id = self::generateAttachmentId(strtolower($file_name) . '.' . $file_type );
        
        $meta = self::registerFile(
                $attachment_id,
                $file_name,
                $file_type,
                //cuenta todos los carácteres
                strlen(utf8_decode($content)),
                $related_to, $related_id );
        
        if( isset($meta[self::FILE_ID]) && $meta[self::FILE_ID] > 0 ){
            
            $file = new TripManFileProvider($meta);
            
            if( !is_null($file)){
                
                $file->write($content);
            }
            
            return $file;
        }
        
        return null;
    }
    /**
     * Carga un archivo
     * @param int $id
     * @return \TripManFileProvider|null
     */
    public static final function loadFile( $id ){
        
        $db = new TripManDBProvider();
        
        $attachment = $db->getRecord(TripManDBProvider::TABLE_ATTACHMENTS, $id);

        if( count($attachment) ){
            
            return new TripManFileProvider( $attachment );
        }
        
        return null;
    }
    /**
     * Muestra la etiqueta del elemento relacionado
     * @param String $related
     * @param int $related_id
     * @return string
     */
    public static final function displayRelated( $related, $related_id = 0 ){
        switch(strtolower( $related ) ){
            case 'booking':
                if( $related_id ){
                    return TripManStringProvider::__('Reserva No.%s',$related_id);
                }
                return TripManStringProvider::__('Reservas');
            case 'report':
                if( $related_id ){
                    return TripManStringProvider::__('Informe No.%s',$related_id);
                }
                return TripManStringProvider::__('Informes');
            default:
                return '--';
        }
    }
    /**
     * Genera una url de acceso al elemento relacionado
     * @param string $related
     * @param mixed $related_id
     * @return string
     */
    public static final function generateRelatedUrl( $related , $related_id ){
        
        switch( $related ){
            case 'booking':
                return TripManRequestProvider::requestAdminRoute(
                            'booking.edit', array(TripManBookingModel::BOOKING_ID=>$related_id));
            case 'report':
                return TripManRequestProvider::requestAdminRoute(
                            'reports', array('id'=>$related_id));
        }
        
        return '';
    }
}



