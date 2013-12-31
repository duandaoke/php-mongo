<?php

namespace Sokil\Mongo;

class Structure
{
    protected $_data = array();
    
    public function __construct(array $data = null)
    {
        if($data) {
            $this->fromArray($data);
        }
    }
    
    public function __get($name)
    {
        return $this->get($name);
    }
    
    public function get($selector)
    {
        if(false === strpos($selector, '.')) {
            return  isset($this->_data[$selector]) ? $this->_data[$selector] : null;
        }

        $value = $this->_data;
        foreach(explode('.', $selector) as $field)
        {
            if(!isset($value[$field])) {
                return null;
            }

            $value = $value[$field];
        }

        return $value;
    }
    
    public function getObject($selector, $className)
    {
        $data = $this->get($selector);
        
        // prepare structure
        $structure =  new $className();
        if(!($structure instanceof Structure)) {
            throw new Exception('Wring structure class specified');
        }
        
        return clone $structure->fromArray($data);
    }
    
    public function getObjectList($selector, $className)
    {
        $data = $this->get($selector);
        
        // prepare structure
        $structure =  new $className();
        if(!($structure instanceof Structure)) {
            throw new Exception('Wring structure class specified');
        }
        
        return array_map(function($dataItem) use($structure) {
            return clone $structure->fromArray($dataItem);
        }, $data);
    }
    
    /**
     * Handle setting params through public property
     * 
     * @param type $name
     * @param type $value
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }
    
    /**
     * Store value to specified selector in local cache
     * 
     * @param type $selector
     * @param type $value
     * @return \Sokil\Mongo\Document
     * @throws Exception
     */
    public function set($selector, $value)
    {        
        $arraySelector = explode('.', $selector);
        $chunksNum = count($arraySelector);
        
        // optimize one-level selector search
        if(1 == $chunksNum) {
            $this->_data[$selector] = $value;
            
            return $this;
        }
        
        // selector is nested
        $section = &$this->_data;

        for($i = 0; $i < $chunksNum - 1; $i++) {

            $field = $arraySelector[$i];

            if(!isset($section[$field])) {
                $section[$field] = array();
            }

            $section = &$section[$field];
        }
        
        // update local field
        $section[$arraySelector[$chunksNum - 1]] = $value;
        
        return $this;
    }
        
    public function toArray()
    {
        return $this->_data;
    }
    
    public function fromArray(array $data)
    {
        $this->_data = array_merge($this->_data, $data);
        
        return $this;
    }
}