<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core;

use ADIOS\Core\Db\DataType;
use ADIOS\Core\DB\Query;
use ADIOS\Core\Exceptions\DBException;
use ADIOS\Core\Exceptions\RecordDeleteException;
use ADIOS\Core\Exceptions\RecordSaveException;
use ADIOS\Core\ViewsWithController\Form;
use ADIOS\Core\ViewsWithController\Table;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use ReflectionClass;

/**
 * Core implementation of database model. Extends from Eloquent's model and adds own
 * functionalities.
 */
class Model extends \Illuminate\Database\Eloquent\Model
{
  /**
   * ADIOS model's primary key is always 'id'
   *
   * @var string
   */
  protected $primaryKey = 'id';

  protected $guarded = [];

  protected ?Builder $eloquentQuery = null;

  /**
   * ADIOS model does not use time stamps
   *
   * @var bool
   */
  public $timestamps = false;

  /**
   * Language dictionary for the context of the model
   *
   * @var array
   */
  // public $languageDictionary = [];

  /**
   * Full name of the model. Useful for getModel() function
   */
  public string $fullName = "";

  /**
   * Short name of the model. Useful for debugging purposes
   */
  public string $shortName = "";

  /**
   * Reference to ADIOS object
   *
   * @var mixed
   */
  public ?Loader $app = NULL;

  /**
   * Shorthand for "global table prefix"
   */
  public ?string $gtp = "";

  /**
   * Name of the table in SQL database. Used together with global table prefix.
   */
  public string $sqlName = '';

  /**
   * URL base for management of the content of the table. If not empty, ADIOS
   * automatically creates URL addresses for listing the content, adding and
   * editing the content.
   */
  public string $urlBase = "";

  public ?array $crud = [];

  /**
   * SQL-compatible string used to render displayed value of the record when used
   * as a lookup.
   */
  public ?string $lookupSqlValue = NULL;

  /**
   * If set to TRUE, the SQL table will not contain the ID autoincrement column
   */
  public bool $isJunctionTable = FALSE;


  public ?array $tableParams = NULL;
  public ?array $formParams = NULL;

  public string $tableEndpoint;
  public string $formEndpoint;


  /**
   * If set to TRUE, the SQL table will contain the `record_info` column of type JSON
   */
  public bool $storeRecordInfo = FALSE;

  var $pdo;
  var $searchController;

  /**
   * Property used to store original data when recordSave() method is calledmodel
   *
   * @var mixed
   */
  var $recordSaveOriginalData = NULL;
  protected string $fullTableSqlName = "";

  private static ?array $allItemsCache = NULL;

  public ?array $junctions = [];


  /**
   * Creates instance of model's object.
   *
   * @param mixed $appOrAttributes
   * @param mixed $eloquentQuery
   * @return void
   */
  public function __construct($appOrAttributes = NULL, $eloquentQuery = NULL)
  {
    if (is_array($appOrAttributes) && isset($appOrAttributes['gtp'])) {
      $this->gtp = $appOrAttributes['gtp'];
    }

    if (empty($this->fullTableSqlName)) {
      $this->fullTableSqlName = (empty($this->gtp) ? '' : $this->gtp . '_') . $this->sqlName;
    }

    if (empty($this->table)) {
      $this->table = (empty($this->gtp) ? '' : $this->gtp . '_') . $this->sqlName; // toto je kvoli Eloquentu
    }

    if (!is_object($appOrAttributes)) {
      // v tomto pripade ide o volanie constructora z Eloquentu
      return parent::__construct($appOrAttributes ?? []);
    } else {
      $this->fullName = str_replace("\\", "/", get_class($this));

      $tmp = explode("/", $this->fullName);
      $this->shortName = end($tmp);

      $this->app = $appOrAttributes;

      $this->myRootFolder = str_replace("\\", "/", dirname((new ReflectionClass(get_class($this)))->getFileName()));

      if ($eloquentQuery === NULL) {
        $this->eloquentQuery = $this->select('id');
      } else {
        $this->eloquentQuery = $eloquentQuery;
        $this->eloquentQuery->pdoCrossTables = [];
      }

      try {
        $this->pdo = $this->getConnection()->getPdo();
      } catch (Exception $e) {
        $this->pdo = null;
      } 

      // During the installation no SQL tables exist. If child's init()
      // method uses data from DB, $this->init() call would fail.
      // Therefore the 'try ... catch'.
      try {
        $this->init();
      } catch (Exception $e) {
        //
      }

      $this->app->db->addTable(
        $this->fullTableSqlName,
        $this->columns(),
        $this->isJunctionTable
      );
    }

    $currentVersion = (int)$this->getCurrentInstalledVersion();
    $lastVersion = $this->getLastAvailableVersion();

    if ($this->lastVersion == 0) {
      $this->saveConfig('installed-version', $lastVersion);
    }

    if ($this->hasAvailableUpgrades()) {

      $this->app->userNotifications->addHtml("
        Model <b>{$this->fullName}</b> has new upgrades available (from {$currentVersion} to {$lastVersion}).
        <a
          href='javascript:void(0)'
          onclick='ADIOS.renderDesktop(\"Desktop/InstallUpgrades\");'
        >Install upgrades</a>
      ");
    } else if (!$this->hasSqlTable()) {
      $this->app->userNotifications->addHtml("
        Model <b>{$this->fullName}</b> has no SQL table.
        <a
          href='javascript:void(0)'
          onclick='ADIOS.renderDesktop(\"Desktop/InstallUpgrades\");'
        >Create table</a>
      ");
    } else if (!$this->isInstalled()) {
      $this->app->userNotifications->addHtml("
        Model <b>{$this->fullName}</b> is not installed.
        <a
          href='javascript:void(0)'
          onclick='ADIOS.renderDesktop(\"Desktop/InstallUpgrades\");'
        >Install model</a>
      ");
    }
  }

  /**
   * Empty placeholder for callback called after the instance has been created in constructor.
   *
   * @return void
   */
  public function init()
  { /* to be overriden */
  }

  /**
   * Retrieves value of configuration parameter.
   *
   * @return void
   */
  public function getConfig(string $configName): string
  {
    return $this->app->config['models'][str_replace("/", "-", $this->fullName)][$configName] ?? "";
  }

  /**
   * Sets the value of configuration parameter.
   *
   * @return void
   */
  public function setConfig(string $configName, $value): void
  {
    $this->app->config['models'][str_replace("/", "-", $this->fullName)][$configName] = $value;
  }

  /**
   * Persistantly saves the value of configuration parameter to the database.
   *
   * @return void
   */
  public function saveConfig(string $configName, $value): void
  {
    $this->app->saveConfig([
      "models" => [
        str_replace("/", "-", $this->fullName) => [
          $configName => $value,
        ],
      ],
    ]);
  }

  /**
   * Shorthand for ADIOS core translate() function. Uses own language dictionary.
   *
   * @param string $string String to be translated
   * @param string $context Context where the string is used
   * @param string $toLanguage Output language
   * @return string Translated string.
   */
  public function translate(string $string, array $vars = []): string
  {
    return $this->app->translate($string, $vars, $this);
  }

  public function hasSqlTable()
  {
    return in_array($this->fullTableSqlName, $this->app->db->existingSqlTables);
  }

  /**
   * Checks whether model is installed.
   *
   * @return bool TRUE if model is installed, otherwise FALSE.
   */
  public function isInstalled(): bool
  {
    return $this->getConfig('installed-version') != "";
  }

  /**
   * Gets the current installed version of the model. Used during installing upgrades.
   *
   * @return void
   */
  public function getCurrentInstalledVersion(): int
  {
    return (int)($this->getConfig('installed-version') ?? 0);
  }

  public function getLastAvailableVersion(): int
  {
    return max(array_keys($this->upgrades()));
  }

  /**
   * Returns list of available upgrades. This method must be overriden by each model.
   *
   * @return array List of available upgrades. Keys of the array are simple numbers starting from 1.
   */
  public function upgrades(): array
  {
    return [
      0 => [], // upgrade to version 0 is the same as installation
    ];
  }

  /**
   * Installs the first version of the model into SQL database. Automatically creates indexes.
   *
   * @return void
   */
  public function install()
  {
    if (!empty($this->fullTableSqlName)) {
      $this->app->db->createSqlTable($this->fullTableSqlName);

      foreach ($this->indexes() as $indexOrConstraintName => $indexDef) {
        if (empty($indexOrConstraintName) || is_numeric($indexOrConstraintName)) {
          $indexOrConstraintName = md5(json_encode($indexDef) . uniqid());
        }

        $tmpColumns = "";

        foreach ($indexDef['columns'] as $tmpKey => $tmpValue) {
          if (!is_numeric($tmpKey)) {
            // v tomto pripade je nazov stlpca v kluci a vo value mozu byt dalsie nastavenia
            $tmpColumnName = $tmpKey;
            $tmpOrder = strtolower($tmpValue['order'] ?? 'asc');
            if (!in_array($tmpOrder, ['asc', 'desc'])) {
              $tmpOrder = 'asc';
            }
          } else {
            $tmpColumnName = $tmpValue;
            $tmpOrder = '';
          }

          $tmpColumns .=
            ($tmpColumns == '' ? '' : ', ')
            . '`' . $tmpColumnName . '`'
            . (empty($tmpOrder) ? '' : ' ' . $tmpOrder);
        }

        switch ($indexDef["type"]) {
          case "index":
            $this->app->db->query("
              alter table `" . $this->fullTableSqlName . "`
              add index `{$indexOrConstraintName}` ({$tmpColumns})
            ");
            break;
          case "unique":
            $this->app->db->query("
              alter table `" . $this->fullTableSqlName . "`
              add constraint `{$indexOrConstraintName}` unique ({$tmpColumns})
            ");
            break;
        }
      }

      $this->createSqlForeignKeys();

      $this->saveConfig('installed-version', max(array_keys($this->upgrades())));

      return TRUE;
    } else {
      return FALSE;
    }
  }

  public function hasAvailableUpgrades(): bool
  {
    $currentVersion = $this->getCurrentInstalledVersion();
    $lastVersion = $this->getLastAvailableVersion();
    return ($lastVersion > $currentVersion);
  }

  /**
   * Installs all upgrades of the model. Internaly stores current version and
   * compares it to list of available upgrades.
   *
   * @return void
   * @throws DBException When an error occured during the upgrade.
   */
  public function installUpgrades(): void
  {
    if ($this->hasAvailableUpgrades()) {
      $currentVersion = (int)$this->getCurrentInstalledVersion();
      $lastVersion = $this->getLastAvailableVersion();

      try {
        $this->app->db->startTransaction();

        $upgrades = $this->upgrades();

        for ($v = $currentVersion + 1; $v <= $lastVersion; $v++) {
          if (is_array($upgrades[$v])) {
            foreach ($upgrades[$v] as $query) {
              $this->app->db->query($query);
            }
          }
        }

        $this->app->db->commit();
        $this->saveConfig('installed-version', $lastVersion);
      } catch (DBException $e) {
        $this->app->db->rollback();
        throw new DBException($e->getMessage());
      }
    }
  }

  public function dropTableIfExists()
  {
    $this->app->db->query("set foreign_key_checks = 0");
    $this->app->db->query("drop table if exists `" . $this->fullTableSqlName . "`");
    $this->app->db->query("set foreign_key_checks = 1");
  }

  /**
   * Create foreign keys for the SQL table. Called when all models are installed.
   *
   * @return void
   */
  public function createSqlForeignKeys()
  {

    if (!empty($this->fullTableSqlName)) {
      $this->app->db->createSqlForeignKeys($this->fullTableSqlName);
    }
  }

  /**
   * Returns full name of the model's SQL table
   *
   * @return string Full name of the model's SQL table
   */
  public function getFullTableSqlName()
  {
    return $this->fullTableSqlName;
  }

  /**
   * Returns full relative URL path for model. Used when generating URL
   * paths for tables, forms, etc...
   *
   * @param mixed $params
   * @return void
   */
  public function getFullUrlBase($params)
  {
    $urlBase = $this->urlBase;
    if (is_array($params)) {
      foreach ($params as $key => $value) {
        if (is_array($value)) continue;
        $urlBase = str_replace("{{ {$key} }}", (string)$value, $urlBase);
      }
    }

    return $urlBase;
  }

  //////////////////////////////////////////////////////////////////
  // misc helper methods

  public function findForeignKeyModels()
  {
    $foreignKeyModels = [];

    foreach ($this->app->models as $model) {
      foreach ($model->columns() as $colName => $colDef) {
        if (!empty($colDef["model"]) && $colDef["model"] == $this->fullName) {
          $foreignKeyModels[$model->fullName] = $colName;
        }
      }
    }

    return $foreignKeyModels;
  }

  public function getEnumValues()
  {
    $tmp = $this
      ->selectRaw("{$this->fullTableSqlName}.id")
      ->selectRaw("(" . str_replace("{%TABLE%}", $this->fullTableSqlName, $this->lookupSqlValue()) . ") as ___lookupSqlValue")
      ->orderBy("___lookupSqlValue", "asc")
      ->get()
      ->toArray();

    $enumValues = [];
    foreach ($tmp as $key => $value) {
      $enumValues[$value['id']] = $value['___lookupSqlValue'];
    }

    return $enumValues;
  }

  public function associateKey($input, $key)
  {
    if (is_array($input)) {
      $output = [];
      foreach ($input as $row) {
        $output[$row[$key]] = $row;
      }
      return $output;
    } else {
      return parent::keyBy($input);
    }
  }

  public function sqlQuery($query)
  {
    return $this->app->db->query($query, $this);
  }



  //////////////////////////////////////////////////////////////////
  // routing

  public function routing(array $routing = [])
  {
    // return $this->app->dispatchEventToPlugins("onModelAfterRouting", [
    //   "model" => $this,
    //   "routing" => $this->addStandardCRUDRouting($routing),
    // ])["routing"];
  }

  public function addStandardCRUDRouting($routing = [], $params = [])
  {
    if (empty($params['urlBase'])) {
      $urlBase = str_replace('/', '\/', $this->urlBase);
    } else {
      $urlBase = $params['urlBase'];
    }

    $urlParams = (empty($params['urlParams']) ? [] : $params['urlParams']);

    $varsInUrl = preg_match_all('/{{ (\w+) }}/', $urlBase, $m);
    foreach ($m[0] as $k => $v) {
      $urlBase = str_replace($v, '([\w\.-]+)', $urlBase);
      $urlParams[$m[1][$k]] = '$' . ($k + 1);
    }

    if (!is_array($routing)) {
      $routing = [];
    }

    $routing = array_merge(
      $routing,
      $this->getStandardCRUDRoutes($urlBase, $urlParams, $varsInUrl)
    );

    foreach ($this->columns() as $colName => $colDefinition) {
      if ($colDefinition['type'] == 'lookup') {
        $tmpModel = $this->app->getModel($colDefinition['model']);
        $routing = array_merge(
          $routing,
          $this->getStandardCRUDRoutes(
            str_replace("/", "\\/", $tmpModel->urlBase) . '\/(\d+)\/' . $urlBase,
            $urlParams,
            $varsInUrl + 1
          )
        );
      }
    }

    return $routing;
  }

  public function getStandardCRUDRoutes($urlBase, $urlParams, $varsInUrl)
  {
    if (empty($urlBase)) return [];

    $routing = [

      // Default
      '/^' . $urlBase . '$/i' => [
        "permission" => "{$this->fullName}/Browse",
        "controller" => $this->crud['browse']['controller'] ?? "Components/Table",
        "params" => array_merge($urlParams, [
          "model" => $this->fullName,
        ])
      ],

      // Browse
      '/^' . $urlBase . '\/browse$/i' => [
        "permission" => "{$this->fullName}/Browse",
        "controller" => $this->crud['browse']['controller'] ?? "Components/Table",
        "params" => array_merge($urlParams, [
          "model" => $this->fullName,
        ])
      ],

      // Edit
      '/^' . $urlBase . '\/(\d+)\/edit$/i' => [
        "permission" => "{$this->fullName}/Edit",
        "controller" => $this->crud['browse']['controller'] ?? "Components/Table",
        "params" => array_merge($urlParams, [
          "displayMode" => "window",
          "windowParams" => [
            "uid" => Helper::str2uid($this->fullName) . '_edit',
          ],
          "model" => $this->fullName,
          "id" => '$' . ($varsInUrl + 1),
        ])
      ],

      // Add
      '/^' . $urlBase . '\/add$/i' => [
        "permission" => "{$this->fullName}/Add",
        "controller" => $this->crud['browse']['controller'] ?? "Components/Table",
        "params" => array_merge($urlParams, [
          "displayMode" => "window",
          "windowParams" => [
            "uid" => Helper::str2uid($this->fullName) . '_add',
            "modal" => TRUE,
          ],
          "model" => $this->fullName,
          "id" => -1,
        ])
      ],

      // Save
      '/^' . $urlBase . '\/save$/i' => [
        "permission" => "{$this->fullName}/Save",
        "controller" => $this->crud['save']['controller'] ?? "Components/Form/Save",
        "params" => array_merge($urlParams, [
          "model" => $this->fullName,
        ])
      ],

      // Delete
      '/^' . $urlBase . '\/delete$/' => [
        "permission" => "{$this->fullName}/Delete",
        "controller" => $this->crud['delete']['controller'] ?? "Components/Form/Delete",
        "params" => array_merge($urlParams, [
          "model" => $this->fullName,
        ])
      ],

      // Copy
      '/^' . $urlBase . '\/copy$/i' => [
        "permission" => "{$this->fullName}/Copy",
        "controller" => $this->crud['copy']['controller'] ?? "Components/Form/Copy",
        "params" => array_merge($urlParams, [
          "model" => $this->fullName,
        ])
      ],

      // Print
      '/^' . $urlBase . '\/(\d+)\/print$/i' => [
        "permission" => "{$this->fullName}/Edit",
        "controller" => "Printer",
        "params" => array_merge($urlParams, [
          "contentController" => $this->crud['print']['controller'] ?? "Components/Form",
          "params" => [
            "model" => $this->fullName,
            "id" => '$' . ($varsInUrl + 1),
          ]
        ])
      ],

      // Search
      '/^' . $urlBase . '\/search$/i' => [
        "permission" => "{$this->fullName}/Search",
        "controller" => $this->crud['search']['controller'] ?? "Components/Table/Search",
        "params" => array_merge($urlParams, [
          "model" => $this->fullName,
          "searchGroup" => $this->tableParams['title'] ?? $urlBase,
          "displayMode" => "window",
          "windowParams" => [
            "modal" => TRUE,
          ],
        ])
      ],

      // Export/CSV
      '/^' . $urlBase . '\/Export\/CSV$/' => [
        "permission" => "{$this->fullName}/Export/CSV",
        "controller" => "Components/Table/Export/CSV",
        "params" => array_merge($urlParams, [
          "model" => $this->fullName,
        ])
      ],

      // Import/CSV
      '/^' . $urlBase . '\/Import\/CSV$/i' => [
        "permission" => "{$this->fullName}/Export/CSV",
        "controller" => "Components/Table/Import/CSV",
        "params" => array_merge($urlParams, [
          "model" => $this->fullName,
        ])
      ],

      // Import/CSV/Import
      '/^' . $urlBase . '\/Import\/CSV\/Import$/i' => [
        "permission" => "{$this->fullName}/Import/CSV",
        "controller" => "Components/Table/Import/CSV/Import",
        "params" => array_merge($urlParams, [
          "model" => $this->fullName,
        ])
      ],

      // Import/CSV/DownloadTemplate
      '/^' . $urlBase . '\/Import\/CSV\/DownloadTemplate$/i' => [
        "permission" => "{$this->fullName}/Import/CSV",
        "controller" => "Components/Table/Import/CSV/DownloadTemplate",
        "params" => array_merge($urlParams, [
          "model" => $this->fullName,
        ])
      ],

      // Import/CSV/Preview
      '/^' . $urlBase . '\/Import\/CSV\/Preview$/i' => [
        "permission" => "{$this->fullName}/Import/CSV",
        "controller" => "Components/Table/Import/CSV/Preview",
        "params" => array_merge($urlParams, [
          "model" => $this->fullName,
        ])
      ],

      // Api/Get/<ID>
      '/^Api\/' . $urlBase . '\/Get\/(\d+)$/i' => [
        "permission" => "{$this->fullName}/Api/Get",
        "controller" => ($this->crud['api']['controller'] ?? "Api") . "/Get",
        "params" => array_merge($urlParams, [
          "model" => $this->fullName,
          "id" => '$' . ($varsInUrl + 1),
        ])
      ],

      // Api/Get:<column>=<value>
      '/^Api\/' . $urlBase . '\/Get:(.+)=(.+)$/i' => [
        "permission" => "{$this->fullName}/Api/Get",
        "controller" => ($this->crud['api']['controller'] ?? "Api") . "/Get",
        "params" => array_merge($urlParams, [
          "model" => $this->fullName,
          "column" => '$' . ($varsInUrl + 1),
          "value" => '$' . ($varsInUrl + 2),
        ])
      ],

    ];

    return $routing;
  }

  //////////////////////////////////////////////////////////////////
  // definition of columns

  public function columns(array $columns = []): array
  {
    $newColumns = [];

    if (!$this->isJunctionTable) {
      $newColumns['id'] = [
        'type' => 'int',
        'byte_size' => '8',
        'sql_definitions' => 'primary key auto_increment',
        'title' => 'ID',
        'readonly' => 'yes',
        'viewParams' => [
          'Table' => ['show' => TRUE],
          'Form' => ['show' => TRUE]
        ],
      ];
    }

    // default column settings
    foreach ($columns as $colName => $colDefinition) {
      $newColumns[$colName] = $colDefinition;

      if ($colDefinition["type"] == "char") {
        $this->app->console->info("{$this->fullName}, {$colName}: char type is deprecated");
      }

      switch ($colDefinition["type"]) {
        case "int":
          $newColumns[$colName]["byte_size"] = $colDefinition["byte_size"] ?? 8;
          break;
        case "float":
          $newColumns[$colName]["byte_size"] = $colDefinition["byte_size"] ?? 14;
          $newColumns[$colName]["decimals"] = $colDefinition["decimals"] ?? 2;
          break;
        case "varchar":
        case "password":
          $newColumns[$colName]["byte_size"] = $colDefinition["byte_size"] ?? 255;
          break;
      }
    }

    if ($this->storeRecordInfo) {
      $newColumns['record_info'] = [
        'type' => 'json',
        'title' => 'Record Information',
      ];
    }

    foreach ($newColumns as $colName => $colDef) {
      $colObject = $this->app->db->columnTypes[$colDef['type']];

      if ($colObject instanceof DataType) {
        $newColumns[$colName] = $colObject->columnDefinitionPostProcess($colDef);
      }
    }

    $newColumns = $this->app->dispatchEventToPlugins("onModelAfterColumns", [
      "model" => $this,
      "columns" => $newColumns,
    ])["columns"];

    $this->fillable = array_keys($newColumns);

    return $newColumns;
  }

  public function getColumnsToShow(): array
  {
    $columnsToShow = [];
    foreach ($this->columns() as $columnName => $columnValue) {
      if (isset($columnValue['show']) && $columnValue['show'] === false) continue;
      $columnsToShow[$columnName] = $columnValue;
    }

    return $columnsToShow;
  }

  public function getColumnsToShowInView(string $view, array|null $columns = NULL): array
  {
    if ($columns === NULL) {
      $columns = $this->columns();
    }

    $columnsToShow = [];

    foreach ($columns as $columnName => $columnValue) {
      if (
        (isset($columnValue['show']) && $columnValue['show'] === true)
        || (
          isset($columnValue['viewParams'][$view]['show'])
          && $columnValue['viewParams'][$view]['show'] === true
        )
      ) {
        $columnsToShow[$columnName] = $columnValue;
      }
    }

    return $columnsToShow;
  }

  public function columnNames()
  {
    return array_keys($this->columns());
  }

  public function indexes(array $indexes = [])
  {
    return $this->app->dispatchEventToPlugins("onModelAfterIndexes", [
      "model" => $this,
      "indexes" => $indexes,
    ])["indexes"];
  }

  public function indexNames()
  {
    return array_keys($this->indexNames());
  }

  // /**
  //  * Returns the configuration of various inputs used in the form.
  //  *
  //  * @return array
  //  */
  // public function inputs(): array
  // {
  //   return [];
  // }

  /**
   * Parses the $data containing strings as a result of DB fetch operation
   * and converts the value of each column to the appropriate PHP type.
   * E.g. columns of type 'int' or 'lookup' will have integer values.
   *
   * @param array $data
   * @param string $lookupKeyPrefix
   *
   * @return [type]
   */
  public function normalizeRowData(array $data, string $lookupKeyPrefix = "")
  {
    foreach ($this->columns() as $column => $columnDefinition) {
      $columnType = $columnDefinition['type'];
      if (isset($this->app->db->columnTypes[$columnType])) {
        $data[$lookupKeyPrefix . $column] = $this->app->db->columnTypes[$columnType]->fromString($data[$lookupKeyPrefix . $column]);
      } else {
        $data[$lookupKeyPrefix . $column] = NULL;
      }

      if ($columnType == 'lookup' && empty($lookupKeyPrefix)) {
        $lookupModel = $this->app->getModel($columnDefinition['model']);
        $data = array_merge($data,
          $lookupModel->normalizeRowData($data, $column . ':LOOKUP:')
        );
      }
    }

    return $data;
  }

  //////////////////////////////////////////////////////////////////
  // CRUD methods

  public function getRelationships()
  {
    return $this; // to be overriden, should return chained Eloquent's ->with() method calls
  }

  public function getExtendedData($item)
  {
    return NULL; // to be overriden, should return $item with extended information
    // the NULL return is for optimization in getAll() method
  }

  public function getById(int $id)
  {
    $item = reset($this->getRelationships()->where('id', $id)->get()->toArray());

    if ($this->getExtendedData([]) !== NULL) {
      $item = $this->getExtendedData($item);
    }

    $item = $this->app->dispatchEventToPlugins("onModelAfterGetExtendedData", [
      "model" => $this,
      "item" => $item,
    ])["item"];

    return $item;
  }

  public function getLookupSqlValueById(int $id)
  {
    $row = $this->app->db->select($this)
      ->columns([
        [$this->lookupSqlValue($this->fullTableSqlName), 'lookup_value']
      ])
      ->where([['id', '=', $id]])
      ->fetch();

    return $row[0]['lookup_value'] ?? '';
  }

  public function getAll(string $keyBy = "id", $withLookups = FALSE, $processLookups = FALSE)
  {
    if ($withLookups) {
      $items = $this->getWithLookups(NULL, $keyBy, $processLookups);
    } else {
      $items = $this->pdoPrepareExecuteAndFetch("select * from :table", [], $keyBy);
    }

    if ($this->getExtendedData([]) !== NULL) {
      foreach ($items as $key => $item) {
        $items[$key] = $this->getExtendedData($item);
      }
    }

    return $items;
  }

  public function getAllCached()
  {
    if (static::$allItemsCache === NULL) {
      static::$allItemsCache = $this->getAll();
    }

    return static::$allItemsCache;
  }

  public function getQueryWithLookups($callback = NULL)
  {
    $query = $this->getQuery();
    $this->addLookupsToQuery($query);

    if ($callback !== NULL && $callback instanceof Closure) {
      $query = $callback($this, $query);
    }

    return $query;
  }

  public function getWithLookups($callback = NULL, $keyBy = 'id', $processLookups = FALSE)
  {
    $query = $this->getQueryWithLookups($callback);
    return $this->processLookupsInQueryResult(
      $this->fetchRows($query, $keyBy, FALSE),
      $processLookups
    );
  }

  public function insertRow($data)
  {
    return $this->app->db->insert($this)
      ->set($data)
      ->execute();
  }

  public function insertRowWithId($data)
  {
    return $this->app->db->insert($this)
      ->set($data)
      ->execute();
  }

  public function insertOrUpdateRow($data)
  {
    unset($data['id']);

    $duplicateKeyData = $data;

    return $this->app->db->insert($this)
      ->set($data)
      ->onDuplicateKey($duplicateKeyData)
      ->execute();
  }

  public function insertRandomRow($data = [], $dictionary = [])
  {
    return $this->insertRow(
      $this->app->db->getRandomColumnValues($this, $data, $dictionary)
    );
  }

  public function updateRow($data, $id)
  {
    $queryOk = $this->app->db->update($this)
      ->set($data)
      ->whereId((int)$id)
      ->execute();

    return ($queryOk ? $id : FALSE);
  }

  public function deleteRow($id)
  {
    return $this->app->db->delete($this)
      ->whereId((int)$id)
      ->execute();
  }

  public function copyRow($id)
  {
    $row = $this->app->db->select($this)
      ->columns([Query::allColumnsWithoutLookups])
      ->where([
        ['id', '=', (int)$id]
      ])
      ->fetchOne();

    unset($row['id']);

    return $this->insertRow($row);
  }


  public function search($q)
  {
  }

  public function pdoPrepareAndExecute(string $query, array $variables)
  {
    $q = $this->pdo->prepare(str_replace(":table", $this->fullTableSqlName, $query));
    return $q->execute($variables);
  }

  public function pdoPrepareExecuteAndFetch(string $query, array $variables, string $keyBy = "")
  {
    $q = $this->pdo->prepare(str_replace(":table", $this->fullTableSqlName, $query));
    $q->execute($variables);

    $rows = [];
    while ($row = $q->fetch(\PDO::FETCH_ASSOC)) {
      if (empty($keyBy)) {
        $rows[] = $row;
      } else {
        $rows[$row[$keyBy]] = $row;
      }
    }

    return $rows;
  }

  //////////////////////////////////////////////////////////////////
  // lookup processing methods

  // $initiatingModel = model formulara, v ramci ktoreho je lookup generovany
  // $initiatingColumn = nazov stlpca, z ktoreho je lookup generovany
  // $formData = aktualne data formulara
  public function lookupWhere(
    $initiatingModel = NULL,
    $initiatingColumn = NULL,
    $formData = [],
    $params = []
  )
  {
    return [];
  }

  // $initiatingModel = model formulara, v ramci ktoreho je lookup generovany
  // $initiatingColumn = nazov stlpca, z ktoreho je lookup generovany
  // $formData = aktualne data formulara
  public function lookupOrder(
    $initiatingModel = NULL,
    $initiatingColumn = NULL,
    $formData = [],
    $params = []
  )
  {
    return [['input_lookup_value', 'asc']];
  }

  // $initiatingModel = model formulara, v ramci ktoreho je lookup generovany
  // $initiatingColumn = nazov stlpca, z ktoreho je lookup generovany
  // $formData = aktualne data formulara
  public function lookupQuery(
    $initiatingModel = NULL,
    $initiatingColumn = NULL,
    $formData = [],
    $params = [],
    $having = "TRUE"
  ): Query
  {
    $where = $params['where'] ?? $this->lookupWhere($initiatingModel, $initiatingColumn, $formData, $params);
    $order = $params['order'] ?? $this->lookupOrder($initiatingModel, $initiatingColumn, $formData, $params);

    return $this->app->db->select($this)
      ->columns([
        ['id', 'id'],
        [$this->lookupSqlValue($this->fullTableSqlName), 'input_lookup_value']
      ])
      ->where($where)
      ->havingRaw($having)
      ->order($order);
  }

  // $initiatingModel = model formulara, v ramci ktoreho je lookup generovany
  // $initiatingColumn = nazov stlpca, z ktoreho je lookup generovany
  // $formData = aktualne data formulara
  public function lookupSqlQuery(
    $initiatingModel = NULL,
    $initiatingColumn = NULL,
    $formData = [],
    $params = [],
    $having = "TRUE"
  ): string
  {
    return $this->lookupQuery(
      $initiatingModel,
      $initiatingColumn,
      $formData,
      $params,
      $having
    )->buildSql();
  }

  public function lookupSqlValue($tableAlias = NULL): string
  {
    // $this->lookupSqlValue = ""
    $value = $this->lookupSqlValue ?? "concat('{$this->fullName}, id = ', {%TABLE%}.id)";
    // echo(get_class($this)." - ".$value."<br/>");

    return ($tableAlias !== NULL
      ? str_replace('{%TABLE%}', "`{$tableAlias}`", $value)
      : $value
    );
  }

  /**
   * onTableParams
   *
   * @param Table $tableObject
   *
   * @return array Modified table params
   */
  public function onTableParams(Table $tableObject, array $params): array
  {
    return (array)$this->app->dispatchEventToPlugins("onModelAfterTableParams", [
      "tableObject" => $tableObject,
      "params" => $params,
    ])["params"];
  }

  /**
   * onTableRowParams
   *
   * @param Table $tableObject
   *
   * @return array Modified row params
   */
  public function onTableRowParams(Table $tableObject, array $params, array $data): array
  {
    return (array)$this->app->dispatchEventToPlugins("onModelAfterTableRowParams", [
      "tableObject" => $tableObject,
      "params" => $params,
      "data" => $data
    ])["params"];
  }


  public function onTableRowCssFormatter(Table $tableObject, array $data): string
  {
    return (string)$this->app->dispatchEventToPlugins("onModelAfterTableRowCssFormatter", [
      "tableObject" => $tableObject,
      "data" => $data,
    ])["data"]["css"];
  }

  public function onTableCellCssFormatter(Table $tableObject, array $data): string
  {
    return (string)$this->app->dispatchEventToPlugins("onModelAfterTableCellCssFormatter", [
      "tableObject" => $tableObject,
      "data" => $data,
    ])["data"]["css"];
  }

  public function onTableCellHtmlFormatter(Table $tableObject, array $data): string
  {
    return (string)$this->app->dispatchEventToPlugins("onModelAfterTableCellHtmlFormatter", [
      "tableObject" => $tableObject,
      "data" => $data,
    ])["data"]["html"];
  }

  public function onTableCellCsvFormatter(Table $tableObject, array $data): string
  {
    return (string)$this->app->dispatchEventToPlugins("onModelAfterTableCellCsvFormatter", [
      "tableObject" => $tableObject,
      "data" => $data,
    ])["data"]["csv"];
  }

  /**
   * onTableBeforeInit
   *
   * @param Table $tableObject
   *
   * @return void
   */
  public function onTableBeforeInit(Table $tableObject): void
  {
    $this->app->dispatchEventToPlugins("onModelAfterTableBeforeInit", [
      "tableObject" => $tableObject,
    ]);
  }

  /**
   * onTableAfterInit
   *
   * @param Table $tableObject
   *
   * @return void
   */
  public function onTableAfterInit(Table $tableObject): void
  {
    $this->app->dispatchEventToPlugins("onModelAfterTableAfterInit", [
      "tableObject" => $tableObject,
    ]);
  }

  /**
   * onTableAfterDataLoaded
   *
   * @param Table $tableObject
   *
   * @return void
   */
  public function onTableAfterDataLoaded(Table $tableObject): void
  {
    $this->app->dispatchEventToPlugins("onModelAfterTableAfterDataLoaded", [
      "tableObject" => $tableObject,
    ]);
  }

  //////////////////////////////////////////////////////////////////
  // Components/Form methods

  public function columnValidate(string $column, $value): bool
  {
    $valid = TRUE;

    $colDefinition = $this->columns()[$column] ?? [];
    $colType = $colDefinition['type'];

    if ($this->app->db->isRegisteredColumnType($colType)) {
      $valid = $this->app->db->columnTypes[$colType]->validate($value);
    }

    return $valid;
  }

  /**
   * onFormBeforeInit
   *
   * @param mixed $formObject
   *
   * @return void
   */
  public function onFormBeforeInit($formObject): void
  {
  }

  /**
   * onFormAfterInit
   *
   * @param mixed $formObject
   *
   * @return void
   */
  public function onFormAfterInit($formObject): void
  {
  }

  public function onFormParams(Form $formObject, array $params): array
  {
    return (array)$this->app->dispatchEventToPlugins("onModelAfterFormParams", [
      "formObject" => $formObject,
      "params" => $params,
    ])["params"];
  }

  public function onFormChange(string $column, string $formUid, array $data): array
  {
    return [];

    // example return:
    // return [
    //   'column_1' => ['value' => 'newColumnValue'],
    //   'column_2' => ['inputHtml' => 'newInputHtml', 'inputCssClass' => 'newInputCssClass'],
    //   'column_3' => ['alert' => 'This is just an alert.'],
    //   'column_4' => ['warning' => 'Something is not correct.'],
    //   'column_4' => ['fatal' => 'Ouch. Fatal error!'],
    // ];
  }

  public function recordValidate($data)
  {
    $invalidInputs = [];

    foreach ($this->columns() as $column => $colDefinition) {
      if (
        $colDefinition['required']
        && ($data[$column] == NULL || $data[$column] == '')
      ) {
        $invalidInputs[$column] = $this->app->translate(
          "`{{ colTitle }}` is required.",
          ['colTitle' => $colDefinition['title']]
        );
      } else if (!$this->columnValidate($column, $data[$column])) {
        $invalidInputs[$column] = $this->app->translate(
          "`{{ colTitle }}` contains invalid value.",
          ['colTitle' => $colDefinition['title']]
        );
      }
    }

    if (!empty($invalidInputs)) {
      throw new RecordSaveException(
        json_encode($invalidInputs)
      );
    }

    return $this->app->dispatchEventToPlugins("onModelAfterRecordValidate", [
      "model" => $this,
      "data" => $data
    ])['params'];
  }

  public function normalizeRecordData(array $data): array {
    $columns = $this->columns();

    // Vyhodene, pretoze to v recordSave() sposobovalo mazanie udajov
    // foreach ($columns as $colName => $colDef) {
    //   if (!isset($data[$colName])) $data[$colName] = NULL;
    // }

    foreach ($data as $colName => $colValue) {
      if (!isset($columns[$colName])) {
        unset($data[$colName]);
      } else {
        switch ($columns[$colName]["type"]) {
          case "int": $data[$colName] = (int) $colValue; break;
          case "lookup": $data[$colName] = ((int) $colValue) <= 0 ? NULL : (int) $colValue; break;
          case "float": $data[$colName] = (float) $colValue; break;
          case "boolean": 
            if (empty($colValue) || !((bool) $colValue)) {
              $data[$colName] = 0;
            } else {
              $data[$colName] = 1;
            }
          break;
        }
      }
    }

    return $data;
  }

  /**
   * Check if the lookup table needs the id of the inserted record from this model
   */
  private function ___getInsertedIdForLookupColumn(array $lookupColumns, array $lookupData, int $insertedRecordId): array
  {
    foreach ($lookupColumns as $lookupColumnName => $lookupColumnData) {
      if ($lookupColumnData['type'] != 'lookup') continue;

      if ($lookupColumnData['model'] == $this->fullName) {
        $lookupData[$lookupColumnName] = $insertedRecordId;
        break;
      }
    }

    return $lookupData;
  }

  private function ___validateBase64Image(string $base64String)
  {
    $pattern = '/^data:image\/[^;]+;base64,/';
    return preg_match($pattern, $base64String);
  }

  public function recordCreate(array $data): int {
    return $this->create($data)->id;
  }

  public function recordUpdate(int $id, array $data): int {
    $this->find($id)->update($data);
    return $id;
  }

  public function recordSave(array $data)
  {
    $id = (int) $data['id'];
    $isInsert = ($id <= 0);

    $this->recordSaveOriginalData = $data;

    // extract data for this model and data for lookup models
    $dataForThisModel = [];
    $dataForLookupModels = [];
    foreach ($data as $key => $value) {
      if (strpos($key, ":LOOKUP:") === FALSE) {
        $dataForThisModel[$key] = $value;
      } else {
        [$columnName, $lookupColumnName] = explode(":LOOKUP:", $key);
        $dataForLookupModels[$columnName][$lookupColumnName] = $value;
      }

      // Upload image
      if ($this->columns()[$key]['type'] == 'image') {
        // If is not base64 (new image, skip)
        if ($this->___validateBase64Image((string)$data[$key]['fileData']) == 0) {
          unset($dataForThisModel[$key]);
          continue;
        }

        $folderPath = $this->getFolderPath();
        $fileName = bin2hex(random_bytes(10)) . '-' . $data[$key]['fileName'];

        // Replace just with filePath to save in DB
        $dataForThisModel[$key] = $fileName;

        if (!is_dir($folderPath)) mkdir($folderPath);

        $imageData = preg_replace('/^data:image\/[^;]+;base64,/', '', $data[$key]['fileData']);
        $image = base64_decode($imageData);

        if (file_put_contents($folderPath . "/{$fileName}", $image) === false) {
          throw new Exception($this->translate("Upload file error"));
        }
      }
    }

    $this->recordValidate($dataForThisModel);

    if ($isInsert) {
      $dataForThisModel = $this->onBeforeInsert($dataForThisModel);
    } else {
      $dataForThisModel = $this->onBeforeUpdate($dataForThisModel);
    }

    $dataForThisModel = $this->onBeforeSave($dataForThisModel);
    $dataForThisModel = $this->normalizeRecordData($dataForThisModel);

    if ($id <= 0) {
      $this->app->router->checkPermission($this->fullName . ':Create');
      unset($dataForThisModel['id']);
      $returnValue = $this->recordCreate($dataForThisModel);
      $data['id'] = (int) $returnValue;
      $id = (int) $returnValue;
    } else {
      $this->app->router->checkPermission($this->fullName . ':Update');
      $returnValue = $this->recordUpdate($id, $dataForThisModel);
    }

    // save data for lookup models first (and create records, if necessary)
    foreach ($dataForLookupModels as $lookupColumnName => $lookupData) {
      $lookupModelName = $this->columns()[$lookupColumnName]['model'] ?? NULL;

      if ($lookupColumnName == NULL) continue;

      $lookupModel = $this->app->getModel($lookupModelName);
      $lookupData = $this->___getInsertedIdForLookupColumn($lookupModel->columns(), $lookupData, $data['id']);
      $lookupModel->recordValidate($lookupData);

      if ($data[$lookupColumnName] <= 0) {
        $lookupData = $lookupModel->onBeforeInsert($lookupData);
      } else {
        $lookupData = $lookupModel->onBeforeUpdate($lookupData);
      }

      $lookupData = $this->onBeforeSave($lookupData);

      if ($data[$lookupColumnName] <= 0) {
        $this->app->router->checkPermission($lookupModel->fullName . ':Create');
        $data[$lookupColumnName] = (int) $lookupModel->insertRow($lookupData);
      } else {
        $this->app->router->checkPermission($lookupModel->fullName . ':Update');
        $lookupModel->updateRow($lookupData, $data[$lookupColumnName]);
      }
    }

    // save cross-table-alignments
    foreach ($this->junctions as $jName => $jParams) {
      if (!isset($data[$jName])) continue;

      $junctions = $data[$jName] ?? NULL;
      if (!is_array($junctions)) {
        $junctions = @json_decode($data[$jName], TRUE);
      }

      if (is_array($junctions)) {
        $junctionModel = $this->app->getModel($jParams["junctionModel"]);

        $this->app->pdo->execute("
          delete from `{$junctionModel->getFullTableSqlName()}`
          where `{$jParams['masterKeyColumn']}` = ?
        ", [$id]);

        foreach ($junctions as $junction) {
          $idOption = (int) $junction;
          if ($idOption > 0) {
            $this->app->pdo->execute("
              insert into `{$junctionModel->getFullTableSqlName()}` (
                `{$jParams['masterKeyColumn']}`,
                `{$jParams['optionKeyColumn']}`
              ) values (?, ?)
            ", [$id, $idOption]);
          }
        }
      }
    }

    if ($isInsert) {
      $returnValue = $this->onAfterInsert($data, $returnValue);
    } else {
      $returnValue = $this->onAfterUpdate($data, $returnValue);
    }

    $returnValue = $this->onAfterSave($data, $returnValue);

    return $returnValue;
  }

  public function recordDelete(int $id)
  {
    $id = (int)$id;

    try {
      $this->onBeforeDelete($id);
      $returnValue = $this->deleteRow($id);
      $returnValue = $this->onAfterDelete($id);
      return $returnValue;
    } catch (RecordDeleteException $e) {
      return $this->app->renderHtmlWarning($e->getMessage());
    }
  }

  //////////////////////////////////////////////////////////////////
  // Components/Cards methods

  public function cards(array $cards = [])
  {
    return $this->app->dispatchEventToPlugins("onModelAfterCards", [
      "model" => $this,
      "cards" => $cards,
    ])["cards"];
  }

  public function cardsParams($params)
  {
    return $this->app->dispatchEventToPlugins("onModelAfterCardsParams", [
      "model" => $this,
      "params" => $params,
    ])["params"];
  }

  public function cardsCardHtmlFormatter($cardsObject, $data)
  {
    return $this->app->dispatchEventToPlugins("onModelAfterCardsCardHtmlFormatter", [
      "model" => $this,
      "cardsObject" => $cardsObject,
      "data" => $data,
    ])["html"];
  }

  //////////////////////////////////////////////////////////////////
  // Components/Tree methods

  public function treeParams($params)
  {
    return $this->app->dispatchEventToPlugins("onModelAfterTreeParams", [
      "model" => $this,
      "params" => $params,
    ])["params"];
  }


  //////////////////////////////////////////////////////////////////
  // save/delete events

  /**
   * onBeforeInsert
   *
   * @param mixed $data
   *
   * @return array
   */
  public function onBeforeInsert(array $data): array
  {
    if ($this->storeRecordInfo) {
      // REVIEW DD: Tato uprava nie je podla toho, ako sme si to vysvetlili.
      // Mixujes dohromady $data a $columns.
      // T.j. do $data['record_info'] nemoze ist konfiguracia inputov - su to $data.
      $data['record_info'] = $this->getNewRecordInfo();
      $data['record_info'] = $this->setRecordInfoCreated($data['record_info']);
    }

    return $this->app->dispatchEventToPlugins("onModelBeforeInsert", [
      "model" => $this,
      "data" => $data,
    ])["data"];
  }

  /**
   * onModelBeforeUpdate
   *
   * @param mixed $data
   *
   * @return array
   */
  public function onBeforeUpdate(array $data): array
  {
    if ($this->storeRecordInfo) {
      $data['record_info'] = $this->setRecordInfoUpdated($data);
    }

    return $this->app->dispatchEventToPlugins("onModelBeforeUpdate", [
      "model" => $this,
      "data" => $data,
    ])["data"];
  }

  /**
   * onBeforeSave
   *
   * @param mixed $data
   *
   * @return [type]
   */
  public function onBeforeSave(array $data): array
  {
    if ($this->storeRecordInfo) $data['record_info'] = json_encode($data['record_info']);

    return $this->app->dispatchEventToPlugins("onModelBeforeSave", [
      "model" => $this,
      "data" => $data,
    ])["data"];
  }

  /**
   * onAfterInsert
   *
   * @param mixed $data
   * @param mixed $returnValue
   *
   * @return [type]
   */
  public function onAfterInsert(array $data, $returnValue)
  {
    return $this->app->dispatchEventToPlugins("onModelAfterInsert", [
      "model" => $this,
      "data" => $data,
      "returnValue" => $returnValue,
    ])["returnValue"];
  }

  /**
   * onModelAfterUpdate
   *
   * mixed $data
   * @param mixed $returnValue
   *
   * @return [type]
   */
  public function onAfterUpdate(array $data, $returnValue)
  {
    return $this->app->dispatchEventToPlugins("onModelAfterUpdate", [
      "model" => $this,
      "data" => $data,
      "returnValue" => $returnValue,
    ])["returnValue"];
  }

  /**
   * onAfterSave
   *
   * @param mixed $data
   * @param mixed $returnValue
   *
   * @return [type]
   */
  public function onAfterSave(array $data, $returnValue)
  {
    return $this->app->dispatchEventToPlugins("onModelAfterSave", [
      "model" => $this,
      "data" => $data,
      "returnValue" => $returnValue,
    ])["returnValue"];
  }


  public function onBeforeDelete(int $id): int
  {
    return $this->app->dispatchEventToPlugins('onModelBeforeDelete', [
      'model' => $this,
      'id' => $id,
    ])['id'];
  }

  public function onAfterDelete(int $id): int
  {
    return $this->app->dispatchEventToPlugins('onModelAfterDelete', [
      'model' => $this,
      'id' => $id,
    ])['id'];
  }

  public function loadRecords(callable|null $queryModifierCallback = null): array {
    $query = $this->prepareLoadRecordQuery();
    if ($queryModifierCallback !== null) $queryModifierCallback($query);

    $data = $query->get()?->toArray();

    if (!is_array($data)) $data = [];

    foreach ($data as $key => $value) {
      $data[$key] = $this->onAfterLoadRecord($data[$key]);
    }

    return $data;
  }

  public function loadRecord(callable|null $queryModifierCallback = null): array {
    $data = reset($this->loadRecords($queryModifierCallback));
    if (!is_array($data)) $data = [];
    return $data;
  }


  public function prepareLoadRecordQuery(bool $addLookups = false): \Illuminate\Database\Eloquent\Builder {
    $tmpColumns = $this->columns();

    $selectRaw = [];
    $withs = [];
    $joins = [];

    foreach ($tmpColumns as $tmpColumnName => $tmpColumnDefinition) {
      $selectRaw[] = $this->fullTableSqlName . '.' . $tmpColumnName;
    }

    $selectRaw[] = '(' .
      str_replace('{%TABLE%}', $this->fullTableSqlName, $this->lookupSqlValue())
      . ') as _lookupText_'
    ;


    if ($addLookups) {

      // LOOKUPS and RELATIONSHIPS
      foreach ($tmpColumns as $columnName => $column) {
        if ($column['type'] == 'lookup') {
          $lookupModel = $this->app->getModel($column['model']);
          $lookupConnection = $lookupModel->getConnectionName();
          $lookupDatabase = $lookupModel->getConnection()->getDatabaseName();
          $lookupTableName = $lookupModel->getFullTableSqlName();
          $joinAlias = 'join_' . $columnName;
          $lookupSqlValue = "(" .
            str_replace("{%TABLE%}.", '', $lookupModel->lookupSqlValue())
            . ") as lookupSqlValue";

          $selectRaw[] = "(" .
            str_replace("{%TABLE%}", $joinAlias, $lookupModel->lookupSqlValue())
            . ") as `{$columnName}:LOOKUP`"
          ;

          $joins[] = [
            $lookupDatabase . '.' . $lookupTableName . ' as ' . $joinAlias,
            $joinAlias.'.id',
            '=',
            $this->fullTableSqlName.'.'.$columnName
          ];

          $withs[$columnName] = function ($query) use ($lookupDatabase, $lookupTableName, $lookupSqlValue) {
            $query
              ->from($lookupDatabase . '.' . $lookupTableName)
              ->selectRaw('*, ' . $lookupSqlValue)
            ;
          };
        }
      }

    }

    // TODO: Toto je pravdepodobne potencialna SQL injection diera. Opravit.
    $query = $this->selectRaw(join(',', $selectRaw))->with($withs);
    foreach ($joins as $join) {
      $query->leftJoin($join[0], $join[1], $join[2], $join[3]);
    }

    return $query;
  }

  public function onAfterLoadRecord(array $data): array {
    return $data;
  }

  //////////////////////////////////////////////////////////////////
  // own implementation of lookups and pivots

  // getQuery
  public function getQuery($columns = NULL)
  {
    if ($columns === NULL) $columns = $this->fullTableSqlName . ".id";
    return $this->select($columns);
  }

  // addLookupsToQuery
  public function addLookupsToQuery($query, $lookupsToAdd = NULL)
  {
    if (empty($query->addedLookups)) {
      $query->addedLookups = [];
    }

    if ($lookupsToAdd === NULL) {
      $lookupsToAdd = [];
      foreach ($this->columns() as $colName => $colDef) {
        if (!empty($colDef['model'])) {
          $tmpModel = $this->app->getModel($colDef['model']);
          $lookupsToAdd[$colName] = $tmpModel->shortName;
        }
      }
    }

    foreach ($query->addedLookups as $colName => $lookupName) {
      unset($lookupsToAdd[$colName]);
    }

    $selects = [$this->fullTableSqlName . ".*"];
    $joins = [];

    foreach ($lookupsToAdd as $colName => $lookupName) {
      $lookupedModel = $this->app->getModel($this->columns()[$colName]['model']);

      $selects[] = $lookupedModel->getFullTableSqlName() . ".id as {$lookupName}___LOOKUP___id";

      $lookupedModelColumns = $lookupedModel->columns();

      foreach ($lookupedModel->columnNames() as $lookupedColName) {
        if (!$lookupedModelColumns[$lookupedColName]['virtual'] ?? FALSE) {
          $selects[] = $lookupedModel->getFullTableSqlName() . ".{$lookupedColName} as {$lookupName}___LOOKUP___{$lookupedColName}";
        }
      }

      $joins[] = [
        $lookupedModel->getFullTableSqlName(),
        $lookupedModel->getFullTableSqlName() . ".id",
        '=',
        $this->fullTableSqlName . ".{$colName}"
      ];

      $query->addedLookups[$colName] = $lookupName;
    }

    $query = $query->addSelect($selects);
    foreach ($joins as $join) {
      $query = $query->leftJoin($join[0], $join[1], $join[2], $join[3]);
    }

    return $this;
  }

  // addCrossTableToQuery
  public function addCrossTableToQuery($query, $crossTableModelName, $resultKey = '')
  {
    $crossTableModel = $this->app->getModel($crossTableModelName);
    if (empty($resultKey)) {
      $resultKey = $crossTableModel->shortName;
    }

    $foreignKey = "";
    foreach ($crossTableModel->columns() as $crossTableColName => $crossTableColDef) {
      if ($crossTableColDef['model'] == $this->fullName) {
        $foreignKey = $crossTableColName;
      }
    }

    if (empty($query->pdoCrossTables)) {
      $query->pdoCrossTables = [];
    }

    $query->pdoCrossTables[] = [$crossTableModel, $foreignKey, $resultKey];

    return $this;
  }

  public function processLookupsInQueryResult($rows)
  {
    $processedRows = [];
    foreach ($rows as $rowKey => $row) {
      foreach ($row as $colName => $colValue) {
        $strpos = strpos($colName, "___LOOKUP___");
        if ($strpos !== FALSE) {
          $tmp1 = strtoupper(substr($colName, 0, $strpos));
          $tmp2 = substr($colName, $strpos + strlen("___LOOKUP___"));
          $row[$tmp1][$tmp2] = $colValue;
          unset($row[$colName]);
        }
      }
      $processedRows[$rowKey] = $row;
    }
    return $processedRows;
  }

  // fetchRows
  public function fetchRows($eloquentQuery, $keyBy = 'id', $processLookups = TRUE)
  {
    $query = $this->pdo->prepare($eloquentQuery->toSql());
    $query->execute($eloquentQuery->getBindings());

    $rows = $this->associateKey($query->fetchAll(\PDO::FETCH_ASSOC), 'id');

    if ($processLookups) {
      $rows = $this->processLookupsInQueryResult($rows);
    }

    if (!empty($eloquentQuery->pdoCrossTables)) {
      foreach ($eloquentQuery->pdoCrossTables as $crossTable) {
        list($tmpCrossTableModel, $tmpForeignKey, $tmpCrossTableResultKey) = $crossTable;

        $tmpCrossQuery = $tmpCrossTableModel->getQuery();
        $tmpCrossTableModel->addLookupsToQuery($tmpCrossQuery);
        $tmpCrossQuery->whereIn($tmpForeignKey, array_keys($rows));

        $tmpCrossTableValues = $this->fetchRows($tmpCrossQuery, 'id', FALSE);

        foreach ($tmpCrossTableValues as $tmpCrossTableValue) {
          $rows[$tmpCrossTableValue[$tmpForeignKey]][$tmpCrossTableResultKey][] = $tmpCrossTableValue;
        }
      }
    }

    if (empty($keyBy) || $keyBy === NULL || $keyBy === FALSE || $keyBy == 'id') {
      return $rows;
    } else {
      return $this->associateKey($rows, $keyBy);
    }
  }

  // countRowsInQuery
  public function countRowsInQuery($eloquentQuery)
  {
    $query = $this->pdo->prepare($eloquentQuery->toSql());
    $query->execute($eloquentQuery->getBindings());

    $rows = $query->fetchAll(\PDO::FETCH_COLUMN, 0);

    return count($rows);
  }

  public function getNewRecordInfo(): array
  {
    return [
      'id_created_by' => [
        'type' => 'lookup',
        'title' => 'Created By',
        'model' => 'ADIOS/Models/User',
        'foreignKeyOnUpdate' => 'CASCADE',
        'foreignKeyOnDelete' => 'CASCADE',
        'value' => null,
        'readonly' => true
      ],
      'created_at' => [
        'title' => 'Created At',
        'type' => 'datetime',
        'value' => null,
        'readonly' => true,
      ],
      'id_updated_by' => [
        'type' => 'lookup',
        'title' => 'Updated By',
        'model' => 'ADIOS/Models/User',
        'foreignKeyOnUpdate' => 'CASCADE',
        'foreignKeyOnDelete' => 'CASCADE',
        'value' => null,
        'readonly' => true
      ],
      'updated_at' => [
        'title' => 'Updated At',
        'type' => 'datetime',
        'value' => null,
        'readonly' => true
      ]
    ];
  }

  public function setRecordInfoCreated(array $recordInfo): array
  {
    $recordInfo['id_created_by']['value'] = $this->app->userProfile['id'];
    $recordInfo['created_at']['value'] = date('Y-m-d H:i:s');

    return $recordInfo;
  }

  public function setRecordInfoUpdated(array $data): array
  {
    $tmpData = $this->find($data['id']);
    $recordInfo = json_decode($tmpData->record_info, true);

    $recordInfo['id_updated_by']['value'] = $this->app->userProfile['id'];
    $recordInfo['updated_at']['value'] = date('Y-m-d H:i:s');

    return $recordInfo;
  }

  public function getFolderUrl(): string
  {
    return "{$this->app->config['uploadUrl']}/" . str_replace('/', '-', $this->fullName);
  }

  public function getFolderPath(): string
  {
    return "{$this->app->config['uploadDir']}/" . str_replace('/', '-', $this->fullName);
  }


  public function relationships(): array
  {
    $relationships = [];

    foreach ($this->columns() as $columnName => $columnDefinition) {
      if ($columnDefinition['type'] == 'lookup') {
        $relationships[] = $columnName;
      }
    }

    return $relationships;
  }
}
