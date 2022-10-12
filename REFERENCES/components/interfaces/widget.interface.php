<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

/**
 * Interfaz de llamada de widgets
 */
interface IWidget{
    /**
     * Formulario de adminstración del widget en wp-admin
     */
    function form( $instance );
    /**
     * Guardado de parámetros del widget desde wp-admin
     */
    function update($new_instance, $old_instance);
    /**
     * display del widget en el front-end
     */
    function widget($args, $instance);
    /**
     * Permite emular el funcionamiento del acceso a la caché de la vista en el contexto de widget
     * @param string $var Nombre de la propiedad
     * @param mixed $default Resultado por omisión
     * @return mixed
     */
    function get_data($var,$default = null);
}