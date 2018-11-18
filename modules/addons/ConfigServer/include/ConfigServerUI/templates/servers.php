<div class="tablebg">
    <h4>Accounts</h4>
    <table style="text-align: center" id="sortabletbl1" class="datatable" width="100%">
        <tbody>
        <tr>
            <th>ID</th>
            <th>Email</th>
            <th>Total licenses</th>
            <th>Credit</th>
            <th>Partner level</th>
            <th>Discount</th>
            <th>Actions</th>
        </tr>
        <?php foreach($vars['servers'] as $server):?>
            <tr>
                <td><?=$server->id;?></td>
                <td><?=$server->email;?></td>
                <td><?=$server->total_licenses;?></td>
                <td><?=$server->credit;?>$</td>
                <td><?=$server->partnerLevel;?></td>
                <td><?=$server->discount;?>%</td>
                <td><a href="addonmodules.php?module=ConfigServer&serverId=<?=$server->id;?>">» Choose account</a></td>
            </tr>
        <?php endforeach;?>
        </tbody>
    </table>
</div>
<?php require(__DIR__ . "/copyright.php"); ?>