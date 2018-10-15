<?php
require_once("./application/libraries/GameParser.php");

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
ini_set("auto_detect_line_endings", true);

class BinFileParser extends GameParser {
    static protected $col_names = array("Bin ID","Method","Tax","Size","GC perc","Num contigs"
                ,"Chimerism","Chimerism rank","Completeness","Contamination","Strain Het");
    
    function __construct() {
        $this->samples_cols_match = array();
    }
    
    protected function get_column_names() {
        return self::$col_names;
    }
    
    function parse($filename, $samples) {
        $ci = get_instance();
        $result = array();
        $row = 1;
        $handle = fopen($filename, "rb");
        $num_samples = sizeof($samples);
        $bin_names = array();
        while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {
            //$data = fgetcsv($handle, 1000, "\t");
            if ($row == 2) { // We ignore the first line, and the second one is the header
                if ($this->validate_header($data, $samples) == FALSE) {
                    return FALSE;
                }
            } elseif ($row > 2) { // data rows
                if (sizeof($data) > 0) {
                    if (sizeof($data) != sizeof(self::$col_names)+2*$num_samples) {
                        $this->error_message = "Insuficient number of columns at row ".$row;
                        return FALSE;
                    } else {
                        $bin = new Bin();
                        $bin->name = $data[0];
                        // Validate that the bin name is unique
                        if (isset($bin_names[$bin->name])) {
                            $this->error_message = "Repeated bin name at row ".$row;
                            return FALSE;
                        } else {
                            $bin_names[$bin->name] = TRUE;
                        }
                        $bin->method = trim($data[1]);
                        $bin->taxonomy = trim($data[2]);
                        $bin->size = trim($data[3]);
                        $bin->gc_per = trim($data[4]);
                        $bin->contig_num = trim($data[5]);
                        $bin->chimerism = trim($data[6]);
                        $bin->completeness = trim($data[8]);
                        $bin->contamination = trim($data[9]);
                        $bin->strain_het = trim($data[10]);
                        // Fill abundances in samples
                        $abundances = array();
                        foreach ($this->samples_cols_match as $sample => $col) {
                            $abundances[$sample]["cover"] = trim($data[$col["cover"]]);
                            $abundances[$sample]["norm"] = trim($data[$col["norm"]]);
                        }
                        $bin->abundances = $abundances;

                        $result[] = $bin;
                    }
                }
            }
            $row++;
        }
        fclose($handle);
        
        return $result;
    }

}