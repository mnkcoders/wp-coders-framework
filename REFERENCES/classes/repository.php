<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

/**
 * Allow only child classes to access the repository core attributes
 */
class Repository extends Component{

    const ENCODING_DEFAULT = 'default';
    const ENCODING_BASE64 = 'base64';
    const ENCODING_HEX = 'hex';
    /**
     * @var string
     */
    private $_buffer = '';
    
    /**
     * @param string $storage
     */
    protected function __construct( $storage , array $data = array( ) ) {
        
        $this->set( 'app', $storage)
                //->set('key', $app->endPointKey())
                ->set('name', isset($data['name']) ? $data['name'] : 'undefined' )
                ->set('type', isset($data['type']) ? $data['type'] : 'text/plain' )
                ->set('size', isset($data['size']) ? $data['size'] : 0 );
    }
    /**
     * @return string
     */
    public function getPath( ){
        
        if( $this->getId() !== FALSE ){

            $root = self::path($this->get('app'));

            if( strlen($root) ){
                return sprintf('%s/%s',$root,$this->getId());
            }
        }

        return FALSE;
    }
    /**
     * @param string $source
     * @param boolean $create
     * @return string
     */
    protected static final function path( $source , $create = FALSE ){

        $storage = \CodersApp::repoPath( $source );
        
        if(strlen($storage)){
   
            if( $create && !file_exists($storage) ){
    
                mkdir($storage);
                
                return file_exists($storage) ? $storage : '';
            }
        }
        
        return $storage;
    }
    /**
     * @return string
     */
    protected static function generateId(){
        return md5(uniqid(date('YmdHis'),TRUE));
    }
    /**
     * @return string
     */
    public function getId(){ return $this->get('id', FALSE); }
    /**
     * @return string
     */
    public function getName(){ return $this->get('name','unnamed'); }
    /**
     * @return string
     */
    public function getType(){ return $this->get('type','text/plain'); }
    /**
     * @return string
     */
    public function getExtension(){ return $this->get('extension','txt'); }
    /**
     * @return string
     */
    public function getDescription(){ return $this->get('description',''); }

    /**
     * @param boolean $unload
     * @return \CODERS\Framework\Repository
     */
    public function save( $unload = FALSE ){
        
        $resource = $this->getPath();
        
        if( strlen($resource) ){

            if( strlen($this->_buffer)){

                switch( self::encoding() ){
                    case self::ENCODING_BASE64:
                        $buffer = base64_encode( $this->_buffer );
                        break;
                    case self::ENCODING_HEX:
                        $buffer = bin2hex( $this->_buffer );
                        break;
                    default:
                        $buffer = $this->_buffer;
                        break;
                }

                $bytes = file_put_contents( $resource, $buffer );
                
                if( $bytes === FALSE || $bytes === 0 ){
                    throw new \Exception( 'FILE_IO_ERROR' );
                }
                elseif( $unload ){
                    $this->_buffer = '';
                }
            }
        }
        
        return $this;
    }
    /**
     * @return \CODERS\Framework\Repository
     */
    public function load(){
        
        $resource = $this->getPath();
        
        if(strlen($resource) && file_exists($resource)){
            
            $buffer = file_get_contents($resource);
            
            if( $buffer !== FALSE ){
                
                switch( self::encoding() ){
                    case self::ENCODING_BASE64:
                        $this->_buffer = base64_decode($buffer);
                        break;
                    case self::ENCODING_HEX:
                        $this->_buffer = hex2bin($buffer);
                        break;
                    default:
                        $this->_buffer = $buffer;
                }
            }
        }
        
        return $this;
    }
    /**
     * @param array $meta
     * @param string $storage
     * @param string $content
     * @return \CODERS\Framework\Repository
     * @throws Exception
     */
    public static function create( array $meta , $storage = 'root', $content = '' ){
        
        if(array_key_exists('name', $meta)){
            throw new Exception('MISSING_RESOURCE_META_NAME');
        }
        if(array_key_exists('type', $meta)){
            throw new Exception('MISSING_RESOURCE_META_TYPE');
        }
        //if(array_key_exists('size', $meta)){
        //    throw new Exception('MISSING_RESOURCE_META_SIZE');
        //}
        if( !array_key_exists('size', $meta)){
            $meta['size'] = strlen($content);
        }
        
        $meta['id'] = self::generateId();
        
        $resource = new Repository($storage, $meta );
        
        if(strlen($content)){
            $resource->_buffer = $content;
        }
        
        return $resource->save(TRUE);
    }
    /**
     * Upload a file
     * @param string $input
     * @param string $storage
     * @return \CODERS\Framework\Repository|boolean
     * @throws \Exception
     */
    public static final function upload( $input , $storage = 'root' ){
        
        try{
            $destination = self::path($storage,TRUE); //  $this->getPath($file['id']);
            
            $fileMeta = array_key_exists($input, $_FILES) ? $_FILES[ $input ] : array();

            if( count($fileMeta) === 0 ){
                throw new \Exception('Invalid file');
            }
            
            if( strlen($destination) === 0 ){
                throw new \Exception('Invalid desstination');
            }
            
            switch( $fileMeta['error'] ){
                case UPLOAD_ERR_CANT_WRITE:
                    throw new \Exception('UPLOAD_ERROR_READ_ONLY');
                case UPLOAD_ERR_EXTENSION:
                    throw new \Exception('UPLOAD_ERROR_INVALID_EXTENSION');
                case UPLOAD_ERR_FORM_SIZE:
                    throw new \Exception('UPLOAD_ERROR_SIZE_OVERFLOW');
                case UPLOAD_ERR_INI_SIZE:
                    throw new \Exception('UPLOAD_ERROR_CFG_OVERFLOW');
                case UPLOAD_ERR_NO_FILE:
                    throw new \Exception('UPLOAD_ERROR_NO_FILE');
                case UPLOAD_ERR_NO_TMP_DIR:
                    throw new \Exception('UPLOAD_ERROR_INVALID_TMP_DIR');
                case UPLOAD_ERR_PARTIAL:
                    throw new \Exception('UPLOAD_ERROR_INCOMPLETE');
                case UPLOAD_ERR_OK:
                    break;
            }
            
            $buffer = file_get_contents($fileMeta['tmp_name']);
            
            unlink($fileMeta['tmp_name']);
           
            if( $buffer !== FALSE ){

                return self::create($fileMeta,$storage,$buffer);
            }
        }
        catch (\Exception $ex) {
            print( $ex->getMessage() );
        }
        
        
        return FALSE;
    }
    /**
     * @param string $origin
     * @param string $destination
     * @param type $encode
     * @return boolean
     */
    private static final function moveUpload( $origin , $destination , $encode = self::ENCODING_DEFAULT ){
        
        if(file_exists($origin)){
            
            $buffer = file_get_contents($origin);
            
            if( $buffer !== FALSE ){
                switch( $encode ){
                    case self::ENCODING_BASE64:
                        $buffer = base64_encode($buffer);
                    case self::ENCODING_HEX:
                        $buffer = bin2hex($buffer);
                }
                $bytes = file_put_contents($destination, $buffer);
                
                return $bytes !== FALSE && $bytes > 0;
            }
        }
        
        //return move_uploaded_file($origin, $destination);
        
        return FALSE;
    }
    /**
     * @return string
     */
    public static final function encoding(){
        return get_option('coders_repo_encoding',self::ENCODING_DEFAULT);
    }
}


