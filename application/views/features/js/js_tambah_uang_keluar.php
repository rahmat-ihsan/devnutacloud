<?php
/**
 * Created by PhpStorm.
 * User: husnan
 * Date: 18/11/16
 * Time: 19:53
 */
?>
<script type="text/javascript">
    var xx;
    jQuery(document).ready(function ($) {
        xx = $;
        $('#outlet').change(function () {
            var val = $(this).val();

            window.location = '<?=base_url('uang_keluar/data?outlet=');?>' + val;

        });
    });
    function redirectTonewItem() {
        var selected_outlet = xx('#outlet').val();
        if (selected_outlet == -999) {
            alert('Pilih outlet terlebih dahulu.');
        } else {
            window.location = '<?=base_url('uang_keluar/tambahData?outlet=');?>' + selected_outlet;
        }

    }
</script>