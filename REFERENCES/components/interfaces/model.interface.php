<?php namespace CODERS\Framework;
defined('ABSPATH') or die;
/**
 * Model definition methods
 */
interface IModel{
    /**
     * 
     */
    function __toString();
    /**
     * @param strinig $name
     * @return string
     */
    function __get( $name );
    /**
     * @param string $name
     * @param mixed $default
     */
    function get( $name , $default = null );
    /**
     * @param string $name
     * @return boolean
     */
    function has( $name );
}