<style>
    input, select, textarea {
        font-family: Tahoma;font-size: 11px;
    }
    .configServer .head {
        padding: 10px 25px 10px 25px;
        background-color: #666;
        font-weight: bold;
        font-size: 14px;
        color: #E3F0FD;
        margin: 0 0 15px 0;
        -moz-border-radius: 5px;
        -webkit-border-radius: 5px;
        -o-border-radius: 5px;
        border-radius: 5px;
    }
</style>
<div class="configServer">
    <div class="head">Fast installation</div>
    <div class="infobox">
        <strong><span class="title">Fast installation</span></strong>
        <br>
        You can add all products with one click then you need to modify product name and description.
        Exchange rate is by rials, you need to modify that if you are using toman.
    </div>
    <form method="post" action="addonmodules.php?module=ConfigServer&serverId=<?=$vars['serverId'];?>&addProducts">
                <table class="form" width="100%">
                <tbody>
                <tr>
                    <td class="fieldlabel" style="font-size: 13px">Type</td>
                    <td class="fieldarea">
                        <select name="productType" class="form-control">
                             <option value="addon" <?=($vars['productType'] == 'addon') ? 'selected' : '';?>>Addon</option>
                             <option value="product" <?=($vars['productType'] == 'product') ? 'selected' : '';?>>Product</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel" style="font-size: 13px">Product Group</td>
                    <td class="fieldarea">
                        <select name="productGroup" class="form-control">
                            <?php foreach($vars['productGroups'] as $key => $item):?>
                                <option value="<?=$item->id;?>" <?=($vars['productGroup'] == $item->id) ? 'selected' : '';?>>(<?=$item->id;?>) <?=$item->name;?></option>
                            <?php endforeach;?>
                        </select>
                        <span>Leave empty if you are adding addon</span>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel" style="font-size: 13px">CSP Product</td>
                    <td class="fieldarea">
                        <select id="multi-view" name="product[]" class="form-control selectize-multi-select" multiple="multiple" data-value-field="id">
                             <?php foreach($vars['products'] as $key => $item):?>
                                <option value="<?=$item->id;?>" <?=(in_array($item->id, $vars['product'])) ? 'selected' : '';?>><?=$item->fullName;?></option>
                            <?php endforeach;?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel" style="font-size: 13px">Allow change IP</td>
                    <td class="fieldarea">
                        <select name="allowChangeIP" class="form-control">
                             <option value="1" <?=($vars['allowChangeIP']) ? 'selected' : '';?>>Yes</option>
                             <option value="0" <?=(!$vars['allowChangeIP']) ? 'selected' : '';?>>No</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel" style="font-size: 13px">Currency</td>
                    <td class="fieldarea">
                        <select name="currency" class="form-control">
                            <?php foreach($vars['currencies'] as $key => $item):?>
                                <option value="<?=$item->id;?>" <?=($vars['currency'] == $item->id) ? 'selected' : '';?>><?=$item->code;?></option>
                            <?php endforeach;?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel" style="font-size: 13px">Exchange rate</td>
                    <td class="fieldarea">
                        <input type="number" name="exchangeRate" class="form-control" value="<?=$vars['exchangeRate'];?>">
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel" style="font-size: 13px">Round by</td>
                    <td class="fieldarea">
                        <input type="number" name="roundBy" class="form-control" value="<?=$vars['roundBy'];?>">
                    </td>
                </tr>
            </tbody>
            </table>
            <div class="btn-container">
                <input type="submit" value="Add" class="btn btn-primary">
            </div>
        </form>
        <?php if(isset($vars['success']) && !empty($vars['success'])):?>
            <div class="successbox">
                <strong><span class="title">Changes Saved Successfully!</span></strong>
                <br>
                <?=$vars['success'];?>
            </div>
        <?php endif;?>
        <?php if(isset($vars['error']) && !empty($vars['error'])):?>
            <div class="errorbox"><strong><span class="title">Error</span></strong><br><?=$vars['error'];?></div>
        <?php endif;?>
</div>