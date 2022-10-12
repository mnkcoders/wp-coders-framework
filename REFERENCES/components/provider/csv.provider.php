<?php namespace CODERS\Framework\Providers;

defined('ABSPATH') or die;

/**
 * Gestor básico de exportación a CSV
 */
final class CSV{
    
    private $_headers = array();
    
    private $_content = array();
    
    private $_separator = "\t";
    
    public final function __construct( array $data, $separator = NULL ) {
        
        if( !is_null($separator)){
            
            $this->_separator = $separator;
        }
        
        $count = $this->import( $data );
        
        TripManLogProvider::debug(sprintf('%s registros preparados para exportar a CSV',$count));
    }
    /**
     * Importa los datos para parsear a CSV
     * @param array $content
     * @return int Recuento de filas importadas
     */
    private final function import( array $content ){

        $headers_set = FALSE;
        
        $this->_content = array();
        
        foreach( $content as $row ){
            
            $data = array();

            foreach( $row as $header => $value ){
                
                if( !$headers_set && !in_array( $header, $this->_headers ) ){

                    $this->_headers[] = $header;
                }
                
                $data[ ] = strval( $value );
            }
            
            $this->_content[] = $data;
            
            if( !$headers_set ){

                $headers_set = TRUE;
            }
        }
        
        return count($this->_content);
    }
    /**
     * Exporta en formato CSV para escribir a un fichero
     * @param string $file_name Nombre del archivo a generar
     * @param string $related_to Elemento relacionado
     * @param int $related_id ID del elemento relacionado
     * @return \TripManFileProvider
     */
    public final function export( $file_name, $related_to = NULL, $related_id = 0 ){
        //cabecera
        $buffer = implode($this->_separator, $this->_headers ) . "\n";
        //filas
        foreach( $this->_content as $row ){
            $buffer .= implode($this->_separator, $row) . "\n";
        }
        
        return TripManFileProvider::createFile(
                $file_name,
                TripManFileProvider::FILE_TYPE_CSV,
                $buffer,
                $related_to,$related_id );
    }
}