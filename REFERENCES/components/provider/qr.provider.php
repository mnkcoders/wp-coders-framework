<?php namespace CODERS\Framework\Providers;

defined('ABSPATH') or die;

/**
 * Generador de codigos QR utilizando la api de google on-the-fly
 * <api>https://developers.google.com/chart/infographics/docs/qr_codes?csw=1</api>
 */
class QR{
    
    const ENCODING_UTF8 = 'UTF-8'; //por defecto
    const ENCODING_SHIFT = 'Shift_JIS';
    const ENCODING_ISO = 'ISO-8859-1';
    
    const ERR_LEVEL_L = 'L'; //tolera hasta 7% perdida de info
    const ERR_LEVEL_M = 'M'; //tolera hasta 15% de perdida
    const ERR_LEVEL_Q = 'Q'; //tolera hasta 25%
    const ERR_LEVEL_H = 'H'; //tolera hasta 30%
    /**
     * @var URL URL del servicio
     */
    const VENDOR_URL = 'https://chart.googleapis.com/chart';
    
    private $_data;
    
    private $_size = 300;
    
    private $_encoding = self::ENCODING_UTF8;
    
    private $_errorLevel = self::ERR_LEVEL_L;
    
    private $_margin = 4;
    /**
     * @param mixed $data
     * @param string $encoding
     * @param int $size
     * @param int $margin
     * @param string $errorRec
     */
    private function __construct( $data , $encoding = self::ENCODING_UTF8 , $size = 300 , $margin = 4 , $errorRec = 'L' ) {
        
        $this->set('margin', $margin)
                ->set('size', $size)
                ->set('encoding', $encoding)
                ->set('errorLevel', $errorRec)
                ->set('data', $data);
    }
    /**
     * @return type
     */
    public final function __toString() {
        return $this->_data;
    }
    /**
     * @param string $name
     * @return mixed
     */
    public final function __get($name) {
        
        $attr = '_' . strtolower($name);
        
        return isset( $this->$attr ) ? $this->$attr : null;
    }
    /**
     * @param string $name
     * @param mixed $value
     */
    public final function __set($name, $value) {
        $this->set($name, $value);
    }
    /**
     * Datos serializados en array de pares
     * @return array
     */
    public  final function export(){
        return array(
            'cht' => 'qr',
            'chs' => sprintf('%sx%s',$this->_size,$this->_size),
            'choe' => $this->_encoding,
            'chl' => $this->_data,
            'chld' => sprintf('%s|%s',$this->_errorLevel,$this->_margin)
        );
    }
    /**
     * @param type $attr
     * @param type $value
     * @return \TripManQRProvider
     */
    public final function set( $attr , $value ){

        switch( $attr ){
            case 'data':
                if( is_array( $value ) ){
                    $this->_data = json_encode( $value );
                }
                elseif(is_object($value)){
                    $this->_data = strval($value);
                }
                else{
                    $this->_data = $value;
                }
                break;
            default:
                $var = '_' . strtolower($attr);

                if( isset( $this->$var ) ){
                    $this->$var = $value;
                }
                break;
        }
        
        return $this;
    }
    /**
     * URL para generar el código QR (requiere acceso a  internet)
     * @return URL
     */
    public final function qr(){
        
        $query = array();
        
        foreach( $this->export() as $var => $val ){
            $query[] = sprintf('%s="%s"',$var,$val);
        }
        
        return self::VENDOR_URL . '?' . implode('&', $query);
    }
    /**
     * Crea un codigo QR
     * @param mixed $data
     * @param int $size
     * @param int $margin
     * @param string $encoding
     * @param string $errorRec
     * @return \TripManQRProvider
     */
    public static final function create( $data, $size = 300 , $margin = 4 , $encoding = self::ENCODING_UTF8, $errorRec = self::ERR_LEVEL_L ){
        
        return new TripManQRProvider(
                $data,
                $encoding,
                $size,
                $margin,
                $errorRec);
    }
}


