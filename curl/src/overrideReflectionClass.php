<?php

namespace grikdotnet\curl;

class overrideReflectionClass extends \ReflectionClass
{
	/**
	 * Returns the array with a list of overridden methods
	 * @return array
	 */
	public function getOverrideMethods()
    {
        //find ancestor name and methods
        $ancestor = $this;
        while ( $tmp = $ancestor->getParentClass() ){
            $ancestor = $tmp;
        }
        $ancestor_class_name = $ancestor->getName();
        $methodsObject = $ancestor->getMethods();
        if (!$methodsObject){
            //no methods defined in the upmost parent
            return array();
        }
	    $ancestor_methods = [];
        foreach ($methodsObject as $v){
            $ancestor_methods[]=$v->getName();
        }

        //get all methods of the class/object and find the overloaded ones
        $methods = $this->getMethods();
        
        if ( !$methods){
            return array();
        }

        $result = array();
        foreach ( $methods as $method ){
            $method_class_name = $method->getDeclaringClass()->getName();
            $method_name = $method->getName();
            if ( $method_class_name  !== $ancestor_class_name && in_array( $method_name, $ancestor_methods ) ){
                $result[] = $method_name;
            }
        }
        return $result;
    }
}

