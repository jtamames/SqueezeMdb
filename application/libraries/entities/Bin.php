<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
ini_set("auto_detect_line_endings", true);

class Bin {
    var $id;
    
    var $name;
    
    var $method;
    
    var $taxonomy;
    
    var $size;
    
    var $contig_num;
    
    var $gc_per;
    
    var $chimerism;
    
    var $completeness;
    
    var $contamination;
    
    var $strain_het;
    
    var $abundances;
}