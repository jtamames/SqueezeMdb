<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
ini_set("auto_detect_line_endings", true);

class Gene {
    var $id;
    
    var $orf;
    
    var $name;
    
    var $contig;
    
    var $taxonomy;
    
    var $gc_per;
    
    var $sequence;
    
    var $kegg_id;
    
    var $kegg_function;
    
    var $kegg_path;

    var $cog_id;
    
    var $cog_function;
    
    var $cog_path;
    
    var $pfam;
    
    var $abundances;
}

