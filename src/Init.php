<?php namespace Dariuszdusza\Phpmysqlxml;


/*
*	AUTHOR: DARIUSZ DUSZA
*
*	THIS IS SIMPLE PHP MYSQL XML DATABASE API
*	BASIC SETUP:
*		TABLE
*			ID  -> INTEGER
*			XML -> TEXT
*				ROOT ELEMENT OF XML -> TABLE NAME (STANDARD SETUP)
*
*	ARRAY[XML_TAG => VALUE] IS USED AS INPUT AND OUTPUT OF AN XML.
*
*	INDEX[FIELD1, FIELD2, ...] IS AN ARRAY OF XML TAGS THAT CORRESPOND WITH TABLE FILDS.
*
*	EXAMPLE:
*			using database credentials:
*				$PHP_MySQL_XML = new PHP_MySQL_XML_DB(SERVER_ADDRES, USER_NAME, USER_PASSWORD, DATABASE);
*			using mysqli object:
*				$PHP_MySQL_XML = new PHP_MySQL_XML_DB(mysqli);
*
*
*		INSERT XML VALUES INTO DATABSE:
*			CREATE XML ARRAY:
*				XML = array(XML_TAG => VALUE, ..., XML_TAG_n => VALUE_n);
*				$PHP_MySQL_XML->insert(XML,TABLE_NAME);
*
*
*		SELECT XML WITH SPECIFIC VALUES FROM DATABASE:
*			CREATE XML ARRAY:
*				XML = array(XML_TAG => VALUE, ..., XML_TAG_n => VALUE_n);
*				LIMIT_STRING IS AN SQL LIMIT STATEMENT FOR EXAMPLE: 'LIMIT 1'
*				$PHP_MySQL_XML->select(XML,TABLE_NAME, LIMIT_STRING);
*					CALLING METHOD select WITHOUT LIMIT_STRING IS POSSIBLE -> $PHP_MySQL_XML->select(XML,TABLE_NAME);
*
*
*		UPDATE
*
*
*		MOVE
*
*
*/


class Init
{
    public $db = null,
        $id_field_name = 'id',
        $xml_field_name = 'xml';
    //
    // if host is an object -> already initialized
    //
    public function __construct($host, $user = null, $password = null, $database = null, $charset = "utf8")
    {
        if (is_object($host)) {
            $this->db = $host;
        } else {
            $this->db = new \mysqli($host, $user, $password, $database);
            //
            if ($this->db->connect_error) {
                throw new \Exception('[DB ERROR][Connection to database failed ' . $this->db->connect_error . ']');
            }
            //
            $this->db->set_charset($charset);
        }
    }
    //
    //
    //
    public function __destruct()
    {
    }
    //
    // this function move records from one table to another
    //
    public function move($fields, $where = array(), $tables = array(), $index = array())
    {
        if (count($where) == 0)
            throw new \Exception('[MOVE ERROR][EMPTY WHERE ARRAY]');
        if (count($tables) == 0 || !isset($tables['from']) || !isset($tables['to']))
            throw new \Exception('[MOVE ERROR][EMPTY TABLE FROM TO ARRAY]');
        //
        // MOVE WITH INDEX
        //
        if (count($index) > 0) {
            //
            $_index = '';
            foreach ($index as $k => $v)
                $_index .= sprintf("%s = '%s' AND", $k, $v);
            //
            $q = sprintf("INSERT INTO %s (%s) SELECT %s FROM %s WHERE %s %s", $tables['to'], $fields, $fields, $tables['from'], $_index, $this->where($where));
            $d = sprintf("DELETE FROM %s WHERE %s %s", $tables['from'], $_index, $this->where($where));
        }
        //
        // MOVE WITHOUT INDEX
        //
        else {
            //
            $q = sprintf("INSERT INTO %s (%s) SELECT %s FROM %s WHERE %s", $tables['to'], $fields, $fields, $tables['from'], $this->where($where));
            $d = sprintf("DELETE FROM %s WHERE %s", $tables['from'], $this->where($where));
        }
        //
        mail('dariusz@dusza.org', 'move', $q . "\n" . $d);
        $result = $this->db->query($q);
        $result = $this->db->query($d);
    }
    //
    //
    //
    public function update($update = array(), $where = array(), $table_name = null, $index = array())
    {
        if (count($update) == 0)
            throw new \Exception('[UPDATE ERROR][EMPTY UPDATE ARRAY]');
        if (count($where) == 0)
            throw new \Exception('[UPDATE ERROR][EMPTY WHERE ARRAY]');
        if ($table_name == null)
            throw new \Exception('[UPDATE ERROR][EMPTY TABLE NAME]');
        //
        // UPDATE WITH INDEX
        //
        if (count($index) > 0) {
            //
            $_index = '';
            foreach ($index as $k => $v)
                $_index .= sprintf("%s = '%s' AND", $k, $v);
            //
            $q = sprintf("SELECT %s,%s FROM %s WHERE %s %s", $this->id_field_name, $this->xml_field_name, $table_name, $_index, $this->where($where));
        }
        //
        // UPDATE WITHOUT INDEX
        //
        else {
            //
            $q = sprintf("SELECT %s,%s FROM %s WHERE %s", $this->id_field_name, $this->xml_field_name, $table_name, $this->where($where));
        }
        mail('dariusz@dusza.org', 'update', $q);
        //
        //$result = $this->db->query(sprintf("SELECT %s,%s FROM %s WHERE %s", $this->id_field_name, $this->xml_field_name, $table_name, $this->where($where)));
        $result = $this->db->query($q);
        //
        while ($row = $result->fetch_array()) {
            //
            $this->db->query(sprintf("UPDATE %s SET %s='%s' WHERE %s='%s'", $table_name, $this->xml_field_name, $this->update_xml($this->xml_to_array($row[$this->xml_field_name]), $update, $table_name), $this->id_field_name, $row[$this->id_field_name]));
        }
    }
    //
    //
    //
    private function update_xml($base = array(), $update = array(), $root = null)
    {
        foreach ($update as $tag => $value)
            $base[$tag] = $value;
        //
        return $this->array_to_xml($base, $root);

    }
    //
    //
    //
    public function add($add = array(), $where = array(), $table_name = null, $index = array())
    {
        if (count($add) == 0)
            throw new \Exception('[ADD ERROR][EMPTY ADD ARRAY]');
        if (count($where) == 0)
            throw new \Exception('[ADD ERROR][EMPTY WHERE ARRAY]');
        if ($table_name == null)
            throw new \Exception('[ADD ERROR][EMPTY TABLE NAME]');
        //
        // ADD WITH INDEX
        //
        if (count($index) > 0) {
            //
            $_index = '';
            foreach ($index as $k => $v)
                $_index .= sprintf("%s = '%s' AND", $k, $v);
            //
            $q = sprintf("SELECT %s,%s FROM %s WHERE %s %s", $this->id_field_name, $this->xml_field_name, $table_name, $_index, $this->where($where));
        }
        //
        // ADD WITHOUT INDEX
        //
        else {
            //
            $q = sprintf("SELECT %s,%s FROM %s WHERE %s", $this->id_field_name, $this->xml_field_name, $table_name, $this->where($where));
        }
        //
        $result = $this->db->query($q);
        //
        while ($row = $result->fetch_array()) {
            //
            $this->db->query(sprintf("UPDATE %s SET %s='%s' WHERE %s='%s'", $table_name, $this->xml_field_name, $this->add_to_xml($this->xml_to_array($row[$this->xml_field_name]), $add, $table_name), $this->id_field_name, $row[$this->id_field_name]));
        }
    }
    //
    //
    //
    private function add_to_xml($base = array(), $add = array(), $root = null)
    {
        return $this->array_to_xml(($add + $base), $root);
    }
    //
    //
    //
    public function select($xml_tags_values = array(), $table_name = null, $index = array(), $limit = null, $results = array())
    {
        if (count($xml_tags_values) == 0)
            throw new \Exception('[SELECT ERROR][EMPTY ARRAY]');
        if ($table_name == null)
            throw new \Exception('[SELECT ERROR][EMPTY TABLE NAME]');
        //
        // SELECT WITH INDEX
        //
        if (count($index) > 0) {
            //
            $_index = '';
            foreach ($index as $k => $v)
                $_index .= sprintf("%s = '%s' AND", $k, $v);
            //
            $q = sprintf("SELECT %s FROM %s WHERE %s %s %s", $this->xml_field_name, $table_name, $_index, $this->where($xml_tags_values), $limit);
        }
        //
        // SELECT WITHOUT INDEX
        //
        else {
            //
            $q = sprintf("SELECT %s FROM %s WHERE %s %s", $this->xml_field_name, $table_name, $this->where($xml_tags_values), $limit);
        }
        //
        $result = $this->db->query($q);
        //
        if ($result) {
            while ($row = $result->fetch_array())
                try {
                    $results[] = $this->xml_to_array($row[$this->xml_field_name]);
                } catch (\Exception $e) {
                    throw new \Exception('[SELECT ERROR]' . $e->getMessage());
                }
            //
            return $results;
        } else {
            return null;
        }
        /*
        if(count($results) > 0)
            return $results;
        else
            throw new \Exception('[EMPTY RESULT]');
        */
    }
    //
    // $index are tags names value comes from $xml -> at this point only one index allowed
    //
    public function insert($xml = array(), $table_name = null, $index = array())
    {
        if (count($xml) == 0)
            throw new \Exception('[INSERT ERROR][EMPTY XML ARRAY]');
        //
        if ($table_name == null)
            throw new \Exception('[INSERT ERROR][EMPTY TABLE NAME]');
        //
        // INSERT WITH INDEX
        //
        if (count($index) > 0) {
            //
            // INDEX ERROR -> ADD THIS MOMENT SUPPORT FOR 1 INDEX ELEMENT
            //
            if (count($index) > 1)
                throw new \Exception('[INSERT ERROR][INDEX ERROR MORE THAN 1 NOT SUPPORTED]');
            //
            if (!($_index = $this->_index($xml, $index)))
                throw new \Exception('[INSERT ERROR][INDEX INCOMPATIBLE WITH XML ARRAY][' . $this->array_to_xml($index) . ']');
            //
            // ADD XML ELEMENT TO $_index
            //
            $_index[] = array($this->xml_field_name, $this->db->real_escape_string($this->array_to_xml($xml, $table_name)));
            //
            $prepare = sprintf("INSERT INTO %s(%s) VALUES (%s)", $table_name, $this->_index_columns($_index), rtrim(str_repeat('?,', count($_index)), ','));
            //
            if (!($stmt = $this->db->prepare($prepare)))
                throw new \Exception('[INSERT ERROR][Prepare failed: (' . $this->db->errno . ')' . $this->db->error . ']');
            //
            // CREATE BIND ARRAY WITH REFERENCES
            //
            $_bind = array(str_repeat('s', count($_index)));
            foreach ($_index as $i => $a)
                $_bind[] =& $a[1];

            //
            $ref = new \ReflectionClass('mysqli_stmt');
            $method = $ref->getMethod('bind_param');
            $method->invokeArgs($stmt, $_bind);
            //
            if (!$stmt->execute())
                throw new \Exception('[INSERT ERROR][Execute failed: (' . $this->db->errno . ')' . $this->db->error . ']');
        }
        //
        // INSERT WITHOUT INDEX
        //
        else {
            //
            $prepare = sprintf("INSERT INTO %s(%s) VALUES (?)", $table_name, $this->xml_field_name);
            if (!($stmt = $this->db->prepare($prepare)))
                throw new \Exception('[INSERT ERROR][Prepare failed: (' . $this->db->errno . ')' . $this->db->error . ']');
            //
            if (!$stmt->bind_param("s", $this->db->real_escape_string($this->array_to_xml($xml, $table_name))))
                throw new \Exception('[INSERT ERROR][Binding parameters failed: (' . $this->db->errno . ')' . $this->db->error . ']');
            //
            if (!$stmt->execute())
                throw new \Exception('[INSERT ERROR][Execute failed: (' . $this->db->errno . ')' . $this->db->error . ']');
        }
    }
    //
    //
    //
    private function _index_columns($index, $c = null)
    {
        foreach ($index as $i => $a)
            $c .= $a[0] . ',';
        return rtrim($c, ',');
    }
    //
    //
    //
    private function _index($xml, $index, $_index = array())
    {
        foreach ($index as $i)
            if (isset($xml[$i]))
                $_index[] = array(0 => $i, 1 => $xml[$i]);
        if (count($_index) > 0)
            return $_index;
        else
            return false;
    }
    //
    //
    //
    private function array_to_xml($array, $root = null, $out = null)
    {
        $xml_replace_charaters = array('<' => '_', '>' => '_', '&' => ' and ', '"' => '\'');
        foreach ($array as $k => $v)
            $out .= "<$k>" . (is_array($v) ? $this->array_to_xml($v) : strtr(trim($v), $xml_replace_charaters)) . "</$k>";
        return ($root == null) ? $out : sprintf("<%s>%s</%s>", $root, $out, $root);
    }
    //
    //
    //
    private function where($xml_tags = array(), $locate = null)
    {
        foreach ($xml_tags as $xml_tag => $value) {
            $locate .= $this->locate_xml_query($xml_tag, $value);
        }
        return substr($locate, 0, -4);
    }
    //
    //
    //
    private function locate_xml_query($xml_tag, $value)
    {
        // DON'T FORGET ABOUT SPACE PRECEDING QUERY!!!
        return sprintf(" SUBSTRING(%s, LOCATE('<%s>', %s)+LENGTH('<%s>'), LOCATE('</%s>', %s, LOCATE('<%s>', %s))-LOCATE('<%s>', %s)-LENGTH('<%s>')) = '%s' AND", $this->xml_field_name, $xml_tag, $this->xml_field_name, $xml_tag, $xml_tag, $this->xml_field_name, $xml_tag, $this->xml_field_name, $xml_tag, $this->xml_field_name, $xml_tag, $value);
    }
    //
    //
    //
    private function xml_to_array($xml)
    {
        try {
            return $this->xml_object_to_array(new \SimpleXMLElement($xml));
        }
        catch (\Exception $e) {
            throw new \Exception('[ERROR][CONVERSION XML TO ARRAY][' . $e->getMessage() . ']');
        }
    }
    //
    //
    //
    private function xml_object_to_array($xmlObject, $out = array())
    {
        foreach ((array)$xmlObject as $index => $node) {
            if (is_object($node) && $node->count() > 0) {
                $out[$index] = xml_to_array($node);
            } elseif (is_object($node) && $node->count() == 0) {
                $out[$index] = '';
            } else
                $out[$index] = $node;
        }
        return $out;
    }
}

?>
