<?php namespace CODERS\Framework\Models;

defined('ABSPATH') or die;

class File extends \CODERS\Framework\Model{
    /**
     * @param array $data
     */
    protected function __construct($route = '',array $data = array()) {

        parent::define('id',self::TYPE_TEXT)
                ->define('name',self::TYPE_TEXT)
                ->define('size',self::TYPE_NUMBER)
                ->define('type',self::TYPE_TEXT)
                ->define('storage',self::TYPE_TEXT,array('value'=>'uploads') )
                ->defineTimeStamps();

        parent::__construct( $route , $data );
    }
    /**
     * Array of headers required to stream this file
     * @param boolean $attach
     * @return array
     */
    public final function headers( $attach = FALSE ){
        
        $header = array(
            sprintf('Content-Type: %s' , $this->type ),
            sprintf( 'Content-Disposition: %s; filename="%s"',
                    //mark as attachment if cannot be embedded or not required as download
                    $attach || !$this->canEmbed() ? 'attachment' : 'inline',
                    $this->name ),
            sprintf( 'Content-Length: %s', $this->getSize() ),
            //sprintf( 'Cache-Control : %s, max-age=%s;', 'private' , 3600 )
            //'Cache-Control : public, max-age=3600;',
        );
        
        return $header;
    }
    /**
     * @param boolean $encodeB64
     * @return string|Boolean
     */
    public final function read( $encodeB64 = false ){
        
        $content = $this->exists() ? file_get_contents($this->path()) : FALSE;
        
        if( $content !== FALSE ){
            return $encodeB64 ? base64_encode($content) : $content;
        }
        return '';
    }
    /**
     * @param string $buffer
     * @return boolean
     */
    public final function write( $buffer ){
        return file_put_contents($this->path(), $buffer);
    }
    /**
     * @return boolean
     */
    public final function save(){
        $ts = $this->__ts();
        $db = $this->db();
        $result = $db->select('gallery' , 'COUNT(*) AS `ids`' , array('id'=>$this->id) );
        if( count($result) && $result[0]['ids'] > 0 ){
            $this->change('date_updated',$ts,true);
            var_dump($this->values());
            return $db->update('gallery', $this->updated(), array('id'=>$this->id)) > 0;
        }
        else{
            $this->change('date_updated',$ts)->change('date_created',$ts );
            var_dump($this->values());
            return $db->insert('gallery', $this->values()) > 0;
        }
        
        return false;
    }
    /**
     * @return string
     */
    public final function path(){
        return sprintf('%s/%s/%s',
                \CodersApp::storage(),
                $this->storage,
                $this->id);
    }
    /**
     * @return boolean
     */
    public final function exists(){
        return file_exists($this->path());
    }    
    /**
     * @return boolean
     */
    public final function isImage(){
        switch( $this->type ){
            case 'image/gif':
            case 'image/png':
            case 'image/jpeg':
            case 'image/bmp':
                return true;
        }
        return false;
    }
    /**
     * @return boolean
     */
    public final function isText(){
        return !$this->isImage();
    }
    /**
     * @return boolean
     */
    public final function valid(){
        return intval( $this->id ) > 0;
    }
    /**
     * Can be embedded in the webview?
     * @return boolean
     */
    public final function canEmbed(){
        switch( $this->type ){
            //images and media
            case 'image/jpg':
            case 'image/jpeg':
            case 'image/png':
            case 'image/gif':
            case 'image/bmp':
            //text files
            case 'text/plain':
            case 'text/html':
                return TRUE;
        }
        return FALSE;
    }
    /**
     * @param array $data
     * @return \CODERS\Framework\Providers\File
     */
    public static final function new( array $data = array( ) ){
        $endpoint = \CodersApp::instance()->endPoint();
        return new File( $endpoint ,$data );
    }
}