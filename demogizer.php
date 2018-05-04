<?php

/**
 * Demogizer
 */

/* configuration variables */
define("DB_MODE", "MySql"); //MySql or Sqlite
define("DB_TABLE_PREFIX", ""); 
define("DB_SERVER", "localhost");
define("DB_DATABASE", "database_name");
define("DB_USER", "database_user");
define("DB_PASSWORD", "database_password");
define("DB_FILEPATH", "");
define("ROOT", dirname(__FILE__));
define("DB_FILE", "files/db.sql");
define("SOURCE_SITE_PATH", "files/site/");
define("DESTINATION_SITE_PATH", "/var/www/html/");

$dz = new Demogizer();
$dz->ClearDemo();

class Demogizer
{
	var $server = '';
	var $database = '';
	var $user = '';
	var $pass = '';
	var $prefix = '';
	var $mode = '';
	var $filepath = '';
    var $sourcesitepath = '';
    var $destinationsitepath = '';
    var $dbfile = '';
	public function __construct($server = '', $database = '', $user = '', $pass = '', $prefix = '', $mode = '', $filepath = '', $dbfile = '', $sourcesitepath = '', $destinationsitepath = '') 
    {
		$this->VerifyConstants();
		if ($server == "")
		{
			$this->LoadConstants();
		}
		if ($server != "")
		{
			$this->server = $server;
		}
		if ($database != "")
		{
			$this->database = $database;
		}
		if ($user != "")
		{
			$this->user = $user;
		}
		if ($pass != "")
		{
			$this->pass = $pass;
		}
		if ($prefix != "")
		{
			$this->prefix = $prefix;
		}
		if ($mode != "")
		{
			$this->mode = $mode;
		}
		if ($filepath != "")
		{
			$this->filepath = $filepath;
		}
        if ($dbfile != "")
		{
			$this->dbfile = $dbfile;
		}
        if ($sourcesitepath != "")
		{
			$this->sourcesitepath = $sourcesitepath;
		}
        if ($destinationsitepath != "")
		{
			$this->destinationsitepath = $destinationsitepath;
		}
	}
	private function VerifyConstants() 
	{
		if (!defined("DB_TABLE_PREFIX"))
		{
			@define("DB_TABLE_PREFIX", "");
		}
		if (!defined("DB_MODE"))
		{
			@define("DB_MODE", "");
		}
		if (!defined("DB_FILEPATH"))
		{
			@define("DB_FILEPATH", "");
		}
		if (!defined("DB_DATABASE"))
		{
			@define("DB_DATABASE", "");
		}
		if (!defined("DB_USER"))
		{
			@define("DB_USER", "");
		}
		if (!defined("DB_PASSWORD"))
		{
			@define("DB_PASSWORD", "");
		}
		if (!defined("DB_SERVER"))
		{
			@define("DB_SERVER", "");
		}
        if (!defined("DB_FILE"))
		{
			@define("DB_FILE", "");
		}
        if (!defined("SOURCE_SITE_PATH"))
		{
			@define("SOURCE_SITE_PATH", "");
		}
        if (!defined("DESTINATION_SITE_PATH"))
		{
			@define("DESTINATION_SITE_PATH", "");
		}
	}
	private function LoadConstants() 
	{
		$this->server = DB_SERVER;
		$this->database = DB_DATABASE;
		$this->user = DB_USER;
		$this->pass = DB_PASSWORD;
		$this->prefix = DB_TABLE_PREFIX;
		$this->mode = DB_MODE;
		$this->filepath = DB_FILEPATH;
        $this->dbfile = DB_FILE;
        $this->sourcesitepath = SOURCE_SITE_PATH;
        $this->destinationsitepath = DESTINATION_SITE_PATH;
	}
    public function ClearDemo() 
    {
        echo 'Clearing demo...' . '<br>';
        $this->ClearDatabase();
        $this->RestoreDatabase();
        $this->ClearDirectory();
        $this->RestoreDirectory();
        echo 'Demo restored!' . '<br>';
    }
    public function ClearDatabase() 
    {
        echo 'Clearing database...' . '<br>';
        $sql = "SELECT (concat('DROP TABLE IF EXISTS `', table_name, '`;')) as `sql`
FROM information_schema.tables
WHERE table_schema = '$this->database';";
        $results = $this->Execute($sql);
        foreach ($results as $result)
        {
            $sql = $result['sql'];
            $this->Execute($sql);
        }
        echo 'Database cleared!' . '<br>';
    }
    public function RestoreDatabase() 
    {
        echo 'Restoring database...' . '<br>';
        $command = "mysql -u$this->user -p$this->pass -h $this->server -D $this->database < " . ROOT . "/$this->dbfile";
        $output = shell_exec($command);
        echo 'Database restored!' . '<br>';
    }
    public function ClearDirectory() 
    {
        echo 'Clearing directory...' . '<br>';
        $this->rrmdir($this->destinationsitepath);
        echo 'Directory cleared!' . '<br>';
    }
    private function rrmdir($dir) 
    { 
        if (is_dir($dir)) 
        { 
            $objects = scandir($dir); 
            foreach ($objects as $object) 
            { 
                if ($object != "." && $object != "..") 
                { 
                    if (is_dir($dir."/".$object))
                    {
                        $this->rrmdir($dir."/".$object);
                    }
                    else
                    {
                        unlink($dir."/".$object); 
                    } 
                }
            } 
            rmdir($dir);
        }
    }
    public function RestoreDirectory() 
    {
        echo 'Restoring directory...' . '<br>';
        mkdir($this->destinationsitepath);
        $command = "rsync -avz $this->sourcesitepath $this->destinationsitepath";
        $output = shell_exec($command);
        echo 'Directory restored!' . '<br>';
    }
    public function Execute($sql)
	{
		if ($this->mode == "MySql")
		{
			$dsn = "mysql:dbname=" . $this->database . ";host=" . $this->server;
			try
			{
				$db = new PDO($dsn, $this->user, $this->pass);
				//execute generic statement 
				$result = $db->query($sql);
				if ($db->errorCode() != "" && $db->errorCode() != "0") 
				{
				    $errors = $db->errorInfo();
				    error_log("\nFILE: \"db.php\"\nSTATEMENT: \"" . $sql . "\"\nMETHOD: \"Database->Execute\"\nERROR: \"" . $errors[2] . "\"", 0);
				    return "Failed: Unable to execute statement! " . $errors[2];
				}
				$arrResults = array();
				while ($row = $result->fetch(PDO::FETCH_ASSOC))
				{
					$arrResults[] = $row;
				}
				return $arrResults;
			}
			catch (PDOException $e)
			{
				return 'Failed: Connection failed!';// . $e->getMessage();
			}
		}
		else if ($this->mode == "Sqlite")
		{
			$dsn = "sqlite:" . $this->filepath;
			try
			{
				$db = new PDO($dsn);
				//execute generic statement 
				$result = $db->query($sql);
				//echo $db->errorInfo();
				if ($db->errorCode() != "" && $db->errorCode() != "0") 
				{
				    $errors = $db->errorInfo();
				    error_log("\nFILE: \"db.php\"\nSTATEMENT: \"" . $sql . "\"\nMETHOD: \"Database->Execute\"\nERROR: \"" . $errors[2] . "\"", 0);
				    return "Failed: Unable to execute statement! " . $errors[2];
				}
				$arrResults = array();
				while ($row = $result->fetch(PDO::FETCH_ASSOC))
				{
					$arrResults[] = $row;
				}
				return $arrResults;
			}
			catch (PDOException $e)
			{
				return 'Failed: Connection failed!';// . $e->getMessage();
			}
		}
	}
}