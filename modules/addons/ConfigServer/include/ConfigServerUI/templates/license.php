<a href="addonmodules.php?module=ConfigServer&serverId=<?=$vars['serverId'];?>">« Back to licenses</a>
<br>
<br>
<ul class="nav nav-tabs admin-tabs" role="tablist">
    <li class="dropdown pull-right tabdrop hide"><a class="dropdown-toggle" data-toggle="dropdown" href="#" aria-expanded="false"><i class="icon-align-justify"></i> <b class="caret"></b></a><ul class="dropdown-menu"></ul></li>
    <li class="<?=($vars['activeTab'] == 'details') ? 'active' : '';?>">
        <a class="tab-top" href="#details" role="tab" data-toggle="tab" id="tabLink1" data-tab-id="1" aria-expanded="true">License #<?=$vars['license']->id;?></a>
    </li>
</ul>
<div class="tab-content admin-tabs">
  <div class="tab-pane <?=($vars['activeTab'] == 'details') ? 'active' : '';?>" id="details">
    <?php if(isset($vars['success']) && !empty($vars['success'])):?>
            <div class="successbox">
                <strong><span class="title">Changes Saved Successfully!</span></strong>
                <br>
                <?=$vars['success'];?>
            </div>
        <?php endif;?>
        <?php if(isset($vars['error']) && !empty($vars['suerrorccess'])):?>
            <div class="errorbox"><strong><span class="title">Error</span></strong><br><?=$vars['error'];?></div>
            <?php endif;?>
            <div class="row client-summary-panels">
            <div class="col-lg-3 col-sm-6">
                <div class="clientssummarybox">
                        <div class="title">License Information</div>
                        <table class="clientssummarystats license <?=$vars['license']->status;?>" cellspacing="0" cellpadding="2">
                            <tbody>
                            <tr>
                                <td>License ID</td>
                                <td><b><?=$vars['license']->id;?></b></td>
                            </tr>
                            <tr>
                                <td>Product</td>
                                <td><b><?=$vars['license']->product()->fullName;?></b></td>
                            </tr>
                            <tr>
                                <td>IP address</td>
                                <td><b><?=$vars['license']->ip;?></b></td>
                            </tr>
                            <tr>
                                <td>Hostname</td>
                                <td><b><?=$vars['license']->hostname;?></b></td>
                            </tr>
                            <tr>
                                <td>Kernel</td>
                                <td><b><?=$vars['license']->kernel;?></b></td>
                            </tr>
                            <tr>
                                <td>License Key</td>
                                <td><b><?=$vars['license']->licenseKey;?></b></td>
                            </tr>
                            <tr >
                                <td>Status</td>
                                <td><b><?=ucfirst($vars['license']->status);?></b></td>
                            </tr>
                            <?php if ($vars['license']->status == 'suspended' && !empty($vars['license']->suspendedReason)) :?>
                                <tr >
                                <td>Suspend Reason</td>
                                <td><b><?=$vars['license']->suspendedReason;?></b></td>
                            </tr>
                            <?php endif;?>
                            <tr>
                                <td>Renew date</td>
                                <td><b><?=$vars['license']->renewDate;?> (<?=$vars['license']->remainingDays();?> days)</b></td>
                            </tr>
                            <tr>
                                <td>Change IP</td>
                                <td><b><?=$vars['license']->changeIP;?>/3</b></td>
                            </tr>
                            <tr>
                                <td>Cost</td>
                                <td><b>$<?=$vars['license']->product()->priceWithDiscount($vars['license']->cycle);?> (<?=$vars['license']->cycle;?>)</b></td>
                            </tr>
                        </tbody>
                        </table>
                        <ul>
                            <li><a href="addonmodules.php?module=ConfigServer&serverId=<?=$vars['serverId'];?>&licenseId=<?=$vars['license']->id;?>&c=<?=$vars['sessionChecker'];?>&extend=1"><img src="images/icons/add.png" border="0" align="absmiddle"> Extend license</a></li>
                        </ul>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="clientssummarybox">
                    <div class="title">Billing cycles</div>
                    <table class="clientssummarystats" cellspacing="0" cellpadding="2">
                        <tbody>
                            <?php foreach ($vars['license']->product()->cost as $key => $item):?>
                            <tr>
                                <td width="40%"><?=ucfirst($key);?></td>
                                <td>
                                    $<?=$vars['license']->product()->priceWithDiscount($key);?> 
                                </td>
                                <td>
                                    <?=number_format($vars['information']->exchangeRateToman*$vars['license']->product()->priceWithDiscount($key));?> Toman
                                </td>
                            </tr>
                            <?php endforeach;?>
                        </tbody>
                    </table>
                </div>
                <div class="clientssummarybox">
                    <div class="title">Settings</div>
                    <form method="post" action="addonmodules.php?module=ConfigServer&serverId=<?=$vars['serverId'];?>&licenseId=<?=$vars['license']->id;?>&c=<?=$vars['sessionChecker'];?>">
                        <input type="hidden" name="action" value="changeSettings">
                         <table class="clientssummarystats">
                            <tbody>
                            <tr>
                                <td class="fieldlabel" style="font-size: 13px">Auto renew</td>
                                <td class="fieldarea">
                                    <select name="setAutoRenew" class="form-control">
                                        <option value="on" <?=($vars['license']->autoRenew) ? 'selected' : '';?>>On</option>
                                        <option value="off" <?=(!$vars['license']->autoRenew) ? 'selected' : '';?>>Off</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="fieldlabel" style="font-size: 13px">Status</td>
                                <td class="fieldarea">
                                    <select name="setStatus" class="form-control">
                                        <option value="active" <?=($vars['license']->status == 'active') ? 'selected' : '';?>>Active</option>
                                        <option value="suspended" <?=($vars['license']->status == 'suspended') ? 'selected' : '';?>>Suspended</option>
                                        <option value="expired" <?=($vars['license']->status == 'expired') ? 'selected' : '';?>>Expired</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="fieldlabel" style="font-size: 13px">Billing cycle</td>
                                <td class="fieldarea">
                                    <select name="setBillingCycle" class="form-control">
                                        <option value="monthly" <?=($vars['license']->cycle == 'monthly') ? 'selected' : '';?>>Monthly</option>
                                        <option value="quarterly" <?=($vars['license']->cycle == 'quarterly') ? 'selected' : '';?>>Quarterly</option>
                                        <option value="semiannually" <?=($vars['license']->cycle == 'semiannually') ? 'selected' : '';?>>Semi-annually</option>
                                        <option value="annually" <?=($vars['license']->cycle == 'annually') ? 'selected' : '';?>>Annually</option>
                                    </select>
                                </td>
                            </tr>
                    </tbody>
                    </table>
                    <div class="btn-container">
                        <input type="submit" value="Save" class="btn btn-primary">
                    </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                 <div class="clientssummarybox">
                    <div class="title">Change IP (<?=$vars['license']->changeIP;?>/3)</div>
                    <form method="post" action="addonmodules.php?module=ConfigServer&serverId=<?=$vars['serverId'];?>&licenseId=<?=$vars['license']->id;?>&c=<?=$vars['sessionChecker'];?>">
                        <input type="hidden" name="action" value="changeIP">
                        <div style="font-size: 11px; color: #f48042; font-weight: bold;">
                            Note: Changing IP is free for first 3 times.
                            After that you'll be charged 2.0$ each time you change the IP address.
                        </div>
                        <br>
                        <div align="center">
                            <input name="newIP" class="form-control bottom-margin-10" placeholder="New IP address">
                            <input type="submit" value="<?=($vars['license']->changeIP < 3) ? 'Change' : 'Change with 2.0$';?>" class="button btn btn-default">
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="clientssummarybox">
                    <div class="title">Notes</div>
                    <form method="post" action="addonmodules.php?module=ConfigServer&serverId=<?=$vars['serverId'];?>&licenseId=<?=$vars['license']->id;?>&c=<?=$vars['sessionChecker'];?>">
                        <input type="hidden" name="action" value="updateNote">
                        <div align="center">
                            <textarea name="notes" rows="4" class="form-control bottom-margin-5"><?=$vars['license']->notes;?></textarea>
                            <input type="submit" value="Save" class="button btn btn-default">
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div style="text-align: left">
            <h1>Installation</h1>
            <hr/>
            <?php foreach($vars['installationHelp'] as $item):?>
                <h4><?=$item->os;?></h4>
                <div class="message markdown-content">
                <pre><code><?=$item->commands;?></code></pre>
                </div>
            <?php endforeach;?>
        </div>
  </div>
</div>
<?php require(__DIR__ . "/copyright.php"); ?>