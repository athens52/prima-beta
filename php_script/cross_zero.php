<?php
class cell
{
  const ZERO_VALUE = 0;
  const CROSS_VALUE = 1;
  
  private $id;
  private $field_id;
  private $sign;
  private $value;
  
  /**
  * Конструктор
  * 
  */
  function __construct()
  {
    $this->id = 0;
    $this->field_id = 0;
    $this->value = null;
  }
  
  public function getId()
  {
    return $this->id;
  }
  
  public function getFieldId()
  {
    return $this->field_id;
  }
  
  public function getSign()
  {
    return $this->sign;
  }
  
  public function setId($value)
  {
    $this->id = $value;
  }
  
  public function setFieldId($value)
  {
    $this->field_id = $value;
  }
  
  public function setSign($value)
  {
    $this->sign = $value;
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

class fieldState
{
  public static function getFieldStateName($id)
  {
    $state_list = array(
      field::GS_CONTINUED => 'continued',
      field::GS_CROSS_WON => 'X_won',
      field::GS_ZERO_WON => '0_won',
      field::GS_NO_WIN => 'no_win',
    );
    if(array_key_exists($id, $state_list))
    {
      return $state_list[$id];
    }
    else
    {
      return 'unknown state';
    }
  }
}

class field
{
  const GS_CONTINUED = 1; //'continued';
  const GS_CROSS_WON = 2; //'X_won';
  const GS_ZERO_WON = 3; //'0_won';
  const GS_NO_WIN = 4; //'no_win';

  private $cell_list;
  private $field_state;
  private $id;
  
  /**
  * Конструктор
  * 
  */
  function __construct()
  {
    $cell_sign_list = fieldAnalyser::getSignList();
    $this->cell_list = array();
    $this->id = 0;
    foreach($cell_sign_list as $key => $sign)
    {
      $cell = new cell();
      $cell->setSign($sign);
      $this->cell_list[$sign] = $cell;
    }
    $this->field_state = self::GS_CONTINUED;
  }

  /**
  * Получение состояния игры
  * 
  */
  public function getFieldState()
  {
    return $this->field_state;
  }

  public function setFieldState($value)
  {
    $this->field_state = $value;
  }
  
  public function getId()
  {
    return $this->id;
  }
  
  public function setId($value)
  {
    $this->id = $value;
  }
  
  public function initCell($sign, $cell_id, $field_id, $value)
  {
    $this->cell_list[$sign]->setId($cell_id);
    $this->cell_list[$sign]->setFieldId($field_id);
    $this->cell_list[$sign]->setSign($sign);
    $this->cell_list[$sign]->setValue($value);
  }
  
  public function getCell($sign)
  {
    return $this->cell_list[$sign];
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
      throw new EGameOver('Game over');
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
        $this->setFieldState(self::GS_CROSS_WON);
      }
      else
      {
        $this->setFieldState(self::GS_ZERO_WON);
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
  public static function getField($id = null)
  {
    $storage = storageFabrique::getStorage();
    $field = $storage->getField($id);
    return $field;
  }
  
  public static function saveField($field)
  {
    $storage = storageFabrique::getStorage();
    $storage->saveField($field);
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

interface IDBInfoHolder
{
  public function getConnectionOptions();
}

class DBInfoHolder implements IDBInfoHolder
{
  public function getConnectionOptions()
  {
    return array(
      'host' => 'localhost',
      'database' => 'test',
      'login' => 'root',
      'password' => '1',
    );
  }
}

class DBConnector
{
  private static $connection;
  
  public static function getConnection(IDBInfoHolder $db_info_holder = null)
  {
    if(!isset(self::$connection))
    {
      if(is_null($db_info_holder))
      {
        throw new EDBConnectionFailed('No data was found to establish connection');
      }
      $connection_options = $db_info_holder->getConnectionOptions();
      self::$connection = mysql_connect(
        $connection_options['host'],
        $connection_options['login'],
        $connection_options['password']);
      $sql_err_num = mysql_errno();
      $sql_err_mess = mysql_error();
      if ($sql_err_num <> 0) 
      {
        throw new EDBConnectionFailed($sql_err_mess);
      }
      mysql_select_db($connection_options['database']);

      $query = "SET NAMES cp1251";
      self::executeQuery($query);
    }
    return self::$connection;
  }
  
  public static function closeConnection()
  {
    if(isset(self::$connection))
    {
      mysql_close(self::$connection);
    }
  }
  
  public static function executeQuery($query)
  {
    $connection = self::getConnection();
    $sql_res = mysql_query($query, $connection);
    $sql_err_num = mysql_errno();
    $sql_err_mess = mysql_error();
    if ($sql_err_num <> 0) {
      throw new EQueryFailed($sql_err_mess . '[' . $query . ']');
    }
  }
  
  public static function doSelect($query)
  {
    $connection = self::getConnection();
    $sql_res = mysql_query($query, $connection);
    $sql_err_num = mysql_errno();
    $sql_err_mess = mysql_error();
    if ($sql_err_num <> 0) 
    {
      throw new EQueryFailed($sql_err_mess . '[' . $query . ']');
    }
    $num_results = mysql_num_rows($sql_res);
    $data = array();
    for ($i = 0; $i < $num_results; $i++)
    {
      $data[] = mysql_fetch_array($sql_res);
    }
    mysql_free_result($sql_res);
    return $data;
  }
}

interface IStorage
{
  public function getField();
  public function saveField($field);
  public function init();
}

class storageFabrique
{
  public static function getStorage()
  {
    //return new SessionStorage();
    return new MySQLStorage();
  }
}

class SessionStorage implements IStorage
{
  public function getField()
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
  
  public function saveField($field)
  {
    sessionWrapper::setAttribute('game_data', serialize($field));
  }

  public function init()
  {
    session_start();
  }
}

class MySQLStorage implements IStorage
{
  public function getField($id = null)
  {
    if(is_null($id))
    {
      $field = new field();
    }
    else
    {
      $field_data = DBConnector::doSelect('SELECT id, state FROM cs_field WHERE id = ' . $id);
      $field = new field();
      foreach($field_data as $index => $row)
      {
        $field->setId($row['id']);
        $field->setFieldState($row['state']);
      }
      $cell_data = DBConnector::doSelect('SELECT id,field_id,sign,value,update_date FROM cs_cell WHERE field_id = ' . $id . ' ORDER BY update_date');
      foreach($cell_data as $index => $row)
      {
        $field->initCell($row['sign'], $row['id'], $row['field_id'], (int)$row['value']);
      }
    }
    return $field;
  }
  
  public function saveField($field)
  {
    if($field->getId() == 0)
    {
      $query = 'INSERT INTO cs_field (state) VALUES (' . $field->getFieldState() . ')';
    }
    else
    {
      $query = 'UPDATE cs_field SET state = ' . $field->getFieldState() . ' WHERE id = ' . $field->getId();
    }
    DBConnector::executeQuery($query);
    if($field->getId() == 0)
    {
      $id = self::getLastId();
      $field->setId($id);
    }
    
    foreach(fieldAnalyser::getSignList() as $sign)
    {
      $cell = $field->getCell($sign);
      if(!is_null($cell->getValue()))
      {
        if($cell->getId() == 0)
        {
          $query = 'INSERT INTO cs_cell (FIELD_ID, SIGN, VALUE, UPDATE_DATE) VALUES (' . $field->getId() . ', ' . $cell->getSign() . ', ' . $cell->getValue() . ', Now())';
        }
        else
        {
          $query = 'UPDATE cs_cell SET FIELD_ID = ' . $cell->getFieldId() . ', SIGN = ' . $cell->getSign() . ', VALUE = ' . $cell->getValue() . ', UPDATE_DATE = Now() WHERE ID = ' . $cell->getId();
        }
        DBConnector::executeQuery($query);
        if($cell->getId() == 0)
        {
          $id = self::getLastId();
          $cell->setId($id);
        }
      }
    }
  }

  public function init()
  {
    session_start();
    $db_info = new DBInfoHolder();
    DBConnector::getConnection($db_info);
  }

  private static function getLastId()
  {
    $data = DBConnector::doSelect('SELECT last_insert_id() as id');
    $result = 0;
    foreach($data as $index => $row)
    {
      $result = $row['id'];
    }
    return $result;
  }

  public function getFieldList()
  {
    $field_data = DBConnector::doSelect('SELECT id, state FROM cs_field ORDER BY id');
    $result = array();
    foreach($field_data as $index => $row)
    {
      $field = new field();
      $field->setId($row['id']);
      $field->setFieldState($row['state']);
      $result[$field->getId()] = $field;
    }
    return $result;
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

class EGameOver extends Exception
{

}

class EDBConnectionFailed extends Exception
{
  
}

class EQueryFailed extends Exception
{
  
}
?>

