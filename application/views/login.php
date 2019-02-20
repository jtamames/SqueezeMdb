<?php
$this->load->helper('url');
$ci = get_instance();
?>
<div class="row">
    <div class="col-md-2 col-md-offset-2">
        <img src="<?php echo $ci->config->base_url() ?>/resources/images/logo_lemonM.jpg" alt="SqueezeM" width="150px"/>
    </div>
    <div class="col-md-1 col-md-offset-1" id="logo">
        <h2>SqueezeM</h2>
    </div>
</div>

<?php 
$error = validation_errors();
if (isset($error) && strlen($error) > 0) { ?>
    <div class="row">
        <div class="col-md-offset-3 col-md-6">
            <h3>Errors:</h3>
            <div class="alert alert-danger">
                <?= $error ?>
            </div>
        </div>
    </div>
<?php } ?>
<form name="miform" action="<?= site_url('login') ?>" method="POST" class="form-horizontal">
    <fieldset>
        <div class="row" id="login_box">
            <div class="col-md-6 col-md-offset-3" id="login_fields">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="text" name="email" class="form-control"/>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" name="password" class="form-control"/>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-2 col-md-offset-5">
                <div class="form-group">
                    <input type="submit" name="submit" id="submit" class="btn btn-primary"/>
                    <a href="<?= site_url('reset_password') ?>">Forgot password?</a>
                </div>
            </div>
        </div>
    </fieldset>
</form>
