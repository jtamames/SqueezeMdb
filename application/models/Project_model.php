<?php

class Project_model extends CI_Model {

    function __construct() {
        parent::__construct();

        $this->load->database('default');
        $this->db->save_queries = FALSE;
    }

    function get_user_projects($user_id) {
        $query = $this->db->query('SELECT pr.* FROM Project pr JOIN project_user pu ON (pr.id=pu.project_id) WHERE pu.game_user_id=?', array($user_id));
        $CI = & get_instance();
        $CI->load->library("entities/Game_Project");

        return $query->result("Game_Project");
    }

    function getProjects() {
        $query = $this->db->query('SELECT * FROM Project');
        $CI = & get_instance();
        $CI->load->library("entities/Game_Project");

        return $query->result("Game_Project");
    }

    function exists_project($name, $project_id = NULL) {
        $query = NULL;
        if ($project_id == NULL) {
            $query = $this->db->query('SELECT * FROM Project WHERE name=?', array($name));
        } else {
            $query = $this->db->query('SELECT * FROM Project WHERE id <> ? and name=?', array($project_id, $name));
        }
        return ($query->num_rows() > 0);
    }

    function insert_project($name, $description) {
        $project = array(
            "name" => $name,
            "creation_date" => date("Y-m-d"),
            "description" => $description
        );
        $this->db->insert("Project", $project);
        return $this->db->insert_id();
    }

    function update_project($id, $name, $description) {
        $this->db->query("UPDATE Project SET name=?, description=? WHERE id=?", array($name, $description, $id));
    }

    function insert_samples($project_id, $samples, $type) {
        $result = array();
        foreach ($samples as $sample) {
            $sam_arr['project_id'] = $project_id;
            $sam_arr['name'] = $sample->name;
            $this->db->insert("Sample", $sam_arr);
            $result[$sample->name] = $this->db->insert_id();
            foreach ($sample->properties as $prop) {
                $prop["Sample_ID"] = $result[$sample->name];
                $this->db->insert("Sample_properties", $prop);
            }
        }
        return $result;
    }

    function create_empty_bin($project_id, $samples) {
        $this->db->insert("Bin", array("name" => EMPTY_BIN
            , "project_id" => $project_id));
        $empty_bin_id = $this->db->insert_id();
        foreach ($samples as $id) {
            $this->db->insert("Sample_Bin", array("sample_id" => $id
                , "bin_id" => $empty_bin_id
                , "norm_counts" => 0.0));
        }
        return $empty_bin_id;
    }

    function insert_bins($project_id, $samples, $bins) {
        $this->db->trans_start();
        foreach ($bins as $bin) {
            $bin_arr = array(
                "name" => $bin->name
                , "method" => $bin->method
                , "taxonomy" => $bin->taxonomy
                , "size" => $bin->size
                , "contig_num" => $bin->contig_num
                , "gc_per" => $bin->gc_per
                , "chimerism" => $bin->chimerism
                , "completeness" => $bin->completeness
                , "contamination" => $bin->contamination
                , "strain_het" => $bin->strain_het
                , "project_id" => $project_id
            );
            $this->db->insert("Bin", $bin_arr);
            // Insert bin_sample
            $bin_id = $this->db->insert_id();
            foreach ($bin->abundances as $sample => $abundance) {
                $this->db->insert("Sample_Bin", array("sample_id" => $samples[$sample]
                    , "bin_id" => $bin_id
                    , "norm_counts" => $abundance["norm"]
                    , "coverage" => $abundance["cover"]));
            }
        }
        $this->db->trans_complete();
    }

    function get_bin_id($project_id, $name) {
        $result = null;
        $query = $this->db->query('SELECT bi.id as id
            FROM Bin bi 
            WHERE bi.project_id=? AND bi.name=?
            LIMIT 1', array($project_id, $name));
        if ($query->num_rows() > 0) {
            $aux = $query->result();
            $result = $aux[0]->id;
        } else {
            $result = FALSE;
        }

        return $result;
    }

    function insert_contigs($project_id, $samples, $contigs, $empty_bin_id) {
        $this->db->trans_start();
        $this->db->query('SET foreign_key_checks = 0');
        foreach ($contigs as $contig) {
            $cont_array = array(
                "name" => $contig->name
                , "taxonomy" => $contig->taxonomy
                , "genes_num" => $contig->genes_num
                , "size" => $contig->size
                , "chimerism" => $contig->chimerism
                , "gc_per" => $contig->gc_per
                , "project_id" => $project_id
            );
            $this->db->insert("Contig", $cont_array);
            $id = $this->db->insert_id();
            // Insert abundances
            foreach ($contig->abundances as $sample => $abundance) {
                $this->db->insert("Sample_Contig", ["sample_id" => $samples[$sample]
                    , "contig_id" => $id
                    , "norm_counts" => $abundance["norm"]
                    , "coverage" => $abundance["cover"]]);
            }
            // Insert bin_contig realtionship
            if (!isset($contig->bins) || sizeof($contig->bins) == 0) {
                $this->db->insert("Bin_Contig", array("Bin_ID" => $empty_bin_id, "Contig_ID" => $id));
            } else {
                $this->db->cache_on();

                foreach ($contig->bins as $bin => $method) {
                    $bin_id = $this->get_bin_id($project_id, $bin);
                    if ($bin_id === FALSE) {
                        return FALSE;
                    }
                    $bc = array("Bin_ID" => $bin_id,
                        "Contig_ID" => $id,
                        "Method" => $method);
                    $this->db->insert("Bin_Contig", $bc);
                }
                $this->db->cache_off();
            }
        }
        $this->db->query('SET foreign_key_checks = 1');
        $this->db->trans_complete();
    }

    /* function enable_constraints($enable) {
      $this->db->query
      } */

    function insert_genes_batch($project_id, $samples, $genes) {
        $t0 = time();
        // Create the gene batch
        $gene_batch = array();
        foreach ($genes as $gene) {
            // get contig id
            $this->db->cache_on();
            $contig_id = $this->get_congig_id($project_id, $gene->contig);
            $this->db->cache_off();
            if ($contig_id === FALSE) {
                log_message("error", "Contig name not found inserting gene: {$gene->contig}");
                //return FALSE;
            } else {
                $gene_arr = array(
                    "ORF" => $gene->orf,
                    "name" => $gene->name,
                    "taxonomy" => $gene->taxonomy,
                    "gc_per" => $gene->gc_per,
                    "contig_id" => $contig_id,
                    "kegg_id" => $gene->kegg_id,
                    "kegg_function" => $gene->kegg_function,
                    "kegg_pathway" => $gene->kegg_path,
                    "cog_id" => $gene->cog_id,
                    "cog_function" => $gene->cog_function,
                    "cog_pathway" => $gene->cog_path,
                    "Pfam" => $gene->pfam,
                    "project_id" => $project_id
                );
            }
            $gene_batch[] = $gene_arr;
        }
        // Insert the batch of genes
        $n = $this->db->insert_batch("Gene", $gene_batch, NULL, sizeof($genes));
        $gene_id = $this->db->insert_id();
        // generate the batch of Sample_gene
        $sample_gene_batch = array();
        foreach ($genes as $gene) {
            foreach ($gene->abundances as $sample => $abundances) {
                $sample_gene = array(
                    "gene_id" => $gene_id,
                    "sample_id" => $samples[$sample],
                    "raw_counts" => $abundances["raw"],
                    "norm_counts" => $abundances["norm"]
                );
                $sample_gene_batch[] = $sample_gene;
            }
            $gene_id++;
        }
        $this->db->insert_batch("Sample_Gene", $sample_gene_batch);
        log_message('debug', "Inserted {$n} genes in " . (time() - $t0) . " segs");
    }

    function insert_genes($project_id, $samples, $genes) {
        $t0 = time();
        $this->db->trans_start();
        foreach ($genes as $gene) {
            // get contig id
            $contig_id = $this->get_congig_id($project_id, $gene->contig);
            if ($contig_id === FALSE) {
                log_message("error", "Contig name not found inserting gene: {$gene->contig}");
                return FALSE;
            }
            $gene_arr = array(
                "ORF" => $gene->orf,
                "name" => $gene->name,
                "taxonomy" => $gene->taxonomy,
                "gc_per" => $gene->gc_per,
                "contig_id" => $contig_id,
                "kegg_id" => $gene->kegg_id,
                "kegg_function" => $gene->kegg_function,
                "kegg_pathway" => $gene->kegg_path,
                "cog_id" => $gene->cog_id,
                "cog_function" => $gene->cog_function,
                "cog_pathway" => $gene->cog_path,
                "Pfam" => $gene->pfam,
                "project_id" => $project_id
            );
            $this->db->insert("Gene", $gene_arr);
            $gene_id = $this->db->insert_id();
            // Insert abundances in samples
            foreach ($gene->abundances as $sample => $abundances) {
                $sample_gene = array(
                    "gene_id" => $gene_id,
                    "sample_id" => $samples[$sample],
                    "raw_counts" => $abundances["raw"],
                    "norm_counts" => $abundances["norm"]
                );
                $this->db->insert("Sample_Gene", $sample_gene);
            }
        }
        $this->db->trans_complete();
        log_message('debug', "Inserted " . sizeof($genes) . " genes in " . (time() - $t0) . " segs");
    }

    function get_congig_id($project_id, $contig_name) {
        $result = null;
        $query = $this->db->query('SELECT con.id as id 
            FROM Contig con WHERE con.project_id=? and con.name=? 
            LIMIT 1', array($project_id, $contig_name));
        if ($query->num_rows() > 0) {
            $aux = $query->result();
            $result = $aux[0]->id;
        } else {
            $result = FALSE;
        }

        return $result;
    }

    function assign_project_users($project_id, $users) {
        foreach ($users as $user) {
            $proj_user = array("project_id" => $project_id, "game_user_id" => $user);
            $this->db->insert("project_user", $proj_user);
        }
    }

    function update_project_users($project_id, $users) {
        $query = $this->db->query("DELETE FROM project_user WHERE project_id=?", array($project_id));
        $this->assign_project_users($project_id, $users);
    }

    function delete_project($project_id) {
        $t0 = time();
        $this->delete_sequences($project_id);
        $t1 = time();
        log_message('debug', "::::::> Deleted sequences in " . ($t1 - $t0) . " segs");
        $t0 = $t1;
        $this->db->query("DELETE Sample_Gene
            FROM Sample_Gene INNER JOIN Sample
            WHERE Sample_Gene.sample_id=Sample.id and Sample.project_id=?", array($project_id));
        $t1 = time();
        log_message('debug', "::::::> Deleted sample_gene in " . ($t1 - $t0) . " segs");
        $t0 = $t1;
        $this->db->query("DELETE FROM Gene 
            WHERE Gene.project_id=?", array($project_id));
        $t1 = time();
        log_message('debug', "::::::> Deleted gene in " . ($t1 - $t0) . " segs");
        $t0 = $t1;
        // bin_contig
        $this->db->query("DELETE Bin_Contig
            FROM Sample INNER JOIN Sample_Contig INNER JOIN Bin_Contig 
            WHERE Bin_Contig.contig_id=Sample_Contig.contig_id 
            AND Sample_Contig.sample_id=Sample.id and Sample.project_id=?", array($project_id));
        $t1 = time();
        log_message('debug', "::::::> Deleted Bin_contig in " . ($t1 - $t0) . " segs");
        $t0 = $t1;
        // delete contigs, sample_contig
        $this->db->query("DELETE Sample_Contig
            FROM Sample_Contig INNER JOIN Sample
            WHERE Sample_Contig.sample_id=Sample.ID
            and Sample.project_id=?", array($project_id));
        $t1 = time();
        log_message('debug', "::::::> Deleted Sample_contig in " . ($t1 - $t0) . " segs");
        $t0 = $t1;
        $this->db->query("DELETE FROM Contig WHERE project_id=?", array($project_id));
        $t1 = time();
        log_message('debug', "::::::> Deleted contig in " . ($t1 - $t0) . " segs");
        $t0 = $t1;
        // delete sample_bin, bin
        $this->db->query("DELETE Sample_Bin
            FROM Sample_Bin INNER JOIN Sample
            WHERE Sample_Bin.sample_id=Sample.id and Sample.project_id=?", array($project_id));
        $t1 = time();
        log_message('debug', "::::::> Deleted sample_bin in " . ($t1 - $t0) . " segs");
        $t0 = $t1;
        $this->db->query("DELETE FROM Bin WHERE project_id=?", array($project_id));
        $t1 = time();
        log_message('debug', "::::::> Deleted Bin in " . ($t1 - $t0) . " segs");
        $t0 = $t1;

        // delete sample_properties, samples
        $this->db->query("DELETE Sample_properties, Sample
            FROM Sample_properties INNER JOIN Sample
            WHERE Sample.id=Sample_properties.sample_id AND Sample.project_id=?", array($project_id));

        // delete project_users
        $this->db->query("DELETE FROM project_user WHERE project_user.project_Id=?", array($project_id));

        $this->db->query("DELETE FROM Project WHERE Id=?", array($project_id));
    }

    function get_samples($pid) {
        $query = $this->db->query('SELECT sa.*, pr.property, pr.value FROM Sample sa LEFT JOIN Sample_properties pr ON (sa.id=pr.Sample_ID) '
                . 'WHERE Project_ID=? ORDER BY sa.id', array($pid));
        $samples = array();
        if ($query->num_rows() > 0) {
            $id = -1;
            $sample = NULL;
            foreach ($query->result() as $row) {
                if ($id != $row->ID) {
                    $sample = new Sample();
                    $id = $row->ID;
                    $sample->id = $id;
                    $sample->name = $row->name;
                    $sample->properties = array();
                    $samples[] = $sample;
                }
                $sample->properties[$row->property] = $row->value;
            }
        }
        return $samples;
    }

    function get_project($id) {
        $this->load->library("entities/Game_Project");
        $query = $this->db->query("SELECT * FROM Project WHERE ID=?", array($id));

        $result = NULL;
        if ($query->num_rows() > 0) {
            $aux = $query->result("Game_Project");
            $result = $aux[0];
        }
        return $result;
    }

    function get_project_users($id) {
        $query = $this->db->query("SELECT * FROM project_user WHERE project_id=?", array($id));

        $result = NULL;
        if ($query->num_rows() > 0) {
            $result = $query->result_array();
        }
        return $result;
    }

    function search_query($sql) {
        $query = $this->db->query($sql);

        return $query->result_array();
    }

    function has_sequences($project_id) {
        $query = $this->db->query("SELECT se.ID
                from Sample sam JOIN Sample_Gene sg ON (sam.id=sg.Sample_id)
                JOIN Gene ge ON (sg.Gene_id=ge.id)
                JOIN Sequence se ON (se.Gene_id=ge.id)
                WHERE sam.project_id=? LIMIT 1;", array($project_id));

        return ($query->num_rows() > 0);
    }

    function insert_sequences_batch($project_id, $seqs) {
        // Create the gene batch
        $seqs_batch = array();
        foreach ($seqs as $seq) {
            // get contig id
            $this->db->cache_on();
            $gene_id = $this->get_gene_id($project_id, $seq["gene"]);
            $this->db->cache_off();
            if ($gene_id === FALSE) {
                log_message("error", "Gene name not found inserting sequece of gene: {$seq['gene']}");
            } else {
                $seq_arr = array(
                    "Gene_ID" => $gene_id,
                    "sequence" => $seq["sequence"]);
                $seqs_batch[] = $seq_arr;
            }
        }
        // Insert the batch of genes
        $n = $this->db->insert_batch("Sequence", $seqs_batch, NULL, sizeof($seqs));
    }

    function get_gene_id($project_id, $gene_orf) {
        $result = null;
        $query = $this->db->query('SELECT ge.ID
            FROM Sample sam JOIN Sample_Gene sg ON (sam.id=sg.Sample_Id)
            JOIN Gene ge ON (ge.id=sg.Gene_id)
            WHERE sam.project_id=? AND ge.ORF=? 
            LIMIT 1', array($project_id, $gene_orf));
        if ($query->num_rows() > 0) {
            $aux = $query->result();
            $result = $aux[0]->ID;
        } else {
            $result = FALSE;
        }
        return $result;
    }

    function delete_sequences($project_id) {
        $query = $this->db->query("DELETE FROM Sequence WHERE gene_id IN (
	SELECT ge.ID
	FROM Sample sam JOIN Sample_Gene sg ON (sam.id=sg.Sample_Id)
	JOIN Gene ge ON (ge.id=sg.Gene_id)
	WHERE sam.project_id=?)", array($project_id));
    }

}
