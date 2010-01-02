<style>
.casseaException{font-size:16px; font-family: Verdana; width:800px; margin-bottom: 20px;}
.casseaException th{color: white; background-color: darkred; line-height: 28px;}
.casseaException .trace{white-space:pre;overflow: display;width:600px;font-size:14px !important;line-height: 24px;}
.casseaException td{padding-left:10px; padding-right:10px;}
.casseaException .l{ width:100px; vertical-align:top; }
</style>
<table class='casseaException'>
<th colspan='2'><?php echo $data['type']; ?></th>
<?php if(isset($data['extra'])){
    foreach( $data['extra'] as $k => $v) echo '<tr><td class="l">'.ucfirst($k).':</td><td>'.$v.'</td></tr>';
} ?>
<tr><td class='l'>Message:</td><td><?php echo $data['message']; ?></td></tr>
<tr><td class='l'>Code:</td><td><?php echo $data['code']; ?></td></tr>
<tr><td class='l'>File:</td><td><?php echo $data['file']; ?></td></tr>
<tr><td class='l'>Line:</td><td><?php echo $data['line'];?></td></tr>
<tr><td colspan='2'><div class='trace'><?php echo $data['trace'];?></div></td></tr>
<table>

