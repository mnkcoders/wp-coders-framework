<?php namespace CODERS\Framework\Models;

defined('ABSPATH') or die;
/**
 * Modelo básico para gestionar información genérica
 */
class Model implements \CODERS\Framework\IModel{
    
    public function __construct( array $data = null ) {
        if( !is_null($data)){
            foreach( $data as $var => $val){
                $this->set($var, $val);
            }
        }
    }
    /**
     * @return string
     */
    public function __toString() {
        return parent::getName();
    }
    
    public function __get($name) {
        
    }

    public function get($var, $default = null) {
        
    }

    public function has($var): boolean {
        
    }

    public function toArray(): array {
        
    }

}