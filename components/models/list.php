<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

abstract class ListModel extends \CODERS\Framework\Model{
    
    protected function __construct(array $data = array()) {
        
        parent::__construct($data);

    }
    
}