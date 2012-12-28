<?php
class cell
{
  
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
    if(!is_null($this->value))
    {
      throw new ECellBusy('Cell ' . $this->sign . ' is busy');
    }
    fieldAnalyser::checkValueValid($value);
    $this->value = $value;
  }
  
}

/**
* Класс переводящий код статуса ячейки в символ, отображающий это состояние
* (по идее это часть слоя view)
*/
class cellState
{
  public static function getCellStateName($id)
  {
    $state_list = array(
      fieldAnalyserCrossZero::ZERO_VALUE => '0',
      fieldAnalyserCrossZero::CROSS_VALUE => 'X',
    );
    if(array_key_exists($id, $state_list))
    {
      return $state_list[$id];
    }
    else
    {
      return '&nbsp;';
    }
  }
}

/**
* Класс переводящий код статуса поля в символ, отображающий это состояние
* (по идее это часть слоя view)
*/
class fieldState
{
  public static function getFieldStateName($id)
  {
    $state_list = array(
      fieldAnalyserCrossZero::GS_CONTINUED => 'continued',
      fieldAnalyserCrossZero::GS_CROSS_WON => 'X_won',
      fieldAnalyserCrossZero::GS_ZERO_WON => '0_won',
      fieldAnalyserCrossZero::GS_NO_WIN => 'no_win',
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
    $this->field_state = fieldAnalyser::getInitFieldState();
  }

  /**
  * Получение состояния игры
  * 
  */
  public function getFieldState()
  {
    return $this->field_state;
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
    if(fieldAnalyser::isGameOver($this))
    {
      throw new EGameOver('Game over');
    }
    fieldAnalyser::checkSignValid($sign);
    $this->cell_list[$sign]->setValue($value);
    $this->setFieldState(fieldAnalyser::calculateFieldState($this, $value));
  }
  
  /**
  * Получение списка координат ячеек, занятых к-л значением
  * 
  * @param mixed $value (X или 0)
  */
  public function getCellSign4Value($value)
  {
    fieldAnalyser::checkValueValid($value);
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
  * @param mixed $sign - координаты ячейки
  */
  public function getCellState($sign)
  {
    fieldAnalyser::checkSignValid($sign);
    return $this->cell_list[$sign]->getValue();
  }

  /**
   * Установка состояния поля (игра продолжается, выиграл Х, выиграл 0, ничья)
   * Используется исключительно при инициализации состояния объекта из БД
   * @param $value
   */
  public function setFieldState($value)
  {
    $this->field_state = $value;
  }
  
}

interface IFieldAnalyser
{
  public static function calculateFieldState($field, $value);
  public static function getInitFieldState();
  public static function isGameOver($field);
  public static function checkSignValid($sign);
  public static function checkValueValid($value);
  public static function getSignList();
}

/**
* Класс, реализующий алгоритм игры крестики-нолики
*/
class fieldAnalyserCrossZero implements IFieldAnalyser
{
  const ZERO_VALUE = 0;
  const CROSS_VALUE = 1;

  const GS_CONTINUED = 1; //'continued';
  const GS_CROSS_WON = 2; //'X_won';
  const GS_ZERO_WON = 3; //'0_won';
  const GS_NO_WIN = 4; //'no_win';
  
  /**
   * Расчет состояния поля
   * @static
   * @param $field
   * @param $value
   * @return int
   */
  public static function calculateFieldState($field, $value)
  {
    if(fieldAnalyserCrossZero::isFieldWon($field, $value))
    {
      return fieldAnalyserCrossZero::getWinState($value);
    }
    elseif(fieldAnalyserCrossZero::isFieldWinAvaliable($field, $value) == false)
    {
      return fieldAnalyserCrossZero::GS_NO_WIN;
    }
    return fieldAnalyserCrossZero::GS_CONTINUED;
  }
  
  public static function getInitFieldState()
  {
    return fieldAnalyserCrossZero::GS_CONTINUED;
  }
  
  public static function isGameOver($field)
  {
    $result = false;
    if($field->getFieldState() <> fieldAnalyserCrossZero::GS_CONTINUED)
    {
      $result = true;
    }
    return $result;
  }
  
  public static function checkSignValid($sign)
  {
    if(!in_array($sign, fieldAnalyserCrossZero::getSignList()))
    {
      throw new EInvalidSign($sign . ' is invalid sign');
    }
  }

  public static function checkValueValid($value)
  {
    if(!(($value == fieldAnalyserCrossZero::CROSS_VALUE) or ($value == fieldAnalyserCrossZero::ZERO_VALUE)))
    {
      throw new EInvalidValue($value . ' - is invalid value for cell');
    }
  }
  
  public static function getSignList()
  {
    return array(1,2,3,8,9,4,7,6,5);
  }
  
  /**
  * Получение противоположного значения, относительно параметра
  * 
  * @param mixed $value - значение (X или 0)
  */
  public static function getOppositValue($value)
  {
    fieldAnalyserCrossZero::checkValueValid($value);
    $result = fieldAnalyserCrossZero::CROSS_VALUE;
    if($value == fieldAnalyserCrossZero::CROSS_VALUE)
    {
      $result = fieldAnalyserCrossZero::ZERO_VALUE;
    }
    return $result;
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
  private static function isFieldWon($field, $value)
  {
    $result = false;
    fieldAnalyserCrossZero::checkValueValid($value);
    $state = $field->getCellSign4Value($value);
    if(count($state) < 3) return $result;
    foreach(fieldAnalyserCrossZero::getWinPosition() as $win_sign_list)
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
  private static function isFieldWinAvaliable($field, $value)
  {
    $result = false;
    fieldAnalyserCrossZero::checkValueValid($value);
    $state_zero = $field->getCellSign4Value(fieldAnalyserCrossZero::ZERO_VALUE);
    $state_cross = $field->getCellSign4Value(fieldAnalyserCrossZero::CROSS_VALUE);
    foreach(fieldAnalyserCrossZero::getWinPosition() as $win_sign_list)
    {
      //проверка для fieldAnalyserCrossZero::ZERO_VALUE
      $index_available = 0;
      foreach($win_sign_list as $win_sign)
      {
        if(in_array($win_sign, $state_zero))
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
      //проверка для fieldAnalyserCrossZero::CROSS_VALUE
      $index_available = 0;
      foreach($win_sign_list as $win_sign)
      {
        if(in_array($win_sign, $state_cross))
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

  /**
   * Получение выигрышного статуса для к-л из игроков
   * @static
   * @param $value
   * @return int
   */
  private static function getWinState($value)
  {
    fieldAnalyser::checkValueValid($value);
    if($value == fieldAnalyserCrossZero::CROSS_VALUE)
    {
      return fieldAnalyserCrossZero::GS_CROSS_WON;
    }
    else
    {
      return fieldAnalyserCrossZero::GS_ZERO_WON;
    }
  }
}

/**
* Класс-обертка, параметризуемый конкретным исполнителем алгоритма игры
*/
class fieldAnalyser implements IFieldAnalyser
{
  private static $cur_realisation;
  
  public static function init($realisation_class)
  {
    self::$cur_realisation = $realisation_class;
  }

  /**
   * Расчет состояния поля
   * @static
   * @param $field
   * @param $value
   * @return int
   */
  public static function calculateFieldState($field, $value)
  {
    $class_name = self::$cur_realisation;
    return $class_name::calculateFieldState($field, $value);
  }
  /**
   * Статус присваиваемый полю при инициализации
   * @static
   * @return int
   */
  public static function getInitFieldState()
  {
    $class_name = self::$cur_realisation;
    return $class_name::getInitFieldState();
  }
  /**
   * Проверка игры на завершенность
   * @static
   * @param $field
   * @return bool
   */
  public static function isGameOver($field)
  {
    $class_name = self::$cur_realisation;
    return $class_name::isGameOver($field);
  }
  /**
   * Проверка координаты поля на допустимость
   * @static
   * @param $sign
   * @throws EInvalidSign
   */
  public static function checkSignValid($sign)
  {
    $class_name = self::$cur_realisation;
    return $class_name::checkSignValid($sign);
  }
  /**
   * Проверка значения, предполагаемого к записи в ячейку на допустимость
   * @param $value
   */
  public static function checkValueValid($value)
  {
    $class_name = self::$cur_realisation;
    return $class_name::checkValueValid($value);
  }
  
  /**
  * Получение списка координат ячеек поля
  * 
  */
  public static function getSignList()
  {
    $class_name = self::$cur_realisation;
    return $class_name::getSignList();
  }

}

class fieldPeer
{
  public static function getField($id = null)
  {
    $storage = fieldStorageFabrique::getStorage();
    $field = $storage->getField($id);
    return $field;
  }

  public static function saveField($field)
  {
    $storage = fieldStorageFabrique::getStorage();
    $storage->saveField($field);
  }
}

class valuePeer
{
  public static function getValue()
  {
    $storage = valueStorageFabrique::getStorage();
    $value = $storage->getValue();
    return $value;
  }
  
  public static function saveValue($value)
  {
    $storage = valueStorageFabrique::getStorage();
    $storage->saveValue($value);
  }  
}

/**
* Класс-обертка вокруг данных запроса
*/
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

/**
* Класс-обертка вокруг данных сессии
*/
class sessionWrapper
{
  private static $session_started;
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
  public static function init()
  {
    if(!isset(self::$session_started))
    {
      session_start();
      self::$session_started = true;
    }
  }
}

interface IDBInfoHolder
{
  public function getConnectionOptions();
}

/**
* Класс предоставляющий данные соединения с БД MySQL
*/
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

/**
* Класс, ответственный за подключение к БД MySQL и работу с ней
*/
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

interface IValueStorage
{
  public function getValue();
  public function saveValue($field);
  public function init();
}

class valueStorageFabrique
{
  public static function getStorage()
  {
    return new valueSessionStorage();
    //return new valueMySQLStorage();
  }
}

class valueSessionStorage implements IValueStorage
{
  public function getValue()
  {
    if(sessionWrapper::hasAttribut('game_value'))
    {
      $curr_value = fieldAnalyserCrossZero::getOppositValue(unserialize(sessionWrapper::getAttribute('game_value')));
    }
    else
    {
      $curr_value = fieldAnalyserCrossZero::CROSS_VALUE;
    }
    return $curr_value;
  }
  public function saveValue($value)
  {
    sessionWrapper::setAttribute('game_value', serialize($value));    
  }
  public function init()
  {
    sessionWrapper::init();
  }
}

interface IFieldStorage
{
  public function getField();
  public function saveField($field);
  public function init();
}

class fieldStorageFabrique
{
  public static function getStorage()
  {
    //return new fieldSessionStorage();
    return new fieldMySQLStorage();
  }
}

class fieldSessionStorage implements IFieldStorage
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
    sessionWrapper::init();
  }
}

class fieldMySQLStorage implements IFieldStorage
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