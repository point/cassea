==== <?php echo $data['type']; ?> ====
<?php if(isset($data['extra'])){
    foreach( $data['extra'] as $k => $v)
        printf('%-9s: %s'.PHP_EOL, ucfirst($k), $v);
} ?>
Message  : <?php echo $data['message']; ?>

Code     : <?php echo $data['code']; ?>

File     : <?php echo $data['file']; ?>

Line     : <?php echo $data['line'];?>

<?php echo isset($data['trace'])?$data['trace']:'';?>


