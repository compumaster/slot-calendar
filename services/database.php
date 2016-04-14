<?php
class Database
{
    public $totalExecutionTime = 0;
    public $totalExecutionCount = 0;
    public $queries = array();
    private static $instance;
    private $db;

    // A private constructor; prevents direct creation of object
    private function __construct()
    {
        if(DOMAINTENANCE == 1) die("Site is under maintenance");

        $this->db = new mysqli(HOSTNAME_DBCONN, USERNAME_DBCONN, PASSWORD_DBCONN, DATABASE_DBCONN) or die(trigger_error($db->error(), E_USER_ERROR));
        $a = $this->db->query("SET NAMES 'utf8'");
    }

    /**
     * @return Database
     */
    public static function singleton()
    {

        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }

        return self::$instance;
    }

    function getmicrotime() {
        // split output from microtime() on a space
        list($usec, $sec) = explode(" ",microtime());

        // append in correct order
        return ((float)$usec + (float)$sec);
    }

    public function query($query)
    {
        $start = $this->getmicrotime();
        $backtrace = debug_backtrace();
        if (count($backtrace)>=3)
        {
            $fn = $backtrace[1]['function'] . $backtrace[2]['function'];
            $query = "/* $fn */ " . $query;
        }
        else if (count($backtrace)>=2)
        {
            $fn = $backtrace[1]['function'];
            $query = "/* $fn */ " . $query;
        }

        $result = $this->db->query($query) or die(mysql_error());

        $diff = $this->getmicrotime() - $start;
        $this->totalExecutionTime += $diff;
        $this->totalExecutionCount += 1;
        $this->queries[] = $query . ": " . $diff;

        return $result;
    }

    public function querySingle($query)
    {
        $result = $this->query($query);
        if (!$result){
            return null;
        }

        if ($row = $result->fetch_assoc()){
            return $row[0];
        }

        return null;
    }

    public function num_rows($result)
    {
        return $result->num_rows;
    }

    /**
     * @param mysqli_result $result
     * @return array
     */
    public function fetch_assoc($result)
    {
        if (!isset($result))
        {
            trigger_error(print_r(debug_backtrace(),true), E_USER_WARNING);
            die();
        }

        return $result->fetch_assoc();
    }

    public function affected_rows()
    {
        return $this->db->affected_rows;
    }

    public function real_escape_string (&$variable)
    {
        $variable = $this->db->real_escape_string($variable);
    }

    public function insert_id()
    {
        return  $this->db->insert_id;
    }

    public function fetch_full_result_array($query)
    {
        $result = $this->query($query);
        $table_result=array();
        $r=0;
        while($row = $result->fetch_assoc()) {
            $arr_row=array();
            $c=0;
            while ($c < $result->field_count) {
                $col = $result->fetch_field_direct($c);
                $arr_row[$col -> name] = $row[$col -> name];
                $c++;
            }

            $table_result[$r] = $arr_row;
            $r++;
        }

        return $table_result;
    }
}