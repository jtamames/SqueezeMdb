<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
ini_set("auto_detect_line_endings", true);

class Contig {
    var $id;
    
    var $name;
    
    var $taxonomy;
        
    var $size;
    
    var $genes_num;
    
    var $gc_per;
    
    var $chimerism;
    
    var $bins;
    
    var $abundances;
}