<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 
/**
 * 
 * @author luis
 *
 */
class Game_User
{
	var $id;
	
	var $name;
	
	var $surname;
	
	var $email;
	
	var $password;
	
	var $type;
        
        var $var_code;
        
        var $var_code_expiration;
	
	function __toString()
	{
            return $this->name." ".$this->surname;
	}
}