<?php if(isset($vars['success'])):?>
    <div class="alert alert-success text-center"><?=$vars['success'];?></div>
<?php endif;?>
<?php if(isset($vars['error'])):?>
    <div class="alert alert-warning text-center"><?=$vars['error'];?></div>
<?php endif;?>
<div style="text-align: left; direction: ltr">
    <h2>Details</h2>
    <ul>
        <li>License ID: <b><?=$vars['license']->id;?></b></li>
        <li>IP address: <b><?=$vars['license']->ip;?></b></li>
        <li>Hostname: <b><?=$vars['license']->hostname;?></b></li>
        <li>Kernel: <b><?=$vars['license']->kernel;?></b></li>
        <li>License key: <b><?=$vars['license']->licenseKey;?></b></li>
    </ul>
</div>
<?php if($vars['license']->status == "suspended"):?>
    <br>
    <div class="alert alert-warning text-center">
        Your license is suspended.
        <?php if(!empty($vars['license']->suspendedReason)):?>
            Reason: <?=$vars['license']->suspendedReason;?>
        <?php endif;?>
    </div>
<?php elseif ($vars['license']->status == 'expired'):?>
    <br>
    <div class="alert alert-warning text-center">
        Your license is expired.
    </div>
<?php else:?>
<?php if($vars['license']->status == "active" && ($vars['allowChangeIP'] and $vars['license']->changeIP < 3)):?>
    <hr>
    <div style="text-align: left">
        <h2>Settings</h2>
        <?php if($vars['allowChangeIP'] and $vars['license']->changeIP < 3):?>
            <div class="row domains-row">
            <form method="post" action="clientarea.php">
                <input type="hidden" name="id" value="<?=$vars['serviceId'];?>">
                <input type="hidden" name="action" value="productdetails">
                <input type="hidden" name="modop" value="custom">
                <input type="hidden" name="a" value="changeIP">
                <div class="col-xs-9">
                    <div class="input-group">
                        <span class="input-group-addon">New IP address:</span>
                        <input name="newIP" class="form-control" placeholder="192.168.1.1">
                    </div>
                </div>
                <div class="col-xs-3">
                    <button type="submit" id="btnCompleteProductConfig" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
        <?php endif;?>
    </div>
<?php endif;?>
<hr>
<div style="text-align: left">
    <h2>Installation</h2>
    <hr/>
    <?php foreach($vars['installationHelp'] as $item):?>
        <h4><?=$vars->os;?></h4>
        <div class="message markdown-content">
        <pre><code><?=$item->commands;?></code></pre>
        </div>
    <?php endforeach;?>
</div>
<?php endif;?>