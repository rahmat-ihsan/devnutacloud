<?php
/*
 * This file created by Em Husnan
 * Copyright 2015
 */
?>
<div class="mini-stats-widget full-block-mini-chart">
    <div class="loadmask" id="loadmask-ratarata-transaksi-hari-ini">
        <div class="loadmask-msg" style=" left: 50%;
    top: 50%;
    transform: translate(-50%,-50%);">
            <div class="clearfix">
                <div class="w-loader"></div>
                <span class="w-mask-label">Loading..<span></span>
                </span>
            </div>
        </div>
    </div>
    <div class="mini-stats-top">
        <span class="mini-stats-value" id="caption-ratarata-transaksi-hari-ini">&nbsp;</span>
    </div>
    <a class="ico-cirlce-widget widget-bg-green" target="_BLANK" href="<?= base_url()?>laporan/penjualan?date_start=<?= $selected_datestart ?>&date_end=<?= $selected_dateend?><?php if($selected_outlet != 'Semua') echo '&outlet=' . $selected_outlet; ?>">
        <span><i class="fa fa-list" style="color:#4EBC70"></i></span>
    </a>
    <div class="mini-stats-price">
        <span class="mini-stats-value" id="ratarata-transaksi-hari-ini">&nbsp;</span>
    </div>
    <div class="mini-stats-bottom widget-bg-green" id="footer-ratarata-transaksi-hari-ini">
        <span id="caption-footer-ratarata-transaksi-hari-ini">&nbsp;</span>
    </div>


</div>
