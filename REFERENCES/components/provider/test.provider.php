<?php namespace CODERS\Framework\Providers;

defined('ABSPATH') or die;

final class Test{
    
    public function __toString() {
        return get_class($this);
    }
    
}