<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Allen Minix <allen@allenminix.com>                          |
// |                                                                      |
// +----------------------------------------------------------------------+
//
// $Id:

/*============================================================
// globalvars_class.php
//
// This class is a collection of functions used to assign
// superglobals such as _SERVER, _POST, _GET, _COOKIE, etc. 
// to manually assigned variables.  This ensures 
// of older applications running under PHP with
// register_globals = off.
//
// Also included in this class are functions for bounds
// checking on variables to thwart hacking attempts via
// passing bad values.
//
// Functions included are:
//
//	varValidate($var_name, $var_type, $var_length, $var_regex)
//	globalMethod($vars_2_check)
//
// Requirements:
//
//	$var_name: (only if passed directly to varValidate)
//		Variable to check
//
//	$var_type: (only if passed directly to varValidate)
//		The variable type to check against.  This
//		should be a valid type returned by gettype()
//
//	$var_length: (only if passed directly to varValidate)
//		The max allowable variable length.
//
//	$var_regex: (only if passed directly to varValidate)
//		A regular expression to compare the 
//		variable against for pattern matching
//
//	$vars_2_check:  (a multidimensional array)
//		[var_name][method]
//		          [type]
//		          [length]
//		          [regex]
//
// Values returned:
//
//	$var_validate_status (array from varValidate)
//	- Keys are param_errs, var_pass, and var_pass_comment
//
//----------------------------------------------------------
// Original author: Allen Minix
//                  allen@allenminix.com
//
// Created on:      2004-02-06
// Last modified:   
//
//==========================================================*/

class Global_Vars
{
	var $var_check_total;
	var $var_check_pass;
	var $varValidate_errs;
	var $varValidate_status;

	function varValidate($var_name, $var_type, $var_length, $var_regex) {
		$this->var_check_total  = 0;    // How many audits did we subject the var to?
		$this->var_check_pass   = 0;    // How many audits did the var actually pass?
		$this->varValidate_errs = 0;    // How many parameters were we passed?

        //  Check the variable against a regular expression, if provided...

        if ($var_regex) {
			$this->var_check_total = $this->var_check_total + 1;
			$this->varValidate_errs = $this->varValidate_errs + 1;
			if (ereg($var_regex, $var_name)) {
				$this->var_check_pass = $this->var_check_pass + 1;
			}

        //  Check the variable against type and length constraints, if both provided...
            
		} elseif (!$var_type = "" AND !$var_length = "") {
			$this->var_check_total = $this->var_check_total + 6;
			$this->varValidate_errs = $this->varValidate_errs + 6;
			if ((gettype($var_name) == $var_type) AND (strlen($var_name) <= $var_length)) {
				$this->var_check_pass = $this->var_check_pass + 6;
			} elseif ((gettype($var_name) == $var_type) AND (!strlen($var_name) <= $var_length)) {
				$this->var_check_pass = $this->var_check_pass + 4;
			} elseif ((!gettype($var_name) == $var_type) AND (strlen($var_name) <= $var_length)) {
				$this->var_check_pass = $this->var_check_pass + 2;
			}

        //  Check the variable against length contraints only...
            
		} elseif ($var_type = "" AND !$var_length = "") {
			$this->var_check_total = $this->var_check_total + 2;
			$this->varValidate_errs = $this->varValidate_errs + 2;
			$var_temp = $var_name;
			if (strlen($var_temp) == $var_length) {
				$this->var_check_pass = $this->var_check_pass + 2;
			}

        //  Check the variable against type constraints only...
            
		} elseif (!$var_type = "" AND $var_length = "") {
			$this->var_check_total = $this->var_check_total + 4;
			$this->varValidate_errs = $this->varValidate_errs + 4;
			if (gettype($var_name) == $var_type) {
				$this->var_check_pass = $this->var_check_pass + 4;
			}
		}

        //  Assign the status code relative to how many parameters were passed...
        
		switch ($this->varValidate_errs)
		{
			case 0:
				$this->varValidate_status['param_errs'] = "Variable type, length, and regex missing";
				break;
			case 1:
				$this->varValidate_status['param_errs'] = "Variable type and length missing";
				break;
			case 2:
				$this->varValidate_status['param_errs'] = "Variable type and regex missing";
				break;
			case 3:
				$this->varValidate_status['param_errs'] = "Variable type missing";
				break;
			case 4:
				$this->varValidate_status['param_errs'] = "Variable length and regex missing";
				break;
			case 5:
				$this->varValidate_status['param_errs'] = "Variable length missing";
				break;
			case 6:
				$this->varValidate_status['param_errs'] = "Variable regex missing";
				break;
			case 7:
				$this->varValidate_status['param_errs'] = "All paramaters provided";
				break;
		}

        //  Did the variable pass all applicable audits?
        
		if ($this->var_check_total == $this->var_check_pass) {
			$this->varValidate_status['var_pass'] = 1;
			$this->varValidate_status['var_pass_comment'] = "All variable checks passed";
		} else {

            //  Variable did not pass, so let's assign a status code telling what failed...
        
			$this->varValidate_status['var_pass'] = 0;
			$error_code = $this->var_check_total - $this->var_check_pass;
			switch ($error_code)
			{
				case 1:
					$this->varValidate_status['var_pass_comment'] = "Regex failed";
					break;
				case 2:
					$this->varValidate_status['var_pass_comment'] = "Length failed";
					break;
				case 3:
					$this->varValidate_status['var_pass_comment'] = "Length and regex failed";
					break;
				case 4:
					$this->varValidate_status['var_pass_comment'] = "Type failed";
					break;
				case 5:
					$this->varValidate_status['var_pass_comment'] = "Type and regex failed";
					break;
				case 6:
					$this->varValidate_status['var_pass_comment'] = "Type and length failed";
					break;
			}
		}

        // Pass the variable audit info back as a nice little gift-wrapped array...
        
		return $this->varValidate_status;
	}

	function globalMethod($vars_2_check){
        $temp_vars  = array();   // We need a place to store the vars until checked
        $var_status = array();   // This will hold the returned data from varValidate
		foreach ($vars_2_check AS $var_name => $var_item) {
			foreach ($var_item AS $var_info) {
				$method = "_" . strtoupper($var_info['method']);
				$type   = $var_info['type'];
				$length = $var_info['length'];
				$regex  = $var_info['regex'];
				if (!array_key_exists($var_name,${$method})) {
		                        ${$method}[$var_name]="";
				}

                // Let's not make it a global just yet...
                
				$temp_vars[$var_name] = &${$method}[$var_name];

                // Check the variable to see if it meets quality audits...
                
                $var_status = varValidate($var_name, $type, $length, $regex);

                // If the variable passed audits, give it global scope...

                if ($var_status['var_pass'] == 1) {
                    $GLOBAL[$var_name] = $temp_vars[$var_name];

                // If it fails, nuke any trace that might be in global scope...
                    
                } else {
                    unset($GLOBAL[$var_name]);
                }
            }
        }

        // Return $var_status just in case anyone wants to know what happened...

        return $var_status;
    }

}

?>




