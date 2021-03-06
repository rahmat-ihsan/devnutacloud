<?php
/*
 * This file created by Em Husnan
 * Copyright 2015
 */
?>
<div class="row">
    <div class="col-md-12">
        <div class="box-widget widget-module">
            <div class="widget-head clearfix">
                <span class="h-icon"><i class="fa fa-table"></i></span>
                <h4>Rincian HPP</h4>
                <ul class="widget-action-bar pull-right">
                    <li><span class="widget-collapse waves-effect w-collapse"><i class="fa fa-angle-down"></i></span></li>
                </ul>
            </div>
            <div class="widget-container">
                <div class=" widget-block">
                    <?php $this->load->view('features/filters/filter_form_rincian_hpp'); ?>
                    <hr/>
                    <div class="table-responsive">
                        <table class="table table-bordered  table-striped dt-table-export ">
                            <thead>
                            <tr>
                                <?php foreach ($datagrid['fields'] as $field) { ?>
                                    <th>
                                        <?= CamelToWords($field->name); ?>
                                    </th>
                                <?php } ?>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $grandTotal = 0;

                            foreach ($datagrid['result']as $row) { ?>
                                <tr>
                                    <?php foreach ($datagrid['fields'] as $field) { ?>
                                        <td>
                                            <?php
                                            $fieldname = $field->name;
                                            $lowerfieldname=strtolower($fieldname);
                                            if(strpos($lowerfieldname,'hargapokok') !== FALSE){
                                                echo "". $this->currencyformatter->format($row->$fieldname);
                                            }else if($lowerfieldname === 'jumlah'){
                                                $grandTotal+=$row->$fieldname;
                                                echo "". $this->currencyformatter->format($row->$fieldname);
                                            }else {
                                                echo $row->$fieldname;
                                            }
                                            ?>
                                        </td>
                                    <?php } ?>
                                </tr>
                            <?php } ?>
                            <?php $method = $this->uri->segment(2); if(1==1){?>
                                <!--total-->
                                <tr>
                                    <td colspan="<?=count($datagrid['fields']) -1;?>">
                                        Grand Total
                                    </td>
                                    <td><?=$this->currencyformatter->format($grandTotal);?></td>
                                </tr>
                                <!--/total-->
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
