<?php
class cell
{
  const ZERO_VALUE = 0;
  const CROSS_VALUE = 1;
  
  private $value;
  
  /**
  * Конструктор
  * 
  */
  function __construct()
  {
    $this->value = null;
  }
  
  /**
  * Получение состояния ячейки
  * 
  */
  public function getValue()
  {
    return $this->value;
  }
  
  /**
  * Изменение состояния ячейки
  * 
  * @param mixed $value - значение (X или 0) 
  */
  public function setValue($value)
  {
    if(!(($value == self::CROSS_VALUE) or ($value == self::ZERO_VALUE)))
    {
      throw new EInvalidValue($value . ' - is invalid value for cell');
    }
    $this->value = $value;
  }
  
  /**
  * Получение противоположного значения, относительно параметра
  * 
  * @param mixed $value - значение (X или 0)
  */
  public static function getOppositValue($value)
  {
    if(!(($value == self::CROSS_VALUE) or ($value == self::ZERO_VALUE)))
    {
      throw new EInvalidValue($value . ' - is invalid value for cell');
    }
    $result = self::CROSS_VALUE;
    if($value == self::CROSS_VALUE)
    {
      $result = self::ZERO_VALUE;
    }
    return $result;
  }
}

class field
{
  const GS_CONTINUED = 'continued';
  const GS_CROSS_WON = 'X_won';
  const GS_ZERO_WON = '0_won';
  const GS_NO_WIN = 'no_win';

  private $cell_list;
  private $field_state;
  
  /**
  * Конструктор
  * 
  */
  function __construct()
  {
    $cell_sign_list = fieldAnalyser::getSignList();
    $this->cell_list = array();
    foreach($cell_sign_list as $key => $sign)
    {
      $this->cell_list[$sign] = new cell();
    }
    $this->field_state = self::GS_CONTINUED;
  }

  /**
  * Получение состояния ячейки
  * 
  */
  public function getFieldState()
  {
    return $this->field_state;
  }
  
  /**
  * Изменение состояния ячейки
  * 
  * @param mixed $sign - координаты ячейки
  * @param mixed $value - значение, предполагаемое к помещению в ячейку
  */
  public function setCellState($sign, $value)
  {
    if($this->getFieldState() <> self::GS_CONTINUED)
    {
      throw new EGaveOver('Game over');
    }
    if(!in_array($sign, fieldAnalyser::getSignList()))
    {
      throw new EInvalidSign($sign . ' is invalid sign');
    }
    if(!is_null($this->cell_list[$sign]->getValue()))
    {
      throw new ECellBusy('Cell ' . $sign . ' is busy');
    }
    $this->cell_list[$sign]->setValue($value);
    if(fieldAnalyser::isFieldWon($this, $value))
    {
      if($value == cell::CROSS_VALUE)
      {
        $this->field_state = self::GS_CROSS_WON;
      }
      else
      {
        $this->field_state = self::GS_ZERO_WON;
      }
    }
    elseif(fieldAnalyser::isFieldWinAvaliable($this, $value) == false)
    {
      $this->field_state = self::GS_NO_WIN;
    }
  }
  
  /**
  * Получение списка координат ячеек, занятых к-л значением
  * 
  * @param mixed $value (X или 0)
  */
  public function getCellSign4Value($value)
  {
    if(!(($value == cell::CROSS_VALUE) or ($value == cell::ZERO_VALUE)))
    {
      throw new EInvalidValue($value . ' - is invalid to check win');
    }
    $result = array();
    foreach($this->cell_list as $sign => $cell)
    {
      if($cell->getValue() === $value)
      {
        $result[] = $sign;
      }
    }
    return $result;
  }
  
  /**
  * Получение состояния ячейки поля
  * 
  * @param mixed $sign - координты ячейки
  */
  public function getCellState($sign)
  {
    if(!in_array($sign, fieldAnalyser::getSignList()))
    {
      throw new EInvalidSign($sign . ' is invalid sign');
    }
    return $this->cell_list[$sign]->getValue();
  }
  
}

class fieldAnalyser
{
  /**
  * Получение списка координат ячеек поля
  * 
  */
  public static function getSignList()
  {
    return array(1,2,3,8,9,4,7,6,5);
  }
  
  /**
  * Получение списка выигрышных состояний (координат ячеек, занятие которых обеспечивает игроку победу)
  * 
  */
  private static function getWinPosition()
  {
    return array(array(1,2,3), array(3,4,5), array(5,6,7), array(1,8,7), array(1,9,5), array(3,9,7), array(2,9,6), array(8,9,4));
  }
  
  /**
  * Определение выигрыша для игрока
  * 
  * @param mixed $field - поле для игры (field)
  * @param mixed $value - значение, указывающее на игрока (X или 0)
  */
  public static function isFieldWon($field, $value)
  {
    $result = false;
    if(!(($value == cell::CROSS_VALUE) or ($value == cell::ZERO_VALUE)))
    {
      throw new EInvalidValue($value . ' - is invalid to check win');
    }
    $state = $field->getCellSign4Value($value);
    if(count($state) < 3) return $result;
    foreach(self::getWinPosition() as $win_sign_list)
    {
      $index_win = 0;
      foreach($win_sign_list as $win_sign)
      {
        if(!in_array($win_sign, $state))
        {
          break;
        }
        $index_win++;
      }
      if($index_win == count($win_sign_list))
      {
        $result = true;
        break;
      }
    }
    return $result;
  }

  /**
  * Определение возможности выигрыша для игрока
  * 
  * @param mixed $field - поле для игры (field)
  * @param mixed $value - значение, указывающее на игрока (X или 0)
  */
  public static function isFieldWinAvaliable($field, $value)
  {
    $result = false;
    if(!(($value == cell::CROSS_VALUE) or ($value == cell::ZERO_VALUE)))
    {
      throw new EInvalidValue($value . ' - is invalid to check winner');
    }
    $state = $field->getCellSign4Value(cell::getOppositValue($value));
    foreach(self::getWinPosition() as $win_sign_list)
    {
      $index_available = 0;
      foreach($win_sign_list as $win_sign)
      {
        if(in_array($win_sign, $state))
        {
          break;
        }
        $index_available++;
      }
      if($index_available == count($win_sign_list))
      {
        $result = true;
        break;
      }
    }
    return $result;
  }

}

class fieldPeer
{
  public static function getField()
  {
    if(sessionWrapper::hasAttribut('game_data') and (requestWrapper::hasParameter('clear') == false))
    {
      $field = unserialize(sessionWrapper::getAttribute('game_data'));
    }
    else
    {
      $field = new field();
      sessionWrapper::setAttribute('game_data', serialize($field));
    }
    return $field;
  }
  
  public static function saveField($field)
  {
    sessionWrapper::setAttribute('game_data', serialize($field));
  }
}

class valuePeer
{
  public static function getValue()
  {
    if(sessionWrapper::hasAttribut('game_value'))
    {
      $curr_value = cell::getOppositValue(unserialize(sessionWrapper::getAttribute('game_value')));
    }
    else
    {
      $curr_value = cell::CROSS_VALUE;
    }
    return $curr_value;
  }
  
  public static function saveValue($value)
  {
    sessionWrapper::setAttribute('game_value', serialize($value));    
  }  
}

class requestWrapper
{
  public static function getParameter($param_name, $default = null)
  {
    global $HTTP_GET_VARS;
    if(self::hasParameter($param_name))
    {
      return $HTTP_GET_VARS[$param_name];
    }
    else
    {
      return $default;
    }
  }
  public static function hasParameter($param_name)
  {
    global $HTTP_GET_VARS;
    if(array_key_exists($param_name, $HTTP_GET_VARS))
    {
      return true;
    }
    else
    {
      return false;
    }
  }
}

class sessionWrapper
{
  public static function getAttribute($attr_name, $default = null)
  {
    global $_SESSION;
    if(self::hasAttribut($attr_name))
    {
      return $_SESSION[$attr_name];
    }
    else
    {
      return $default;
    }
  }
  
  public static function setAttribute($attr_name, $value)
  {
    global $_SESSION;
    $_SESSION[$attr_name] = $value;
  }
  
  public static function hasAttribut($attr_name)
  {
    global $_SESSION;
    if(array_key_exists($attr_name,$_SESSION))
    {
      return true;
    }
    else
    {
      return false;
    }
  }
}

class ECellBusy extends Exception
{
  
}

class EInvalidValue extends Exception
{
  
}

class EInvalidSign extends Exception
{
  
}

class EGaveOver extends Exception
{

}
?>

