<div class="tablebg">
    <h4>Products (<?=sizeof($vars['products']);?>)</h4>
    <table style="text-align: center;" id="sortabletbl1" class="datatable licenses" width="100%">
        <tbody>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Setupfee</th>
            <th>Monthly</th>
            <th>Quarterly</th>
            <th>Semi-annually</th>
            <th>Annually</th>

        </tr>
        <?php foreach($vars['products'] as $product):?>
            <tr>
                <td><?=$product->id;?></td>
                <td><?=$product->fullName;?></td>
                <td>$<?=$product->priceWithDiscount('setupfee');?></td>
                <td>$<?=$product->priceWithDiscount('monthly');?></td>
                <td>$<?=$product->priceWithDiscount('quarterly');?></td>
                <td>$<?=$product->priceWithDiscount('semiannually');?></td>
                <td>$<?=$product->priceWithDiscount('annually');?></td>
            </tr>
        <?php endforeach;?>
        </tbody>
    </table>
</div>