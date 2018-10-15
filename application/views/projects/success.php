<script type="text/javascript">
    jQuery(document).ready(function () {
	$("#back").click(function(event){document.location.href = "<?php echo site_url("admin/Projects") ?>";event.preventDefault();});
    });
</script>
<h2>Project created successfuly</h2>
<div>
    <h4>Inserted...</h4>
    <ul>
        <li><?=$num_samples?> samples</li>
        <li><?=$num_bins?> bins</li>
        <li><?=$num_contigs?> contigs</li>
        <li><?=$num_genes?> genes</li>
    </ul>
    Total elapsed time: <?= floor($time/60)?> minutes <?= $time % 60?> seconds
    <button id="back" name="back">Back</button>
</div>