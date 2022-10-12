<?php namespace CODERS\Framework;

defined('ABSPATH') or die;

abstract class CalendarModel extends \CODERS\Framework\Model{
    
    protected function __construct(array $data = array()) {
        
        parent::__construct($data);

    }
    
}