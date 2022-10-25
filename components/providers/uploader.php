<?php namespace CODERS\Framework\Providers;

defined('ABSPATH') or die;

class Uploader extends \CODERS\Framework\Provider{
    /**
     * @var array
     */
    private $_collection = array();
    
    /**
     * 
     * @param array $data
     */
    protected function __construct(array $data = array()) {

        parent::define('storage');
        parent::__construct($data);
    }
    /**
     * @return string
     */
    public final function storage(){
        $route = explode('.',$this->storage);
        $endpoint = \CodersApp::storage( count($route) > 1 ? $route[0] : '' );
        $folder = count($route) > 1 ? $route[1] : $route[0];
        return $endpoint . $folder;
    }

    /**
     * @return string
     */
    public final function path( $file ){
        return $this->storage() . '/' . $file;
    }
    /**
     * @return boolean
     */
    public final function exists(){
        return file_exists($this->storage());
    }
    /**
     * @param string $upload
     * @return array
     */
    private static final function importMeta($upload) {

        $files = array_key_exists($upload, $_FILES) ? $_FILES[$upload] : array();
        $list = array();
        if (count($files)) {
            if (is_array($files['name'])) {
                for ($i = 0; $i < count($files['name']); $i++) {
                    $list[] = array(
                        'name' => $files['name'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'type' => $files['type'][$i],
                        'error' => $files['error'][$i],
                    );
                }
            } else {
                $list[] = $files;
            }
        }
        return $list;
    }
    /**
     * @param string $input
     * @return boolean|$this
     * @throws \CODERS\Framework\Providers\Uploader
     */
    public final function upload( $input ){
        
        if( !$this->exists() ){
            \CodersApp::notify('Invalid storage path ' . $this->storage());
            return false;
        }

        foreach( self::importMeta($input) as $upload ) {
            try{
                switch( $upload['error'] ){
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

                $buffer = file_get_contents($upload['tmp_name']);
                unlink($upload['tmp_name']);
                unset($upload['tmp_name']);
                if( $buffer !== FALSE ){
                    $upload['id'] = parent::generateId($upload['name']);
                    $upload['path'] = $this->path($upload['id']);
                    if( file_put_contents($upload['path'], $buffer) ){
                        $upload['size'] = filesize($upload['path']);
                        //$upload['storage'] = $this->storage;
                        $this->_collection[ $upload['id'] ] = $upload;
                        //$collection[ $upload['id'] ] = $upload;
                    }
                }
                else{
                    throw new \Exception(sprintf('Failed to read upload buffer %s',$upload['name']));
                }
            }
            catch (\Exception $ex) {
                //send notification
                \CodersApp::notify( $ex->getMessage() );
            }
        }
        return $this;
    }
    /**
     * @param function $callable
     * @return array
     */
    public final function each( callable $callable ){
        $output = array();
        if(is_callable($callable)){
            foreach( $this->_collection as $id => $meta ){
                $meta['storage'] = $this->storage;
                $output[$id] = $callable( $meta );
            }
        }
        return $output;
    }
}

