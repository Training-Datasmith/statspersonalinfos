<?php

class StatsExportInterrupt extends Exception
{
    public $exportPayload;

    public function __construct(array $payload)
    {
        $this->exportPayload = $payload;
        parent::__construct('csvExport');
    }
}

class Context
{
  private static $instance;
  public $language;
  public $employee;

  public function __construct()
  {
    $this->language = new stdClass();
    $this->language->id = 1;
    $this->employee = new stdClass();
    $this->employee->id = 1;
  }

  public static function getContext()
  {
    if (self::$instance === null) {
      self::$instance = new Context();
    }

    return self::$instance;
  }

  public static function reset()
  {
    self::$instance = null;
  }
}

class Shop
{
    const SHARE_CUSTOMER = 'share_customer';
    const SHARE_ORDER = 'share_order';
    const CONTEXT_SHOP = 1;

    private static $contextShopId = 1;
    private static $contextShopGroupId = 1;
    private static $shareCustomer = false;

    public static function reset()
    {
        self::$contextShopId = 1;
        self::$contextShopGroupId = 1;
        self::$shareCustomer = false;
    }

    public static function setContextShopId($shopId)
    {
        self::$contextShopId = (int) $shopId;
        if ((int) $shopId === 2) {
            self::$contextShopGroupId = 1;
        } elseif ((int) $shopId === 3) {
            self::$contextShopGroupId = 2;
        } else {
            self::$contextShopGroupId = 1;
        }
    }

    public static function setShareCustomer($enabled)
    {
        self::$shareCustomer = (bool) $enabled;
    }

    public static function getContextShopID()
    {
        return self::$contextShopId;
    }

    public static function getContextShopGroupID()
    {
        return self::$contextShopGroupId;
    }

    public static function getContext()
    {
        return self::CONTEXT_SHOP;
    }

    public static function addSqlRestriction($share = false, $alias = null)
    {
        if ($alias) {
            $alias .= '.';
        } else {
            $alias = '';
        }

        if ($share == self::SHARE_CUSTOMER && self::getContext() == self::CONTEXT_SHOP && self::$shareCustomer) {
            return ' AND ' . $alias . 'id_shop_group = ' . (int) self::$contextShopGroupId . ' ';
        }

        $ids = array(self::$contextShopId);

        return ' AND ' . $alias . 'id_shop IN (' . implode(', ', $ids) . ') ';
    }
}

class Tools
{
    private static $values = array();

    public static function reset()
    {
        self::$values = array();
    }

    public static function setValues(array $values)
    {
        self::$values = $values;
    }

    public static function getValue($key, $default = false)
    {
        if (array_key_exists($key, self::$values)) {
            return self::$values[$key];
        }

        return $default;
    }

    public static function safeOutput($string, $html = false)
    {
        if ($html) {
            return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
        }

        return htmlspecialchars(strip_tags($string), ENT_QUOTES, 'UTF-8');
    }
}

class Db
{
    private static $instance;
    private $link;

    private function __construct()
    {
    }

    public static function getInstance($master = true)
    {
        if (self::$instance === null) {
            self::$instance = new Db();
            self::$instance->connect();
        }

        return self::$instance;
    }

    public static function resetConnection()
    {
        if (self::$instance !== null && self::$instance->link) {
            @mysqli_close(self::$instance->link);
        }
        self::$instance = null;
    }

    private function connect()
    {
        $host = getenv('STATS_DB_HOST') ? getenv('STATS_DB_HOST') : '127.0.0.1';
        $port = (int) (getenv('STATS_DB_PORT') ? getenv('STATS_DB_PORT') : 3306);
        $user = getenv('STATS_DB_USER') ? getenv('STATS_DB_USER') : 'stats';
        $password = getenv('STATS_DB_PASSWORD') ? getenv('STATS_DB_PASSWORD') : 'stats';
        $database = getenv('STATS_DB_NAME') ? getenv('STATS_DB_NAME') : 'stats_personal_infos_test';

        $this->link = @mysqli_connect($host, $user, $password, $database, $port);
        if (!$this->link) {
            throw new RuntimeException('Database connection failed: ' . mysqli_connect_error());
        }

        if (!mysqli_query($this->link, 'SET NAMES utf8mb4')) {
            throw new RuntimeException('SET NAMES utf8mb4 failed');
        }

        if (!mysqli_query($this->link, "SET SESSION sql_mode = ''")) {
            throw new RuntimeException('SET sql_mode failed');
        }
    }

    public function getLink()
    {
        return $this->link;
    }

    public function executeS($sql)
    {
        $result = mysqli_query($this->link, $sql);
        if ($result === false) {
            throw new RuntimeException('SQL error: ' . mysqli_error($this->link) . ' in ' . $sql);
        }

        if ($result === true) {
            return array();
        }

        $rows = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        mysqli_free_result($result);

        return $rows;
    }

    public function getRow($sql)
    {
        $rows = $this->executeS($sql);
        if (count($rows) === 0) {
            return false;
        }

        return $rows[0];
    }

    public function getValue($sql)
    {
        $row = $this->getRow($sql);
        if ($row === false) {
            return false;
        }

        return reset($row);
    }
}

class Module
{
    public $context;
    public $name;
    public $tab;
    public $version;
    public $author;
    public $need_instance;
    public $displayName;
    public $description;
    public $ps_versions_compliancy;
    public $hookCalls = array();
    public $parentInstallResult = true;

    public function __construct()
    {
        $this->context = Context::getContext();
    }

    public function trans($id, array $parameters = array(), $domain = null, $locale = null)
    {
        return $id;
    }

    public function install()
    {
        return (bool) $this->parentInstallResult;
    }

    public function registerHook($name)
    {
        $this->hookCalls[] = array('register', $name);

        return true;
    }

    public function unregisterHook($name)
    {
        $this->hookCalls[] = array('unregister', $name);

        return true;
    }
}

abstract class ModuleGraph extends Module
{
    protected $_values = array();
    protected $_legend = array();
    protected $_titles = array('main' => null, 'x' => null, 'y' => null);
    public $engineCalls = array();
    public $csvCalls = array();

    abstract protected function getData($layers);

    public function engine($params)
    {
        $this->engineCalls[] = $params;

        $option = isset($params['option']) ? $params['option'] : '';

        return 'GRAPH:' . $option;
    }

    public function csvExport($datas)
    {
        $this->csvCalls[] = $datas;
        throw new StatsExportInterrupt($datas);
    }
}
