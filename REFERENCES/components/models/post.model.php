<?php namespace CODERS\Framework\Models;

defined('ABSPATH') or die;
/**
 * Modelo de listado de registros para gestionar colecciones
 */
abstract class PostModel extends \CODERS\Framework\Dictionary implements \CODERS\Framework\IModel{
    
    public function __construct() {
        
        $this->addField('name')
                ->addField('description')
                ->addField('excerpt');
    }
    /**
     * 
     * @return string
     */
    public function __toString() {
        
        $name = \CodersApp::nominalize( parent::__toString() );
        
        return preg_replace('/[a-z]*-post$/', '', $name);
    }
    /**
     * @return string
     */
    public function type(){
        return strval($this);
    }
    /**
     * @return array
     */
    public function definition(){
        
        $name = strval($this);
        
        return array(
            'labels' => array(
                'name' => __($name,'coders_framework'),
                'singular_name' => __($name,'coders_framework'),
            ),
            'public' => TRUE,
            'has_archive' => TRUE,
            'rewrite' => array( 'slug' => $name ),
        );
    }
}


