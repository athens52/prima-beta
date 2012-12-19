<?php
//Скрипт для собственно игры
  include("../../php_script/cross_zero.php");

  storageFabrique::getStorage()->init();

  $field = fieldPeer::getField(requestWrapper::getParameter('id', null));

  $sign_list = fieldAnalyser::getSignList();
  $sign_index = 0;
  $error_message = '';
  if(requestWrapper::hasParameter('sign'))
  {
    $curr_value = valuePeer::getValue();
    try
    {
      $field->setCellState(requestWrapper::getParameter('sign'), $curr_value);
      fieldPeer::saveField($field);
      valuePeer::saveValue($curr_value);
    }
    catch(Exception $e)
    {
      $error_message = $e->getMessage();
    }
  }
?>
<div>
game status: <span style="color:<?php echo $field->getFieldState() == field::GS_CONTINUED ? 'black' : 'green'?>;"><?php echo fieldState::getFieldStateName($field->getFieldState());?></span>
<?php if(strlen($error_message) > 0) {?>
<span style="color:red;">
  <?php echo $error_message;?>
</span>
<?php } ?>

</div>
<table width=150px border=1>
  <?php for ($i = 0; $i < 3; $i++) {?>
  <tr>
    <?php for ($j = 0; $j < 3; $j++) {?>
    <td width="33%">
      <a href="index.php?id=<?php echo $field->getId();?>&sign=<?php echo $sign_list[$sign_index];?>"><div><?php echo is_null($field->getCellState($sign_list[$sign_index])) ? '&nbsp' : ($field->getCellState($sign_list[$sign_index]) == cell::CROSS_VALUE ? 'X' : '0');?></div></a>
      <?php $sign_index++;?>
    </td>
    <?php }?>
  </tr>
  <?php }?>
</table>
<a href="index.php?clear=1">clear</a>
