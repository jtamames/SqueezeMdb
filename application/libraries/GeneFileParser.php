<?php
require_once("./application/libraries/GameParser.php");

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
ini_set("auto_detect_line_endings", true);

class GeneFileParser extends GameParser {
    
    static protected $col_names = array(
        "ORF", "CONTIG ID"
        , "GC PERC"
        , "TAX ORF", "KEGG ID", "KEGGFUN", "KEGGPATH", "COG ID", "COGFUN", "COGPATH"
    );
    
    static protected $col_names_idx = array(
        "ORF"=>-1, "CONTIG ID"=>-1
        , "GC PERC"=>-1, "GENNAME"=>-1
        , "TAX ORF"=>-1, "KEGG ID"=>-1, "KEGGFUN"=>-1, "KEGGPATH"=>-1, "COG ID"=>-1, "COGFUN"=>-1, "COGPATH"=>-1
    );
        
    function __construct() {
        $this->samples_cols_match = array();
    }
    
    protected function get_column_names() {
        return self::$col_names;
    }

    protected function parse() {
        
    }
    
    protected function validate_header2($header, $sample) {
        for ($i = 0; $i < sizeof($header); $i++) {
            
        }
    }
    
    protected function validate_header($header, $sample) {
        $num_samples = sizeof($sample);
        $col_names = $this->get_column_names();
        log_message("debug", ":::::> col names: ".sizeof($col_names)." samples: {$num_samples} target: ".sizeof($header));
        $num_fixed_cols = sizeof($col_names);
        log_message('debug', "@@@@@@@@@@@@> FC: {$num_fixed_cols} NS: {$num_samples} HS: ".sizeof($header));
        if ($num_samples*2 + $num_fixed_cols != sizeof($header)) {
            $this->error_message = "Wrong number of columns";
            return FALSE;
        }
        // Validate fixed name columns
        $sample_cols = 0;
        for ($i = 0; $i < sizeof($header); $i++) {
            
            if ($i < $num_fixed_cols) {
                if ($header[$i] != $col_names[$i]) {
                    $this->error_message = "Wrong header: Expecting ".$col_names[$i];
                    return FALSE;
                }
            } else {
                $aux_sample = "";
                if (strpos($header[$i],"RAW COUNTS") === 0) {
                    $aux_sample = substr($header[$i],11);
                    $this->samples_cols_match[$aux_sample]["raw"] = $i;
                }
                elseif (strpos($header[$i],"COUNTS") === 0) {
                    $aux_sample = substr($header[$i],7);
                    $this->samples_cols_match[$aux_sample]["norm"] = $i;
                }
                else {
                    $this->error_message = "Wrong header: Unknown column '".$header[$i]."'";
                    return FALSE;
                }
                $sample_cols++;
            }
        }
        if ($sample_cols != $num_samples*2) {
            $this->error_message = "Missing sample data columns";
            return FALSE;
        }
        $this->error_message = "";
        return TRUE;
    }
    
    function parse_header($filehandle, $samples) {
        $this->row = 0;
        while (($data = fgetcsv($filehandle, 1000, "\t")) !== FALSE) {
            $this->row++;
            if ((sizeof($data) > 0) && (strpos($data[0], "#") !== 0)) { // The first line is the header
                return $this->validate_header($data, $samples);
            }            
        }
        $this->error_message = "Error parsing Genes file: no header found";
        return FALSE;
    }
    
    function parse_data($filehandle, $samples, $num_rows) {
        $this->row = 0;
        $result = array();
        $num_samples = sizeof($samples);
        // Check the file handle
        if (!isset($filehandle) || $filehandle == NULL) {
            log_message("error", "Error parsing Genes file: Invalid file  handle");
            $this->error_message = "Error parsing Genes file: Invalid file  handle";
            return FALSE;
        }
        // prior to invoke this method, the parse_header method has to be invoked
        if (!isset($this->samples_cols_match) || $this->samples_cols_match == NULL || sizeof($this->samples_cols_match) == 0) {
            log_message("error", "Error parsing Genes file: Header not parsed");
            $this->error_message = "Error parsing Genes file: Header not parsed";
            return FALSE;            
        }
        $j = 0;
        while ($j < $num_rows && (($data = fgetcsv($filehandle, 10000, "\t")) !== FALSE)) {
            if (sizeof($data) > 0 ) {
                if (sizeof($data) != sizeof(self::$col_names)+$num_samples*2) {
                    $this->error_message = "Insuficient number of columns at row ".$this->row;
                    log_message('error', "Insuficient number of columns at row ".$this->row." Cols detected: ".sizeof($data)." Starting with: ".$data[0].", Ending with: ".$data[sizeof($data)-1]);
                    return FALSE;
                } else {
                    $gene = new Gene();
                    $gene->name = $data[0];
                    $gene->contig = trim($data[1]);
                    $gene->gc_per = trim($data[2]);
                    $gene->taxonomy = trim($data[3]);
                    $gene->kegg_id = trim($data[4]);
                    $gene->kegg_function = trim($data[5]);
                    $gene->kegg_path = trim($data[6]);
                    $gene->cog_id = trim($data[7]);
                    $gene->cog_function = trim($data[8]);
                    $gene->cog_path = trim($data[9]);
                    
                    // Fill abundances in samples
                    $abundances = array();
                    foreach ($this->samples_cols_match as $sample => $col) {
                        $abundances[$sample]["raw"] = trim($data[$col["raw"]]);
                        $abundances[$sample]["norm"] = trim($data[$col["norm"]]);
                    }
                    $gene->abundances = $abundances;

                    $result[] = $gene;
                }
            }
            $j++;
            $this->row++;
        }
        
        return $result;
    }
}
