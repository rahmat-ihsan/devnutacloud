<?php
/**
 * Created by PhpStorm.
 * User: Husnan
 * Date: 23/01/2016
 * Time: 10:44
 */ ?>
<div class="form-group">
    <label for="date_start" class="col-md-1 control-label">Mulai</label>
    <div class="col-md-3">
        <div class="input-group date" id="datestart" >
            <input type="text" class="form-control"/>
            <span class="input-group-addon">
                <i class="glyphicon glyphicon-th"></i>
            </span>
        </div>
    </div>


    <label for="date_end" class="col-md-1 control-label">Sampai</label>
    <div class="col-md-3">
        <div class="input-group date" id="dateend" >
            <input type="text" class="form-control"/>
        <span class="input-group-addon">
            <i class="glyphicon glyphicon-th"></i>
        </span>
        </div>
    </div>
</div>
<input type="hidden" name="date_start" value="<?= $date_start; ?>"/>
<input type="hidden" name="date_end" value="<?= $date_end; ?>"/>
