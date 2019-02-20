<?php
$ci = get_instance();
$search_model = $ci->session->userdata('search_model');
$tables = array_keys($search_model);
$search_data = $ci->session->userdata('search_data');

// Transform selected cols arrays into associative arrays
$bin_cols = array();
$contig_cols = array();
$gene_cols = array();

if (isset($search_data)) {
    if (isset($search_data["bin_cols"])) {
        foreach ($search_data["bin_cols"] as $b_col) {
            $bin_cols[$b_col] = TRUE;
        }
    }
    if (isset($search_data["contig_cols"])) {
        foreach ($search_data["contig_cols"] as $c_col) {
            $contig_cols[$c_col] = TRUE;
        }
    }
    if (isset($search_data["gene_cols"])) {
        foreach ($search_data["gene_cols"] as $g_col) {
            $gene_cols[$g_col] = TRUE;
        }
    }
}
?>
<script type="text/javascript">
    function initializeSelect(n) {
        $("#table_" + n).empty();
        var tab = "<option value=''></option> ";
<?php foreach ($tables as $table) { ?>
            tab += "<option value='<?= $table ?>'><?= $table ?></option> ";
<?php } ?>
        $("#table_" + n).html(tab);
    }

    jQuery(document).ready(function () {
        $("#back").click(function (event) {
            document.location.href = "<?php echo site_url("admin/Home") ?>";
            event.preventDefault();
        });
        $("#table_1").change(function (event) {
            id = $(this).attr("id");
            num = id.substring(id.indexOf("_") + 1, id.length);
            table = $(this).val();
            console.log("id: " + id + " table: " + table + " Num: " + num);

            getTableFields(num, table);
        });
        $("#field_1").change(function (event) {
            id = $(this).attr("id");
            num = id.substring(id.indexOf("_") + 1, id.length);
            table = $("#table_" + num).val();
            field = $(this).val();
            getOperators(num, table, field);
        });
        $("#add_clause_2").click(function (event) {
            addSearchClause(2);
            event.preventDefault();
            return;
        });
        $("#search_form").submit(function (event) {
            $("#loading_message").addClass("show").removeClass("hidden");
            return;
        });

    <?php if (isset($search_data["clauses"])) {
        $clauses = $search_data["clauses"];
        // In case there is a existing query we load the data
        for ($i = 0; $i < sizeof($clauses); $i++) {
            if ($i == 0) { ?>
               getTableFields(1,'<?=$clauses[$i]["table"]?>', '<?=$clauses[$i]["field"]?>');
               getOperators(1, '<?=$clauses[$i]["table"]?>', '<?=$clauses[$i]["field"]?>', '<?=$clauses[$i]["op"]?>');
               $("#table_1").val("<?=$clauses[$i]["table"]?>");
               $("#value_1").val("<?=$clauses[$i]["value"]?>");
            <?php }
            else { ?>
                addSearchClause();
               getTableFields(<?=($i+1)?>,'<?=$clauses[$i]["table"]?>', '<?=$clauses[$i]["field"]?>');
               getOperators(<?=($i+1)?>, '<?=$clauses[$i]["table"]?>', '<?=$clauses[$i]["field"]?>', '<?=$clauses[$i]["op"]?>'); 
               $("#table_<?=($i+1)?>").val("<?=$clauses[$i]["table"]?>");
               $("#value_<?=($i+1)?>").val("<?=$clauses[$i]["value"]?>");
            <?php }
        }
    } ?>
    });
</script>


<h3>Search Results of Project <?= $project->name ?></h3>
<?php if (isset($error) && strlen($error) > 0) { ?>
    <div class="row">
        <div class="col-md-offset-2 col-md-8">
            <h4>Errors:</h4>
            <div class="alert alert-danger">
                <?= $error ?>
            </div>
        </div>
    </div>
<?php } ?>

<form id="search_form" name="search_form" action="<?= site_url("Projects/search/{$id}") ?>" method="POST">
    <div class="row">
        <div class="col-md-offset-1 col-md-11 section">
            <h4>Select the columns</h4>
        </div>
    </div>
    <div id="col_sel" class="row">
        <!--
        <div id="sample_cols" class="col-md-2 col-md-offset-2">
            <h4>Sample</h4>
            <div class="checkbox">
                <label><input type="checkbox" name="sample[]" value="samp_name"> Name</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="sample[]" value="samp_location"> Location</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="sample[]" value="samp_longitude"> Longitude</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="sample[]" value="samp_latitude"> Latitude</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="sample[]" value="samp_altitude"> Altitude</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="sample[]" value="samp_date"> Sampling</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="sample[]" value="samp_extraction"> Extraction</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="sample[]" value="samp_seq_method"> Sequence Method</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="sample[]" value="samp_Size"> Size</label>
            </div>
        </div>
        -->
        <?php
        ?>
        <div id="bin_cols" class="col-md-offset-2 col-md-3">
            <h4>Bin</h4>
            <div class="checkbox">
                <label><input type="checkbox" name="bin[]" value="name" <?=(isset($bin_cols["name"])) ? 'checked' : '' ?>> Name</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="bin[]" value="method" <?=(isset($bin_cols["method"])) ? 'checked' : '' ?>> Method</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="bin[]" value="taxonomy" <?=(isset($bin_cols["taxonomy"])) ? 'checked' : '' ?>> Taxonomy</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="bin[]" value="size" <?=(isset($bin_cols["size"])) ? 'checked' : '' ?>> Size</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="bin[]" value="contig_num" <?=(isset($bin_cols["contig_num"])) ? 'checked' : '' ?>> Contig num</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="bin[]" value="gc_per" <?=(isset($bin_cols["gc_per"])) ? 'checked' : '' ?>> GC Percentage</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="bin[]" value="chimerism" <?=(isset($bin_cols["chimerism"])) ? 'checked' : '' ?>> Chimerism</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="bin[]" value="contamination" <?=(isset($bin_cols["contamination"])) ? 'checked' : '' ?>> Contamination</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="bin[]" value="strain_het" <?=(isset($bin_cols["strain_het"])) ? 'checked' : '' ?>> Strain Het</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="bin[]" value="norm_counts" <?=(isset($bin_cols["norm_counts"])) ? 'checked' : '' ?>> Norm. counts</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="bin[]" value="coverage" <?=(isset($bin_cols["coverage"])) ? 'checked' : '' ?>>Coverage</label>
            </div>
        </div>
        <div id="cont_cols" class="col-md-3">
            <h4>Contig</h4>
            <div class="checkbox">
                <label><input type="checkbox" name="contig[]" value="name" <?=(isset($contig_cols["name"])) ? 'checked' : '' ?>> Name</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="contig[]" value="taxonomy" <?=(isset($contig_cols["taxonomy"])) ? 'checked' : '' ?>> Taxonomy</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="contig[]" value="size" <?=(isset($contig_cols["size"])) ? 'checked' : '' ?>> Size</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="contig[]" value="genes_num" <?=(isset($contig_cols["genes_num"])) ? 'checked' : '' ?>> Genes number</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="contig[]" value="gc_per" <?=(isset($contig_cols["gc_per"])) ? 'checked' : '' ?>> GC Percentage</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="contig[]" value="chimerism" <?=(isset($contig_cols["chimerism"])) ? 'checked' : '' ?>> Chimerism</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="contig[]" value="norm_counts" <?=(isset($contig_cols["norm_counts"])) ? 'checked' : '' ?>> Norm. counts</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="contig[]" value="coverage" <?=(isset($contig_cols["coverage"])) ? 'checked' : '' ?>>Coverage</label>
            </div>
        </div>
        <div id="gene_cols" class="col-md-3">
            <h4>Gene</h4>
            <div class="checkbox">
                <label><input type="checkbox" name="gene[]" value="ORF" <?=(isset($gene_cols["ORF"])) ? 'checked' : '' ?>> ORF</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="gene[]" value="name" <?=(isset($gene_cols["name"])) ? 'checked' : '' ?>> Name</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="gene[]" value="taxonomy" <?=(isset($gene_cols["taxonomy"])) ? 'checked' : '' ?>> Taxonomy</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="gene[]" value="gc_per" <?=(isset($gene_cols["gc_per"])) ? 'checked' : '' ?>> GC Percentage</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="gene[]" value="sequence" <?=(isset($gene_cols["sequence"])) ? 'checked' : '' ?>> Sequence</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="gene[]" value="kegg_id" <?=(isset($gene_cols["kegg_id"])) ? 'checked' : '' ?>> KEGG ID</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="gene[]" value="kegg_function" <?=(isset($gene_cols["kegg_function"])) ? 'checked' : '' ?>> KEGG Function</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="gene[]" value="kegg_pathway" <?=(isset($gene_cols["kegg_pathway"])) ? 'checked' : '' ?>> KEGG Pathway</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="gene[]" value="cog_id" <?=(isset($gene_cols["cog_id"])) ? 'checked' : '' ?>> Cog ID</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="gene[]" value="cog_function" <?=(isset($gene_cols["cog_function"])) ? 'checked' : '' ?>> Cog Function</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="gene[]" value="cog_pathway" <?=(isset($gene_cols["cog_pathway"])) ? 'checked' : '' ?>> Cog Pathway</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="gene[]" value="Pfam" <?=(isset($gene_cols["Pfam"])) ? 'checked' : '' ?>> Pfam</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="gene[]" value="norm_counts" <?=(isset($gene_cols["norm_counts"])) ? 'checked' : '' ?>> Norm. counts</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" name="gene[]" value="raw_counts" <?=(isset($gene_cols["raw_counts"])) ? 'checked' : '' ?>>Raw counts</label>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-offset-1 col-md-11 section">
            <h4>Search criteria &nbsp;&nbsp;
                <small><select id="logical_oper" name="logical_oper" >
                        <option value="AND" <?=((isset($operator) && $operator == "AND")?"selected":"")?>>AND</option>
                        <option value="OR" <?=((isset($operator) && $operator == "OR")?"selected":"")?>>OR</option>
                    </select></small>
            </h4>
        </div>
    </div>
    <div id="search_clause_1" class="row search_clause">
        <div class="col-md-2 col-md-offset-2">
            <select id="table_1" name="table_1" class="form-control">
                <option value=""></option>
                <?php foreach ($tables as $table) { ?>
                    <option value="<?= $table ?>"><?= $table ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-md-2">
            <select id="field_1" name="field_1" class="form-control">
            </select>
        </div>
        <div class="col-md-2">
            <select id="oper_1" name="oper_1" class="form-control"></select>
        </div>
        <div class="col-md-2">
            <input type="text" name="value_1" id="value_1" class="form-control"/>
        </div>
    </div>
    <div class="row">
        <div class="col-md-2 col-md-offset-2">
            <button id="add_clause_2" class="btn btn-default">Add more criteria</button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-1 col-md-offset-5">
            <button type="submit" value="Search" class="btn btn-primary btn-lg" id="submit">Search</button>
        </div>
        <div class="col-md-1 ">
            <button class="btn btn-default btn-lg">Back</button>
        </div>

    </div>
</form>
<div class="floating_message hidden" id="loading_message">
    <div id="spinner" class="center-block">
        <img src="<?php echo $ci->config->base_url()?>/resources/images/spinner_game.gif" width="95" height="95"/>
        <div style="margin: 8px;"><h4>Searching data...</h4></div>
    </div>
</div>