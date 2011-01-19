<?php
###################################################
##              BEGIN CLASS HERE                 ##
###################################################

class Navigation
{
    var $dbconnection, $dbname, $limit, $execute, $query;

    // constructor
    function Navigation() {
      $this->offset = 'offset';
      $this->get_ignore = array('module');
    }

    // database connection
    function connection($dbhost,$dbuser,$dbpass)
    {
        @$this->conn = mysql_connect($dbhost,$dbuser,$dbpass) or die("Error connectiong to database $dbhost");
        @mysql_select_db($this->dbname) or die ("Cannot connect to $this->dbname database");
    }

    // mysql query execution
    function execute($query)
    {
        $GLOBALS[$this->offset] = (intval($_GET[$this->offset]) <= 0) ? 0 : $_GET[$this->offset];

        preg_match('/FROM (.*?)( .*|$)/i', $query, $matches);
        preg_match('/WHERE ((.*)\s?=\s?(\'(.*?)\'|"(.*?)"|([^\s]+)))($| (AND|OR) (.*)\s?=\s?(\'(.*?)\'|"(.*?)"|([^\s]+))(\s(.*?))?)?/i', $query, $matches2);

        $row = mysql_fetch_array(mysql_query("SELECT COUNT(*) AS _count FROM {$matches[1]} {$matches2[0]}")) or die ("Error fetching information [".mysql_error()."]...");
        $this->total_result = $row['_count'];

        $query .= " LIMIT " . $GLOBALS[$this->offset] . ", $this->limit";
        @$this->sql_result = mysql_query($query) or die ("Error fetching information + limit [".mysql_error()."]...");
        $this->num_pages = ceil($this->total_result/$this->limit);
    }

    ######################
    ##  PAGES NUMBERS   ##
    ######################
    ##
    ##  $class->show_num_pages('<< previuos','next >>','separator','class=\"myclass\"');
    ##
    function show_num_pages($rew = '', $fwd = '', $separator = '|', $objClass = '')
    {
        if($this->num_pages > 1)
        {
            ## searching for http_get_vars
            foreach($GLOBALS[HTTP_GET_VARS] as $_get_name => $_get_value)
            {
                if($_get_name != $this->offset && !in_array($_get_name, $this->get_ignore)) $this->_get_vars .= "&amp;$_get_name=$_get_value";
            }
            $this->successivo = $GLOBALS[$this->offset] + $this->limit;
            $this->precedente = $GLOBALS[$this->offset] - $this->limit;
            $this->theClass = $objClass;
            if(!empty($rew))
            {
                $GLOBALS[$this->offset] > 0 ? print "<a href='?$this->offset=$this->precedente$this->_get_vars' $this->theClass> $rew</a> $separator " : print "$rew $separator ";
            }

            ## showing pages
            if($this->show_pages_number || !isset($this->show_pages_number))
            {
                for($this->a = 1; $this->a <= $this->num_pages; $this->a++)
                {
                    $this->theNext = ($this->a-1)*$this->limit;
                    if($this->theNext != $GLOBALS[$this->offset])
                    {
                        print "<a href='?$this->offset=$this->theNext$this->_get_vars' $this->theClass>";
                        if($this->number_type == 'alpha') print chr(64 + ($this->a));
                            else print $this->a;
                        print "</a>";
                    } else {
                        if($this->number_type == 'alpha') print chr(64 + ($this->a));
                            else print $this->a;
                    }
                    $this->a < $this->num_pages ? print " $separator " : print " ";
                }
            }
            $this->theNext = $GLOBALS[$this->offset] + $this->limit;
            if(!empty($fwd))
            {
                $GLOBALS[$this->offset] + $this->limit < $this->total_result ? print "$separator <a href='?$this->offset=$this->successivo$this->_get_vars' $this->theClass>$fwd</a>" : print "$separator $fwd";
            }
            return(true);
        }
        return(false);
    }

    ##################################
    ## showing offest range results ##
    ##################################
    ##
    ##  $class->show_offset_range('Previous','Next','-');
    ##
    ##
    function show_offset_range($rew = '', $fwd = '', $separator = '|')
    {
        if($this->num_pages > 1)
        {
            foreach($GLOBALS[HTTP_GET_VARS] as $_get_name => $_get_value)
            {
                if($_get_name != $this->offset && !in_array($_get_name, $this->get_ignore)) $this->_get_vars .= "&amp;$_get_name=$_get_value";
            }
            $this->successivo = $GLOBALS[$this->offset] + $this->limit;
            $this->precedente = $GLOBALS[$this->offset] - $this->limit;
            ## previous num results...
            if(!empty($rew))
            {
                $GLOBALS[$this->offset] > 0 ? print "<a href='?$this->offset=$this->precedente$this->_get_vars' $this->theClass> $rew $this->limit</a> $separator " : print "$rew $this->limit $separator ";
            }

            ## showing numbers...
            for($this->a = 1; $this->a <= $this->num_pages; $this->a++)
            {
                $this->theNext = ($this->a-1)*$this->limit;
                $this->toShow = $this->limit*$this->a - $this->limit + 1 . " - " . $this->limit*$this->a;
                if(($this->limit*$this->a - $this->limit + 1) == $this->total_result)
                {
                    $this->theLast = $this->total_result;
                } else {
                    $this->theLast = $this->limit*$this->a - $this->limit + 1 . " - " . $this->total_result;
                }
                if($this->theNext != $GLOBALS[$this->offset])
                {
                    print "<a href='?$this->offset=$this->theNext$this->_get_vars' $this->theClass>";
                    $this->a == $this->num_pages ? print $this->theLast : print $this->toShow;
                    print "</a>";
                } else {
                    $this->a == $this->num_pages ? print $this->theLast : print $this->toShow;
                }
                $this->a < $this->num_pages ? print " $separator " : print " ";
            }

            ## showing next num results...
            $this->theNext = $GLOBALS[$this->offset] + $this->limit;
            if(!empty($fwd))
            {
                $GLOBALS[$this->offset] + $this->limit < $this->total_result ? print "$separator <a href='?$this->offset=$this->successivo$this->_get_vars' $this->theClass>$fwd $this->limit</a>" : print "$separator $fwd $this->limit";
            }
            return(true);
        }
        return(false);
    }


    ## show info for the offset
    function show_info()
    {
        print "<b>" . $this->total_result . "</b> total results, ";
        $_from = $GLOBALS[$this->offset] + 1;
        $GLOBALS[$this->offset] + $this->limit >= $this->total_result ? $_to = $this->total_result : $_to = $GLOBALS[$this->offset] + $this->limit;
        print "showing results from <b>" . $_from . "</b> to <b>" . $_to . "</b>.";
    }
}
## END CLASS ##
?>