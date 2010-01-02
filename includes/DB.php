<?php
/*- vim:noet:shiftwidth=4:tabstop=4: 
{{{ LICENSE  
* Copyright (c) 2008,2009 Cassea Project
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*     * Redistributions of source code must retain the above copyright
*       notice, this list of conditions and the following disclaimer.
*     * Redistributions in binary form must reproduce the above copyright
*       notice, this list of conditions and the following disclaimer in the
*       documentation and/or other materials provided with the distribution.
*     * Neither the name of the Cassea Project nor the
*       names of its contributors may be used to endorse or promote products
*       derived from this software without specific prior written permission.
*
* THIS SOFTWARE IS PROVIDED BY CASSEA PROJECT ''AS IS'' AND ANY
* EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
* WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
* DISCLAIMED. IN NO EVENT SHALL CASSEA PROJECT BE LIABLE FOR ANY
* DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
* (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
* SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
}}} -*/
/**
 * Набор классов для работы с базой данных Mysql 5.0.50+
 *
 *
 * Флаги по умолчанию: DB::FETCH_ASSOC, DB::STORE_RESULT, DB::DROP_EMPTY_RESULTSET, DB::COMPRESS_RESULTS;
 *
 *
 * TODO DB::init() через mysqli->real_connect(..);
 * @version $Id: DB.php 154 2009-10-12 15:40:57Z billy $
 * @package Database
 */


//mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
mysqli_report(MYSQLI_REPORT_OFF);
//mysqli_report(MYSQLI_REPORT_ALL);

// {{{ DBException
/**
 * Исключение используемое классами для работы с БД.
 *
 * Расширяет встроенный класс Extension путем дополнительной
 * закрытой переменной и параметром конструктора  $query.
 * В случае если исключение вызвано ошибкой SQL запроса
 * то при вызове  {@link __toString  __toString} он будет отображен.
 *
 *
 * Расширен метод {@link __toString __toString} для симпатичного отображения исключения.
 *  
 *
 * @package Database
 *
 **/
class DBException extends CasseaException
{
    /**
     * Запрос вызвавший исключение. 
     * Null если исключение не связано с SQL запросом.
     *
     * @var string
     * @access private
     */
    private $query = null;

    // {{{ __construct
    /**
     * Конструктор расширенный параметром $query
     *
     * @param string  $message сообщение, сообщение ошибки mysql сервера(error)
     * @param int     $code код ошибке,  ошибки mysql сервера(errno)
     * @param string  $query SQL запрос вызвавший ошибку.       
     */
    function __construct( $message = NULL, $code = null, $query = NULL )
    {
        if ($query !== NULL) $this->query = $this->extra['query'] = $query;
        parent::__construct(wordwrap($message,75, "\r\n\t\t" ), $code);
    }//}}}

    // {{{ __toString
    /**
     * Приведение исключения к строке. 
     *
     * Для отображение в html странице окружается < pre > </pre> для 
     * удобочитаемости.
     *
     * @return string исключение в виде строки
     */
    function __toString(){
        $isweb = isset($_SERVER['HTTP_HOST']);
        $str = $isweb?"<pre>":"\r\n\r\n";
        $str.= "Exception:\t".get_class($this)."\r\n";
        if ( $this->query !== NULL )
            $str .= "Query:\t".$this->query."\r\n";

        $str.= "Message:\t".$this->message."\r\n";
        $str.= "Code:\t".$this->code."\r\n";
        $str.= "Line:\t".$this->line."\r\n";
        $str.= "File:\t".$this->file."\r\n\r\n";

        $str.= parent::getTraceAsString();

        $str.= $isweb?"</pre>":"\r\n\r\n";
        return $str;
    }// }}}
}// }}}

// {{{ DBConnectException
/**
 * Исключение выбрасывается при ошибке соединения с базой данныйх.
 */
class DBConnectException extends CasseaException{}
// }}}

// {{{ DBStmt
/**
 * "Подготовленные выражения"
 *
 * Класс расширяет класс mysqli_stmt, добавляя его функции 
 * для установки параметров запроса, обработки результатов
 * и выполнения запроса.
 *
 * Подготовленные выражение необходимо использовать для вставки
 * в БД текстовых данных, которые могут содержать символы
 * неадекватно воспринимаемые парсером MySQL сервера. Например:
 * <code>
 *      $badString = <<<EOD "' # \\' \' \\\' < >"
 * EOD; 
 *      $r = DB::getStmt('update table set text=? where id=?', 'si' )
 *          ->execute(array( $badString, 1));
 *      
 * </code>
 *
 * Так же подготовленные выражения необходимо использовать для выполнения
 * однотипных запросов в базе данных.Например:
 * <code>
 *      $id = 1222;
 *      $stmt = DB::getStmt('select parent, name from tree where id=?', 'i' );
 *      for($i = 0; $i < 100; $i++){
 *          $row = $smtm->execute(array($id));
 *          print_r($row[0]);
 *          $id = $row[0]['id'];
 *      }
 * </code>
 *
 * Ввиду того что класс расширяет класс mysqli_stmt, можно обрасчатся 
 * к родительским функциям непосредственно. Исключением является
 * функция execute() - она перегруженна, и для обращения к mysqli_stmt::execute() 
 * необходимо использовать {@link DBStmt::pexecute() DBStmt::pexecute()}.
 *
 *
 * @package Database
 */
class DBStmt extends mysqli_stmt{
    // {{{ class variables
    /**
     * Строка с типом параметров для выражения.
     * См. {@link DBStmt::__construct() конструктор класса}
     * 
     * @var string
     * @access private
     */
    private $typesStr = '';

    /**
     * SQL запрос для подготовленного выражения.
     *
     * Необходим только для более информативного отображения выбрасываемых исключений.
     *
     * @see DBException
     * @var string
     * @access private
     */
    private $userQuery;
    // }}}

    // {{{ constructor
    /**
     * Конструктор
     *
     * Нет необходимости вызывать конструктор напрямую. Необходимо 
     * использовать метод <b>{@link DB::getStmt()}</b>.
     *
     * Зарос для подготовки выражения задается строкой в которой параметры
     * заменены занками вопроса(?) без кавычек.
     *
     * Для каждого параметра в SQL запросе необходимо указать тип.
     * Типы параметров указываются в порядке их вхождения в запрос.
     * Типы для запроса перечисляются строкой, позиция 
     * символа определяет номер параметра в запросе.
     * 
     * Строка параметров может  состоять только из символов 'i', 'd', 's', 'b'.
     * <ul>
     *      <li><b>i</b> целочисленные значения;
     *      <li><b>d</b> значения с плавающей запятой;
     *      <li><b>s</b> строковые значения;
     *      <li><b>b</b> BLOB значния, будут пересылатся пакетами;
     * </ul>
     *
     * @throws DBException если строка параметров некорректна.
     * @param msqyli $link  соеденение с базой данных
     * @param string $query SQL запрос подготовленного выражения
     * @param string $types перечесление типов параметров
     */
    public function __construct( $link, $query, $types){
        $this-> setTypes($types);
        $this->userQuery = $query;

        parent::__construct($link, $query);
    }// }}}

    // {{{__destruct
    /**
     *
     *
     *
     */
    function __destruct(){
    }// }}}



    // {{{bindParam
    /**
     * Устанавливает значение параметров для "Подготовлленного выражения".
     *
     * 
     *
     * @access private
     * @param array $param индексированный массив значений 
     */
    private function bindParam($param){
        if ( $c = ($this->param_count)){
            $args = array($this->typesStr);
            for ($i =0; $i < $c; $i++){
                $args[] = isset($param[$i])? $param[$i]: null;
            }
            call_user_func_array(array($this, 'bind_param'), $args);
        }
    }//}}}



    // {{{ processResults
    /**
     * Обрабатывает результаты выполнения "Подготовленного выражения"
     *
     * ОБработка происходит только первого набора результатов возвращенного
     * подготовленным выражением. Остальные наборы из-за невозможности обработать
     * игнорируются. Те можно вызывать функции, которые возвращают один набор результатов.
     *
     * Для обработки результатов доступны следующие  флаги: {@link DB::USE_RESULT DB::USE_RESULT}, {@link DB::STORE_RESULT};
     *
     * @throws DBException В случае возникновения ошибки.
     * @param int $flags флаги переданные из метода {@link execute() execute()}.
     * @return mixed  ассоциативный массив результатов, NULL если нет результатов
     */
    private function processResults($flags){
        // Обработка флангов
        $store = true;
        if ($flags & DB::USE_RESULT) $store = false;

        // Есть ли данные
        $meta = $this->result_metadata();
        if (!$meta) return;

        if ( $store) $this->store_result();
        
        $resultdata = array();
        $data = array();
        $fields = array($this );
        while($field = mysqli_fetch_field($meta)){ 
            $fields[] = &$data[$field->name];
        }

        do{
            call_user_func_array('mysqli_stmt_bind_result', $fields);
            if ($r =  $this->fetch()){
                $i = count($resultdata);
                foreach ($data as $k => $v)
                    $resultdata[$i][$k] = $v;
            }
            // Ошибка в процесе вытягивания результатов
            if ($r === false) 
                throw(new DBException($this->error,$this->errno, $this->userQuery));

        } while( $r !== null );

        $meta->close();
        if ($store) $this->free_result();

        // Чистим соединение
        DB::clearResultset();

        return $resultdata;
    }// }}}

    // {{{ execute
    /**
     * Выполняет Подготовленное выражение с указанными параметрами.
     *
     * Функция последовательно вызывает {@link DBStmt::bindParam() DBStmt::bindParam() }, mysqli_smtm::execute, {@link DBStmt::processResults() DBStmt::processResults() },
     * и возвращает результат последней.
     * 
     * 
     *
     * @throws DBException в случае ошибки в любом из вызываемых методов.
     * @param array $param массив значений для выражения
     * @param int $flags флаги для обработки результатов доступны следующие  флаги: {@link DB::USE_RESULT DB::USE_RESULT}, {@link DB::STORE_RESULT};
     * @return mixed  ассоциативный массив результатов, NULL если нет результатов
     */
    public function execute($param = array(), $flags = 0){
        // bind params
        $this->bindParam($param);

        if (! parent::execute())
            throw(new DBException($this->error,$this->errno, $this->userQuery));

        //Обработка результатов
        return $this->processResults($flags);
    }// }}}

    // {{{ pexecute
    /**
    * Прозрачно вызывает mysqli_stmt::exeute();
    *
    * @return bool
    */
    public function pexecute(){
        return parent::execute();
    }// }}}
    
    // {{{ setTypes
    /**
     * Проверяет корректность строки параметров и устанавливает переменную {@link typesStr "$typesStr}.
     *
     * @access private
     * @throws DBException если  строка параметров переданная в {@link DBStmt::__construct() конструктор} или   фабричный метод {@link DB::getStmt()} некорректна.
     * @param string $types
     * @return void
     */
    private function setTypes($types){
        if (preg_match('/^[disb]*$/',$types))
            $this->typesStr = $types;
        else
            throw( new DBException('Bad parameters list: "'.$types.'".') );
    }// }}}

}// }}}

// {{{ DBTransaction
/**
 * Транзакция 
 *
 * Класс является реализацией транзакций Базы данных.
 * База данных должна быть транзакционной. Ннапример  InnoDB, Falcon.
 *
 * Пример использования:
 * <code>
 *      $t = DB::getTransaction(); // получение объекта
 *      // выполнение запросов
 *      $t->query('update table1 set value="new value" where k =1');
 *      $t->query('delete table2  where field1 > 100');
 *      $t->query('call someproc()');
 *      // завершение транзакции
 *      $t->commit();
 * </code>
 *
 *
 * Метод query выбрасывает исключение в случае возникновения ошибки,
 * но не делает автоматического отката. Более устойчивый пример:
 * <code>
 *      $t = DB::getTransaction(); // получение объекта
 *      // выполнение запросов
 *      try{
 *          $t->query('update table1 set value="new value" where k =1');
 *          $t->query('delete table2  where field1 > 100');
 *          $t->query('call someproc()');
 *      }
 *      catch(DBException $e){
 *          $t->rollback();
 *      }
 *      // завершение транзакции
 *      // $t->commit() выбросит исключение если транзакция закрыта
 *      try{
 *          $t->commit();
 *      }
 *      catch (DBException $e) {}
 * </code>
 *
 * Если фабричному методу {@link DB::getTransaction() DB::Transaction()} передан параметр
 * $useException = false, то функция DBTransaction::query() не будт выбрасывать исключения.
 * ее результат будет true в случае успешного выполнения запроса, иначе false. Использовать
 * этот флаг не реккомедуется.
 *
 * Так же следует отметить что функция DBTransaction::query() игнорирует результаты SELECT запросов,
 * ввиду из возможной необъективности в контексте базы данных.
 *
 * На время жизни  транзацкии блокируется доступ к методам DB - любое обращение к ним вызовет исключение.
 * В случае необходимости (выполнение "подготовленных" выражений, невозможности сформировать запросы транзакции
 * без использования результатов SELECT) методу  {@link DB::getTransaction() DB::Transaction()} 
 * необходимо передать параметр $lockDB = false. Использовать этот флаг так же не реккомендуется.
 *
 * Из других ограничений следует отметить невозможность исползования 2х транзакций ввиду единственного
 * соединения с базой данных.
 *
 * @package Database
 */
class DBTransaction{
    // {{{ variable
    /**
     * Объект mysqli
     *
     * Используется для всех операций связанных с базой данных.
     *
     * @var mysqli
     * @access private
     */
    private $mysqli;

    /**
     * Флаг определяет поведение DBTransaction::query() в случае возникновения ошибки
     * @var bool
     * @access private
     */
    private $useException = false;

    /**
     * Количество созданных объектов DBTransaction.
     * Если больше одного конструктор выбросит исключение.
     * @var int
     * @access private
     * @static
     */
    //private static $transactionCount = 0;

    /**
     * Флаг определяющий возможность выполнять SQL запросы.
     *
     * Устанавливается в false после вызово методов DBTransaction::commit(),
     * DBTransaction::rollback(). 
     * 
     * @var bool
     * @access private
     */
    private $queryAllow = true;
    // }}}

    // {{{ __construct
    /**
     * Конструктор вызывается неявно из метода {@link DB::getTransaction() DB::Transaction()}.
     *
     *
     * @param mysqli $conn Соединение с базой данных
     * @param bool $useException использовать исключения в случае возникновения ошибок в метода {@link DBTransaction::query  DBTransaction::query}.
     *
     */
    public function __construct($conn, $useException = true){
        $this->transationCount++;
        $this->mysqli = $conn;
        $this->useException = $useException;
        $this->mysqli->autocommit(false);
    }//}}}

    // {{{ __destruct
    public function __destruct(){
        if ($this->mysqli instanceof mysqli){
            $this->mysqli->close();
        }
   
    }// }}}

    // {{{ query
    /**
     * Выполняет sql запрос
     *
     * Результаты запросов не обрабатываются.
     *
     * @throws DBException при наличии флага $useException в конструкторе; Если транзакция уже завершена(выполненны commit или rollback)
     * @param string sql запрос
     * @return bool true если запрос выполнен, false если запрос не выполнен и в конструкторе указан флаг  $useException
     */
    public function query($sql){
        if (!$this->queryAllow)
            throw new DBException('Transaction already commit or rollback. Query cancel.', 0, $sql);
        
        $res = $this->mysqli->real_query($sql);
        if ( $this->mysqli->field_count != 0 ){
            do
                if($result = $this->mysqli->use_result())
                    $result->free();
            while($this->mysqli->next_result());        
        }
     
        if ($res) return true;
        if ($this->useException)
            throw new DBException($this->mysqli->error,$this->mysqli->errno,$sql);
        return false;
    }// }}}


    // {{{ commit 
    /**
     * Завершение транзакции
     *
     * @throws DBException если транзакция уже завершена(выполненны commit или rollback)
     */
    public function commit(){
        if (!$this->queryAllow)
            throw new DBException('Transaction already commit or rollbacked. Query cancel.');

        $this->mysqli->commit();
        $this->shutdown();
    }// }}}
    
    // {{{ rollback
    /**
     * Откат транзакции
     * @throws DBException если транзакция уже завершена(выполненны commit или rollback)
     */
    public function rollback(){
        if (!$this->queryAllow)
            throw new DBException('Transaction already commited or rollbacked. Query cancel.');

        $this->mysqli->rollback();
        $this->shutdown();
    }// }}}

    // {{{ shutdown
    /**
     * Завершение транзакции, перевод DB в рабочее состояние.
     *
     */
    private function shutdown(){
        $this->mysqli->autocommit(true);

        DB::closeTransaction($this);
        $this->mysqli = null;
        $this->queryAllow = false;

    }//}}}

    // {{{ getConnection 
    /**
     * For DB::closeTransaction only
     * @return $mysqli
     */
    public function getConnection(){
        return $this->mysqli;
    }// }}}

}// }}}

// {{{ DBMysqliFake
/**
 *
 * @package Database
 */
class DBMysqliFake{
    public function __call($name, $arguments) {
        throw new DBException("DB is locked while transation didn't commit.",0,"\r\nMethod: ".$name."( ". implode(', ', $arguments). " )\n");
    }
}// }}}

// {{{ DBMysqliLazyLoad
/**
 *
 * @package Database
 */
class DBMysqliLazyLoad{
    private $dsn;

	public function __construct($dsn){
		$this->dsn = $dsn;
    }

	public function __call($name, $arguments) {
        $this->initDB();
		return call_user_func_array(array(DB::getMysqli(), $name), $arguments);
    }

	public function initDB(){
		DB::init($this->dsn,false);
	}
}// }}}

// {{{ DB
/**
 * Статический класс для работы с базой данных Mysql 5.0.50+ 
 * использующий библиотеку mysqli(nd). 
 *
 * Класс DB агрегирует объект mysqli, ссылку на который можно получить с помощбю метода
 * {@link DB::getMysqli() DB::getMysqli()}:
 * <code>
 *      $affected_rows = DB::getMysqli()->affected_rows;
 * </code>
 *
 * Основной функционал сосредоточен в функциях {@link DB::query() DB::query()} и 
 * {@link DB::multiQuery() DB::multiQuery()}. Они реализуют запрос к базе даннных
 * и предварительную обработку результатов.
 *
 *
 * @package Database
 */ 
class DB{

    // {{{ Constants
    /**
     * Флаг используемый функциями {@link DB::query() DB::query()}, {@link DB::multiQuery DB::multiQuery} и {@link DBStmt::execute()  DBStmt::execute()}
     * для формирования результатов запроса.
     *
     * Результаты формируются ввиде ассоциативного массива.
     * 
     * Данное значение является значением по умолчанию для всех fetch-функций.
     */
    const FETCH_ASSOC = 1;
    /**
     * Флаг используемый функциями {@link DB::query() DB::query()}, {@link DB::multiQuery DB::multiQuery} и {@link DBStmt::execute()  DBStmt::execute()}
     * для формирования результатов запроса.
     *
     * Результаты формируются ввиде нумерованного массива.
     */
    const FETCH_NUM =   2;
    /**
     * Флаг используемый функциями {@link DB::query() DB::query()}, {@link DB::multiQuery DB::multiQuery} и {@link DBStmt::execute()  DBStmt::execute()}
     * для формирования результатов запроса.
     *
     * Результаты формируются ввиде ассоциативного и нумерованного массива.
     */
    const FETCH_BOTH =  4;

    /**
     * Флаг для функции {@link DB::multiQuery DB::multiQuery}.
     * Влияет на представление запросов без результатов (UPDATE, DELETE etc): пустые результаты не добавляются в результирующий массив
     *
     *
     * Данное значение является значением по умолчанию.
     */
    const DROP_EMPTY_RESULTSET =    0; 
    /**
     * Флаг для функции {@link DB::multiQuery DB::multiQuery}.
     * Влияет на представление запросов без результатов (UPDATE, DELETE etc): пустые результаты  добавляются в результирующий массив 
     */
    const SAVE_EMPTY_RESULTSET =    8; 



    /**
     * Флаг для функции {@link DB::multiQuery() DB::multiQuery()}.
     * 
     * Результирующий набор представлен ввиде двух мерного массива(а не трех мерного), в который добавляются строки из
     * всех запросов переданных функции {@link DB::multiQuery() DB::multiQuery()};
     *  
     * Данное значение является значением по умолчанию.
     *
     */
    const COMPRESS_RESULTS =        0;
    /**
     * Флаг для функции {@link DB::multiQuery() DB::multiQuery()}.
     * 
     * Результирующий набор представлен ввиде трех мерного массива, состоящего из результатов 
     * кадого из запросов переданных функции {@link DB::multiQuery() DB::multiQuery()};
     *  
     */
    const DONT_COMPRESS_RESULTS =  16;


    /**
     * Флаг используемый функциями {@link DB::query() DB::query()}, {@link DB::multiQuery DB::multiQuery} и {@link DBStmt::execute()  DBStmt::execute()}
     * для работы с результатми запросов.
     *
     * С использованием данного флага результаты используются непосредственно на строне MySql сервера и не переносятся
     * в память php-процесса. 
     *
     * Использование данного флага может приводить к блокировке записей и таблиц которые используются в запросе.
     *
     */
    const USE_RESULT =  32;

    /**
     * Флаг используемый функциями {@link DB::query() DB::query()}, {@link DB::multiQuery DB::multiQuery} и {@link DBStmt::execute()  DBStmt::execute()}
     * для работы с результатми запросов.
     *
     * С использованием данного флага результаты сохраняются на стороне php-процесса. 
     *
     * Данное значение является значением по умолчанию.
     */
    const STORE_RESULT =  0;
 
    // }}}
    /**
     * Объект mysqli
     *
     * Используется для всех операций связанных с базой данных.
     *
     * @var mysqli
     * @access private
     * @static
     */
    private static $mysqli;

    // {{{ init
    /**
     * Инициализирует соединение с базой данных.
     *
     * Инстанцирует объект mysqli в скрытой статической пересменной DB::mysqli.
     *
     *
     * @throws DBException В случае возникновения ошибок подключения к базе данных
     * @param string $host Хост сервера Базы данных
     * @param string $username логин пользователя
     * @param string $password пароль пользователя
     * @param string $dbname имя базы данных
     * @param int $port порт сервера 
     * @param string $socket сокет сервера
     * @return void
     */
    static public function init( $dsn, $lazyLoad = true ){
        if ($lazyLoad){
            self::$mysqli = new DBMysqliLazyLoad($dsn);
            return;
        }

		if (self::$mysqli instanceof DBMysqliLazyLoad){
			$p = self::parseDSN($dsn);
            self::$mysqli = new mysqli( $p['host'], $p['username'], $p['password'], $p['database'], $p['port'], $p['socket']);
			if ( mysqli_connect_errno()) throw new DBConnectException(mysqli_connect_error(), mysqli_connect_errno());
            if (!(self::$mysqli->set_charset('utf8'))) throw ( new DBException('Unable set charset "utf8":'.self::$mysqli->error)); 
        }
    }// }}}

    // {{{ close
    /**
     *Закрывает соединение
     */
    public static function close(){
        if (self::$mysqli instanceof mysqli)
            self::$mysqli->close();
         
    }// }}}

    // {{{ getMysqli
    /**
     * Get-метод для получения ссылки на объект {@link DB::mysqli DB::mysqli}.
     *
     * @return mysqli
     */
    static public function &getMysqli(){
		if(self::$mysqli instanceof DBMysqliLazyLoad) self::$mysqli->initDB();
        return self::$mysqli;
    }// }}}

    // {{{ setMysqli
    /**
     * Set-метод для установки объекта {@link DB::mysqli DB::mysqli}.
     *
     * @return mysqli
     */
    static public function setMysqli($mysqli){
        self::$mysqli = $mysqli;
    }// }}}

    // {{{ query
    /**
     * Выполнение SQL запроса.
     *
     * Если запрос вернет несколько наборов результатов(multi query)
     * будет обработан только первый. Все остальные будут проигнорированы,
     * а соединение с базой данных будет доступно для следующих запросов.
     *
     * Данный метод реккомендуется использовать для выполнения 
     * хранимых продцедур, которые возвращают один набор результатов.
     * Второй набор результатов OK/ERR будет автоматически проигнорирован.
     *
     * @throws DBException в случае возникновения ошибки в запросе
     * @param string $query SQL запрос
     * @param int $flags флаги для обработки результатов доступны следующие  флаги: {@link DB::USE_RESULT DB::USE_RESULT}, {@link DB::STORE_RESULT},
     *                      {@link DB::FETCH_NUM  DB::FETCH_NUM }, {@link DB::FETCH_ASSOC  DB::FETCH_ASSOC }, {@link DB::FETCH_BOTH  DB::FETCH_BOTH };
     * @return mixed массив результатов; affected_rows  в случает успешного "UPDATE"-одобного запроса; lastInsertedId в случае insert запроса.
     */
    static public function query( $query, $flags = null){
        //print_pre('==============================mysql:<b>'.$query.'</b>' );
        $r = self::$mysqli->real_query($query);
        if ( $r === true ){
            // process flags
            $data_process ='mysqli_store_result';
            if ($flags & DB::USE_RESULT) $data_process ='mysqli_use_result';
            //var_dump($data_process);
            $resulttype = MYSQLI_ASSOC;
            if ($flags & DB::FETCH_NUM ) $resulttype = MYSQLI_NUM;
            else if ($flags & DB::FETCH_BOTH ) $resulttype = MYSQLI_BOTH;

            $res =  call_user_func($data_process, self::$mysqli);
           if (is_object($res)){
                $data = array();
                while( ($d = $res->fetch_array($resulttype)) !== null){
                        $data[] = $d;
                }
            }
           else{
               if ( strcmp(strtolower(substr(ltrim($query),6)), 'insert'))
                   $data = self::$mysqli->insert_id;
               else  $data = self::$mysqli->affected_rows;
            }

            self::clearResultset($res);

            return $data;
        }
        else
            throw (new DBException(self::$mysqli->error,self::$mysqli->errno,$query));
    }// }}}

    // {{{ multiQuery
    /**
     * Выполнение нескольких SQL запросов.
     *
     * Основное отличии от {@link DB::query() DB::query()} состоит в том, что 
     * в параметр $query можно опдавать несколько запросов, разделенных ';'.
     * SQL конструкция 'delimiter' не поддерживается.
     *
     * <code>
     * $proc = <<<DDD
     * drop procedure if exists test;
     *
     * create procedure test()
     *
     *     select rand() as test;
     *
     * DDD;
     * DB::multiQuery($proc);
     * </code>
     *
     * Вид результата метода будет зависить от флагов.
     * Например: 
     * <code>
     * $proc = <<<DDD
     * select k as `key`, v as `value` from table limit 2;
     * update table set v = 10 where k = 12;
     * DDD;
     * $res = DB::multiQuery($proc, DB::DONT_COMPRESS_RESULTS | DB::SAVE_EMPTY_RESULTSET);
     * print_r($res);
     * </code>
     *
     *
     * Для результатов каждого запроса в результирующем массиве будет
     * <code> 
     *   Array
     *   (
     *       [0] => Array
     *       (
     *           [0] => Array
     *           (
     *               [key] => 1
     *               [value] => -1
     *           )
     *
     *           [1] => Array
     *           (
     *               [key] => 82
     *               [value] => 1
     *           )
     *  
     *       )
     *       [1] => NULL
     *
     *   )
     * </code>
     *
     * @throws DBException в случае возникновения ошибки в запросе
     * @param string $query SQL запрос
     * @param int $flags флаги для обработки результатов доступны следующие  флаги: {@link DB::USE_RESULT DB::USE_RESULT}, {@link DB::STORE_RESULT},
     *                      {@link DB::FETCH_NUM  DB::FETCH_NUM }, {@link DB::FETCH_ASSOC  DB::FETCH_ASSOC }, {@link DB::FETCH_BOTH  DB::FETCH_BOTH },
     *                      {@link DB::DONT_COMPRESS_RESULTS DB::DONT_COMPRESS_RESULTS}, {@link DB::SAVE_EMPTY_RESULTSET DB::SAVE_EMPTY_RESULTSET};
     *                      ;
     * @return mixed массив результатов;
    */
    static public function multiQuery( $query, $flags = 0){
        // flag processing
        
        $data_process ='mysqli_store_result';
        if ($flags & DB::USE_RESULT) $data_process ='mysqli_use_result';

        $resulttype = MYSQLI_ASSOC;
        if ($flags & DB::FETCH_NUM ) $resulttype = MYSQLI_NUM;
        else if ($flags & DB::FETCH_BOTH ) $resulttype = MYSQLI_BOTH;

        $compress = true;
        if ( $flags & DB::DONT_COMPRESS_RESULTS )  $compress = false;;

        $skipNull = true;
        if ( $flags & DB::SAVE_EMPTY_RESULTSET ){
            // Если указано DB::SAVE_EMPTY_RESULTSET,
            // то необходимо отменить компрессию результатов, 
            // иначе пустые запросы не отобразятся в результирующем массиве.
            $skipNull = false;
            $compress = false;
        }

        $mysql = &self::$mysqli;
        $i = 0;
        if ( $res = $mysql->multi_query($query) ){
            $data = array();
            do{
                $res =  call_user_func($data_process, $mysql);
                //echo $i." ==> ";var_dump($res);

                if ( $res ){
                    $subdata = array();
                    $sdcount = 0;
                    while( $tr =  $res->fetch_array($resulttype) )
                        $subdata[$sdcount ++]= $tr;
                    $res->close();
                    $data[] = $subdata;
                }
                // store_result возвращает FALSE для запросов без выбоки
                // запросы без результатов
                elseif ( $mysql->errno == 0 && !$skipNull){ 
                    $data[] = null;
                }
               $i ++;
            }while(  $mysql->next_result());

            if ( $mysql->errno != 0){
                throw (new DBException(self::$mysqli->error,self::$mysqli->errno,"subquery ".($i)."\r\n".$query));
            }

            //print_r($data);
            if ($compress){
                $cdata = array();
                for ($i =0, $c = count($data);  $i < $c; $i++){
                    if (is_array($data[$i])){
                        for ($j = 0, $cc = count($data[$i]); $j < $cc; $j++ )
                            $cdata[] =$data[$i][$j];
                    }
                }
                return $cdata;
            }

            return $data;   
        }
        else{ 
             throw (new DBException(self::$mysqli->error,self::$mysqli->errno,"subquery ".($i)."\r\n".$query));
        }
    }// }}}

    // {{{ getStmt
    /**
     * Фабричный метод для получения объекта DBStmt.
     *
     * Подробного описание см. {@link DBStmt::__construct() DBStmt::__construct()}.
     *
     * @throws DBException если строка параметров некорректна.
     * @param string $query SQL запрос подготовленного выражения
     * @param string $types перечесление типов параметров
     * @return object DBStmt
     */
	static public function getStmt($query, $types = null){
        $stmt = new DBStmt(self::getMysqli(), $query, $types);
        if ( self::$mysqli->errno)
            throw (new DBException(self::$mysqli->error,self::$mysqli->errno,$query));
        return $stmt;
    }// }}}

    // {{{ getTransaction
    /**
     * Фабричный метод для получения объекта транзакции
     *
     * 
     * @throws DBException если создание новой транзакции невозможно.
     * @return object DBTransaction
     */
    static public function getTransaction($useException = true, $lockDB = true ){
        //var_dump(self::$mysqli);
        if ((self::$mysqli instanceof DBMysqliFake) )
            throw new DBException('Only one Transaction my be executed at time');

        $t = new DBTransaction(self::getMysqli(), $useException);
        if ($lockDB ) self::$mysqli = new DBMysqliFake();

        return $t;
    }// }}}

    // {{{ closeTransaction
    /**
     * Закрытие транзакции
     *
     * @param DBTransaction 
     */
    static public function closeTransaction(DBTransaction $t ){
        if ( !(self::$mysqli instanceof mysqli) ) self::$mysqli = $t->getConnection();
    }// }}}

    // {{{ clearResultset
    /**
     * Отчистка соединения с базой данных.
     *
     * Соединение с БД может быть рассинхронизоровано. 
     * Наример, запрос вернул несколько наборов результов, а клиент забрал только 
     * часть из них и пытается выполнить следующий запрос. В такой ситуции сервер возвращает 
     * ошибку и ждет пока ползователь заберет все наборы результатов. 
     * Для решение проблемы необходимо забрать все наборы с сервера, чем и занимается данный метод.
     *
     * DB::query() и DB::multiQuery() оставляют соединение с базой "чистым". 
     * Метод будет полезен при использования объекта mysqli, полученного с помощью метода
     * {@link DB::getMysqli() DB::getMysqli()}.
     * 
     * @param mixed $result если передан объект mysqli_result, то выполнится его закрытие;
     */
    static public function clearResultset($result = null){
        if (is_object($result)) $result->free();    
        while(self::$mysqli->next_result()){
            if($result = self::$mysqli->use_result()){
                $result->free();
            }
        }
    } // }}}

    // {{{ function parseDSN($dsn)
    /**
     * Parse a data source name.
     *
     * Additional keys can be added by appending a URI query string to the
     * end of the DSN.
     *
     * The format of the supplied DSN is in its fullest form:
     * <code>
     *  driver://username:password@host:port/database?option=8&another=true
     *  driver://username:password@unix(/path/to/socket)/database?option=8&another=true
     * </code>
     *
     * Most variations are allowed:
     * <code>
     *  driver://username:password@host:port/database
     *  driver://username:password@host/database
	 *  driver://username:password@/database
	 *  driver:///database
	 *	...
	 *  username:password@/database
	 *  /database
	 * </code>
	 *
	 * Based on works  Tomas V.V.Cox <cox@idecnet.com>
     * @param   string  Data Source Name to be parsed
     *
     * @return  array   an associative array with the following keys:
     *  + driver:  Database backend used in PHP (mysqli, mysqlnd etc.)
     *  + protocol: Communication protocol to use (tcp, unix etc.)
     *  + host: Host specification (hostname)
	 *  + port: Port specification (port)
	 *  + socket: Socket path
     *  + database: Database to use on the DBMS server
     *  + username: User name for login
     *  + password: Password for login
     * @access  public
	*/
    static function parseDSN($dsn)
    {
        $parsed = array(
			'driver' => 'mysqli',
			'protocol' => 'tcp',
			'host' => 'localhost',
			'username' => '',
			'password' => '',
			'database' => '',
			'port'     => null,
			'socket'   => null,
		);

        //  driver
        if (($pos = strpos($dsn, '://')) !== false) {
            $parsed['driver'] = substr($dsn, 0, $pos);
            $dsn = substr($dsn, $pos + 3);
		}	
		// Get (if found): username and password
        // $dsn => username:password@protocol+hostspec/database
        if (($at = strrpos($dsn,'@')) !== false) {
            $str = substr($dsn, 0, $at);
            $dsn = substr($dsn, $at + 1);
            if (($pos = strpos($str, ':')) !== false) {
                $parsed['username'] = rawurldecode(substr($str, 0, $pos));
                $parsed['password'] = rawurldecode(substr($str, $pos + 1));
            } else {
                $parsed['username'] = rawurldecode($str);
            }
		}

		//print_pre($dsn);
        // $dsn = somehost:port/database
		if (preg_match('#^([^(:]+)?(:(\d+))?/(.+)$#', $dsn, $m)){
			if ($m[1] != '') $parsed['host'] = $m[1];
			if ($m[2] != '') $parsed['port'] = $m[2];
			$parsed['protocol'] = 'tcp';
			$dsn = $m[4];
		}else
        //$dsn = unix(/tmp/my.sock)/database
		if (preg_match('|^([^(]+)\((.*?)\)/(.+)$|', $dsn, $match)) {
			//print_pre($match);
            $proto       = $match[1];
            $proto_opts  = $match[2] ? $match[2] : false;
			$parsed['protocol'] = (!empty($proto)) ? $proto : 'tcp';
			if ($parsed['protocol'] == 'unix') 
				$parsed['socket'] = $proto_opts;
			else throw new DBException('Unsopported protocol: '.$proto);
			$dsn = $match[3];
		}        
		else throw new DBException('Unsopported DSN format: '.$dsn);

		// /database
		if (($pos = strpos($dsn, '?')) === false) $parsed['database'] = $dsn;
		// /database?param1=value1&param2=value2
		else {
			$parsed['database'] = substr($dsn, 0, $pos);
			$dsn = substr($dsn, $pos + 1);
			if (strpos($dsn, '&') !== false) $opts = explode('&', $dsn);
			// database?param1=value1
			else $opts = array($dsn);

			foreach ($opts as $opt) {
				list($key, $value) = explode('=', $opt);
				if (!isset($parsed[$key])) 
					// don't allow params overwrite
					$parsed[$key] = rawurldecode($value);
			}
		}
		return $parsed;
    }// }}}
} // }}}
?>
