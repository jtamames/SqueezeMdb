<?php
require_once("./application/libraries/GameParser.php");

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
ini_set("auto_detect_line_endings", true);

class GeneFileParser2 extends GameParser {
    
    static protected $col_names = array(
        "ORF", "CONTIG ID"
        , "GC PERC"
        , "TAX ORF", "KEGG ID", "KEGGFUN", "KEGGPATH", "COG ID", "COGFUN", "COGPATH"
    );
    
    protected $col_names_idx = array(
        "ORF"=>-1, "CONTIG ID"=>-1, "LENGTH AA"=>-1, "GC perc"=>-1, "GENNAME"=>-1, 
        "TAX ORF"=>-1, "KEGG ID"=>-1, "KEGGFUN"=>-1, "KEGGPATH"=>-1, "COG ID"=>-1,
        "COGFUN"=>-1, "COGPATH"=>-1, "PFAM"=>-1
        //, "AASEQ"=>-1
    );
        
    function __construct() {
        $this->samples_cols_match = array();
    }
    
    protected function get_column_names() {
        return self::$col_names;
    }

    protected function parse() {
        
    }
    
    protected function validate_header($header, $sample) {
        for ($i = 0; $i < sizeof($header); $i++) {
            switch ($header[$i]) {
                case "ORF":
                case "CONTIG ID":
                case "LENGTH AA":
                case "GC perc":
                case "GENNAME": 
                case "TAX ORF":
                case "KEGG ID":
                case "KEGGFUN":
                case "KEGGPATH":
                case "COG ID":
                case "COGFUN":
                case "COGPATH":
                case "PFAM":
                //case "AASEQ":
                    $this->col_names_idx[$header[$i]] = $i;
                    log_message("DEBUG", "::> '".$header[$i]."' COL: {$i}");
                    break;
                default:
                    if (strpos($header[$i], "TPM") === 0) {
                        $aux_sample = substr($header[$i], 4);
                        if (!isset($sample[$aux_sample]) || $sample[$aux_sample] == NULL) {
                            $this->error_message = "Wrong header: Unknown sample name '{$aux_sample}' in column {$i}";
                            return FALSE;
                        } else {
                            $this->samples_cols_match[$aux_sample]["norm"] = $i;
                        }
                    } elseif (strpos($header[$i], "RAW READ COUNT") === 0) {
                        $aux_sample = substr($header[$i], 15);
                        if (!isset($sample[$aux_sample]) || $sample[$aux_sample] == NULL) {
                            $this->error_message = "Wrong header: Unknown sample name '{$aux_sample}' in column {$i}";
                            return FALSE;
                        } else {
                            $this->samples_cols_match[$aux_sample]["raw"] = $i;
                        }
                    }
                    break;
            }
        }
        // Check that the header has all the mandatory columns
        foreach ($this->col_names_idx as $column => $index) {
            if ($index < 0) {
                $this->error_message = "Wrong header: Missing column '{$column}'";
                return FALSE;
            }
        }
        return TRUE;
    }
    
    function parse_header($filehandle, $samples) {
        $this->row = 0;
        while (($data = fgetcsv($filehandle, 0, "\t")) !== FALSE) {
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
        while ($j < $num_rows && (($data = fgetcsv($filehandle, 0, "\t")) !== FALSE)) {
            if (sizeof($data) > 0 ) {
                    $gene = new Gene();
                    $gene->orf = $data[$this->col_names_idx["ORF"]];
                    $gene->name = $data[$this->col_names_idx["GENNAME"]];
                    $gene->contig = trim($data[$this->col_names_idx["CONTIG ID"]]);
                    $gene->gc_per = trim($data[$this->col_names_idx["GC perc"]]);
                    $gene->taxonomy = trim($data[$this->col_names_idx["TAX ORF"]]);
                    $gene->kegg_id = trim($data[$this->col_names_idx["KEGG ID"]]);
                    $gene->kegg_function = trim($data[$this->col_names_idx["KEGGFUN"]]);
                    $gene->kegg_path = trim($data[$this->col_names_idx["KEGGPATH"]]);
                    $gene->cog_id = trim($data[$this->col_names_idx["COG ID"]]);
                    $gene->cog_function = trim($data[$this->col_names_idx["COGFUN"]]);
                    $gene->cog_path = trim($data[$this->col_names_idx["COGPATH"]]);
                    $gene->pfam = trim($data[$this->col_names_idx["PFAM"]]);
                    //$gene->sequence = trim($data[$this->col_names_idx["AASEQ"]]);
                    
                    // Fill abundances in samples
                    $abundances = array();
                    foreach ($this->samples_cols_match as $sample => $col) {
                        $abundances[$sample]["raw"] = trim($data[$col["raw"]]);
                        $abundances[$sample]["norm"] = trim($data[$col["norm"]]);
                    }
                    $gene->abundances = $abundances;

                    $result[] = $gene;
                }
            $j++;
            $this->row++;
        }
        
        return $result;
    }
}
