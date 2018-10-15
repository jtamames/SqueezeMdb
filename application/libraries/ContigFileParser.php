<?php
require_once("./application/libraries/GameParser.php");

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
ini_set("auto_detect_line_endings", true);

class ContigFileParser extends GameParser {
    
    protected $row;
    
    static protected $col_names = array("Contig ID","Tax","Chimerism","Chimerism Rank","GC perc","Length","Num genes","Bin ID");

    private $methods;
    
    function __construct() {
        $this->samples_cols_match = array();
        $this->methods = array();
    }
    
    protected function get_column_names() {
        return self::$col_names;
    }
    
    function parse($filename, $samples) {
        $ci = get_instance();
        $result = array();
        $this->row = 1;
        $handle = fopen($filename, "rb");
        $num_samples = sizeof($samples);
        while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {
            if ($this->row == 1) { // The first line is the header
                if ($this->validate_header($data, $samples) == FALSE) {
                    return FALSE;
                }
            } elseif ($this->row > 1) { // data rows
                if (sizeof($data) > 0) {
                    if (sizeof($data) != sizeof(self::$col_names)+2*$num_samples) {
                        $this->error_message = "Insuficient number of columns at row ".$this->row;
                        return FALSE;
                    } else {
                        $contig = new Contig();
                        $contig->name = $data[0];
                        $contig->taxonomy = $data[1];
                        $contig->chimerism = $data[2];
                        $contig->gc_per = $data[4];
                        $contig->size = $data[5];
                        $contig->genes_num = $data[6];
                        // TODO: parse bins x methods
                        $bins = array();
                        if (isset($data[7]) && sizeof($data[7]) > 0 ) {
                            log_message("DEBUG", "1:::::::> {$data[7]}");
                            $aux_meth = json_decode($data[7], TRUE);
                            log_message("DEBUG", "2:::::::> ".  implode(",", $aux_meth));
                            $aux_meth = $aux_meth["\"Bins\""];
                            log_message("DEBUG", "3:::::::> ".  implode(",", $aux_meth));
                            $bins = array();
                            for ($i = 0; $i < sizeof($aux_meth); $i++) {
                                foreach ($aux_meth[$i] as $method => $bin) {
                                    log_message("DEBUG", "::::> Data: {$data[7]} Meth: {$method}");
                                    $bins[$bin] = $method;
                                }
                            }
                        }
                        $contig->bins = $bins;
                        
                        // Fill abundances in samples
                        $abundances = array();
                        foreach ($this->samples_cols_match as $sample => $col) {
                            $abundances[$sample]["cover"] = trim($data[$col["cover"]]);
                            $abundances[$sample]["norm"] = trim($data[$col["norm"]]);
                        }
                        $contig->abundances = $abundances;

                        $result[] = $contig;
                    }
                }
            }
            $this->row = $this->row+1;
        }
        
        return $result;
    }
    
    function parse_header($filehandle, $samples) {
        $this->row = 0;
        while (($data = fgetcsv($filehandle, 1000, "\t")) !== FALSE) {
            $this->row++;
            if ((sizeof($data) > 0) && (strpos($data[0], "#") !== 0)) { // The first line is the header
                return $this->validate_header($data, $samples);
            }            
        }
        $this->error_message = "Error parsing contig file: no header found";
        return FALSE;
    }
    
    function parse_data($filehandle, $samples, $num_rows) {
        $result = array();
        $num_samples = sizeof($samples);
        $ci = get_instance();
        // Check the file handle
        if (!isset($filehandle) || $filehandle == NULL) {
            log_message("error", "Error parsing Contig file: Invalid file  handle");
            $this->error_message = "Error parsing Contig file: Invalid file  handle";
            return FALSE;
        }
        // prior to invoke this method, the parse_header method has to be invoked
        if (!isset($this->samples_cols_match) || $this->samples_cols_match == NULL || sizeof($this->samples_cols_match) == 0) {
            log_message("error", "Error parsing Contig file: Header not parsed");
            $this->error_message = "Error parsing Contig file: Header not parsed";
            return FALSE;            
        }
        $j = 0;
        while ($j < $num_rows && (($data = fgetcsv($filehandle, 1000, "\t")) !== FALSE)) {
            if (sizeof($data) > 0) {
                if (sizeof($data) != sizeof(self::$col_names)+2*$num_samples) {
                    $this->error_message = "Insuficient number of columns at row ".$this->row;
                    return FALSE;
                } else {
                    $contig = new Contig();
                    $contig->name = $data[0];
                    // Validate that the contig name is unique
                    if (isset($contig_names[$contig->name])) {
                        $this->error_message = "Repeated contig name at row ".$this->row;
                        return FALSE;
                    } else {
                        $contig_names[$contig->name] = TRUE;
                    }
                    $contig->taxonomy = $data[1];
                    $contig->chimerism = $data[2];
                    $contig->gc_per = $data[3];
                    $contig->size = $data[4];
                    $contig->genes_num = $data[5];
                    // TODO: parse bins x methods
                    $bins = array();
                    if (isset($data[7]) && sizeof($data[7]) > 0 ) {
                        $aux_meth = json_decode($data[7], TRUE);
                        $aux_meth = $aux_meth["Bins"];
                        $bins = array();
                        for ($i = 0; $i < sizeof($aux_meth); $i++) {
                            foreach ($aux_meth[$i] as $method => $bin) {
                                $bins[$bin] = $method;
                            }
                        }
                    }
                    $contig->bins = $bins;
                    // Fill abundances in samples
                    $abundances = array();
                    foreach ($this->samples_cols_match as $sample => $col) {
                        $abundances[$sample]["cover"] = trim($data[$col["cover"]]);
                        $abundances[$sample]["norm"] = trim($data[$col["norm"]]);
                    }
                    $contig->abundances = $abundances;

                    $result[] = $contig;
                }
            }
            $j++;
            $this->row++;
        }
        
        return $result;
    }
}