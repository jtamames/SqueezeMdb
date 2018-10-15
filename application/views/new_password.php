<h2>Reset password</h2>
<?php
$error = validation_errors();
if (isset($error) && strlen($error) > 0) {?>
<h3>Errors:</h3>
<div>
    <?= $error?>
</div>
<?php } ?>
<form name="miform" action="<?= site_url('Reset_password/validate/'.$val_code) ?>" method="POST">
    <input type="hidden" name="val_code" value="<?=$val_code?>" />
    <input type="hidden" name="user_id" value="<?=$user_id?>" />
    <label for="password">Password:</label>
    <input type="password" name="password" /><br />
    <label for="passconf">Repeat password:</label>
    <input type="password" name="passconf" /><br />
    <input type="submit" name="submit" id="submit" />    
</form>

