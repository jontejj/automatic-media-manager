<?php
/*
$stmt = $mysqli->prepare("CALL item_add(?, ?, ?, ?)"); 

$sp = new DatabaseStatementParameter(); 

$sp->Add_Parameter('mydescription', ParameterType::$STRING); 
$sp->Add_Parameter('myean', ParameterType::$STRING); 
$sp->Add_Parameter('myprice', ParameterType::$DOUBLE); 
$sp->Add_Parameter('myactive', ParameterType::$INTEGER); 

// call this to bind the parameters 
$sp->Bind_Params($stmt); 

//you can then modify the values as you wish 
$sp->Set_Parameter('myactive',0); 
$sp->Set_Parameter('mydescription','whatever'); 
    
// execute prepared statement  
$stmt->execute(); 
*/

class DatabaseStatementBindParameter
{ 
    private $array = array(); 
    
    public function __constructor() 
    { 
    } 
    
    public function addParameter($name, $type, $value = NULL) 
    { 
        $this->array[$name] = array("type" => $type, "value" => $value);    
    } 
    
    public function getTypeString() 
    { 
        $types = "";    
    
        foreach($this->array as $name => $la) 
            $types .= $la['type']; 
        
        return $types; 
    } 
    
    public function setParameter($name, $value) 
    { 
        if (isset($this->array[$name])) 
        { 
            $this->array[$name]["value"] = $value; 
            return true; 
        } 
        return false; 
    } 
    
    public function bindParams(&$stmt) 
    { 
        $ar = Array(); 
        
        $ar[] = $this->getTypeString(); 
        
        foreach($this->array as $name => $la) 
            $ar[] = &$this->array[$name]['value']; 
        if(strlen($ar[0]) > 0)
        	return call_user_func_array(array($stmt, 'bind_param'),$ar); 
        else
        	return false;
    } 
} 

class DatabaseStatementBindResult 
{ 
    private $identifiers = array();
    private $temp = array();
    private $results = array(); 

    public function bindResult(&$stmt) 
    { 
        call_user_func_array(array($stmt, 'bind_result'), $this->results); 
    } 
    public function addParameter($identifer)
    {
    	$this->identifiers [] = $identifer;
    	$this->results[]	= &$this->temp[count($this->identifiers)]; 
    }
    
    public function Get_Array() 
    { 
        return $this->results;    
    } 
    
    public function get($identifier) 
    { 
        return $this->results[array_search($identifier,$this->identifiers)]; 
    } 
} 
?>