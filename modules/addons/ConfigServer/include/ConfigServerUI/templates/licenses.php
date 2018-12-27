<a href="addonmodules.php?module=ConfigServer">« Back to accounts</a>
<br>
<br>
<ul class="nav nav-tabs admin-tabs" role="tablist">
<li class="<?=($vars['activeTab'] == 'search') ? 'active' : '';?>">
    <a class="tab-top" href="#search" role="tab" data-toggle="tab" id="tabLink1" data-tab-id="1" aria-expanded="true">Search/Filter</a>
</li>
<li class="<?=($vars['activeTab'] == 'products') ? 'active' : '';?>">
    <a class="tab-top" href="#products" role="tab" data-toggle="tab" id="tabLink1" data-tab-id="1" aria-expanded="true">Products</a>
</li>
<li class="<?=($vars['activeTab'] == 'addProducts') ? 'active' : '';?>">
    <a class="tab-top" href="#addProducts" onclick="window.location.href='addonmodules.php?module=ConfigServer&serverId=<?=$vars['serverId'];?>&addProducts=1';" role="tab" data-toggle="tab" id="tabLink1" data-tab-id="1" aria-expanded="true">Add Products</a>
</li>
</ul>
<div class="tab-content admin-tabs">
  <div class="tab-pane <?=($vars['activeTab'] == 'search') ? 'active' : '';?>" id="search">
       <form action="addonmodules.php?module=ConfigServer&serverId=<?=$vars['serverId'];?>&search=1" method="post" _lpchecked="1">
        <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
            <tbody>
            <tr>
                <td width="200" class="fieldlabel">IP address</td>
                <td class="fieldarea">
                    <input type="text" name="ip" size="40" value="<?=$vars['criteria']['ip'];?>" class="form-control">
                </td>
            </tr>
            <tr>
                <td class="fieldlabel">Status</td>
                <td class="fieldarea">
                    <select id="multi-view" name="status[]" class="form-control selectize-multi-select" multiple="multiple" data-value-field="id" placeholder="Any Status">
                        <option value="active" <?=(in_array('active', $vars['criteria']['status'])) ? 'selected' : '';?>>Active</option>
                        <option value="suspended" <?=(in_array('suspended', $vars['criteria']['status'])) ? 'selected' : '';?>>Suspended</option>
                        <option value="expired" <?=(in_array('expired', $vars['criteria']['status'])) ? 'selected' : '';?>>Expired</option>
                    </select>
                </td>
            </tr>
        </tbody></table>
        <div class="btn-container">
            <input type="submit" value="Search/Filter" class="btn btn-primary">
        </div>
        </form>
  </div>
  <div class="tab-pane <?=(($vars['activeTab'] == 'products') ? 'active' : '');?>" id="products">
       <?php require(__DIR__ . "/products.php"); ?>
  </div>
  <div class="tab-pane <?=(($vars['activeTab'] == 'addProducts') ? 'active' : '');?>" id="addProducts">
        <?=$vars['addProducts'];?>
  </div>
</div>
<br>
<?php require(__DIR__ . "/header.php"); ?>
<div class="tablebg">
    <h4>Licenses (<?=$vars['information']->total_licenses;?>)</h4>
    <?php if(isset($vars['success']) && !empty($vars['success'])):?>
        <div class="successbox">
            <strong><span class="title">Changes Saved Successfully!</span></strong>
            <br>
            <?=$vars['success'];?>
        </div><br>
    <?php endif;?>
    <?php if(isset($vars['error']) && !empty($vars['error'])):?>
        <div class="errorbox"><strong><span class="title">Error</span></strong><br><?=$vars['error'];?></div><br>
        <?php endif;?>
    <table style="text-align: center;" id="sortabletbl1" class="datatable licenses" width="100%">
        <tbody>
        <tr>
            <th style="width: 75px">ID</th>
            <th>Name</th>
            <th>IP</th>
            <th>Hostname</th>
            <th>Cost</th>
            <th>Status</th>
            <th>Due date</th>
            <th style="width: 55px">Service</th>
            <th style="width: 50px"></th>
        </tr>
        <?php foreach($vars['licenses'] as $license):?>
            <tr class="<?=$license->status;?>">
                <td><?=$license->id;?></t>
                <td class="product" title="<?=$vars['products'][$license->productId]->fullName;?>"><?=$vars['products'][$license->productId]->fullName;?></td>
                <td><?=$license->ip;?></td>
                <td><?=$license->hostname;?></td>
                <td><?=$vars['products'][$license->productId]->priceWithDiscount($license->cycle);?>$ (<?=$license->cycle;?>)</td>
                <td><?=$license->status;?></td>
                <td><?=$license->renewDate;?> (<?=$license->remainingDays();?> days)</td>
                <td><?=$license->client;?></td>
                <td style="height: 100%">
                    <a onclick="return confirm('Are you sure you want to extend this license?');" href="addonmodules.php?module=ConfigServer&serverId=<?=$vars['serverId'];?>&extendLicense=<?=$license->id;?>&c=<?=$vars['sessionChecker'];?>">
                        <img src="images/icons/add.png" border="0" align="absmiddle">
                    </a>
                    &nbsp;
                    <a href="addonmodules.php?module=ConfigServer&serverId=<?=$vars['serverId'];?>&licenseId=<?=$license->id;?>">
                        <img src="images/edit.gif" width="16" height="16" border="0" alt="Edit">
                    </a>
                </td>
            </tr>
        <?php endforeach;?>
        </tbody>
    </table>
</div>
