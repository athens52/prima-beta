<?php
//Скрипт для получения списка игр и просмотра истории каждой из них
  include("../../php_script/cross_zero.php");

  $error_message = '';
  fieldAnalyser::init('fieldAnalyserCrossZero');
  if(fieldStorageFabrique::getStorage() instanceof fieldMySQLStorage)
  {
    fieldStorageFabrique::getStorage()->init();
    if(requestWrapper::hasParameter('field_id'))
    {
      $field = fieldPeer::getField(requestWrapper::getParameter('field_id', null));
    }
    else
    {
      $field_list = fieldStorageFabrique::getStorage()->getFieldList();
    }
  }
  else
  {
    $error_message = 'Operation not supported';
    $field_list = array();
  }


?>
<div>
<?php if(strlen($error_message) > 0) {?>
<span style="color:red;">
  <?php echo $error_message;?>
</span>
<?php } ?>
</div>

<?php if(requestWrapper::hasParameter('field_id')) {?>
<div>
  game status: <span style="color:<?php echo fieldAnalyser::isGameOver($field) ? 'green' : 'black'?>;"><?php echo fieldState::getFieldStateName($field->getFieldState());?></span>
</div>
<table width=150px border=1>
  <?php foreach (fieldAnalyser::getSignList() as $sign) {?>
    <?php $cell = $field->getCell($sign);?>
  <tr>
    <td>
      <?php echo $cell->getSign()?>
    </td>
    <td>
      <?php echo cellState::getCellStateName($cell->getValue())?>
    </td>
  </tr>
  <?php }?>
</table>
<a href="indexField.php">Back to list</a>
<?php } else {?>
<table width=150px border=1>
  <?php foreach ($field_list as $is => $field) {?>
  <tr>
    <td>
      <?php echo $field->getId();?>
    </td>
    <td>
      <span style="color:<?php echo fieldAnalyser::isGameOver($field) ? 'green' : 'black'?>;"><?php echo fieldState::getFieldStateName($field->getFieldState());?></span>
    </td>
    <td>
      <a href="indexField.php?field_id=<?php echo $field->getId()?>">Detail</a>
    </td>
  </tr>
  <?php }?>
</table>
<?php } ?>