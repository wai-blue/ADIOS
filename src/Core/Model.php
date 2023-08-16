<?php

/*
  This file is part of ADIOS Framework.

  This file is published under the terms of the license described
  in the license.md file which is located in the root folder of
  ADIOS Framework package.
*/

namespace ADIOS\Core;

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

  protected ?\Illuminate\Database\Eloquent\Builder $eloquentQuery = null;

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
  public ?\ADIOS\Core\Loader $adios = NULL;

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
   * Readable title for the table listing.
   */
  public string $tableTitle = "";

  /**
   * Readable title for the form when editing content.
   */
  public string $formTitleForEditing = "";

  /**
   * Readable title for the form when inserting content.
   */
  public string $formTitleForInserting = "";

  /**
   * SQL-compatible string used to render displayed value of the record when used
   * as a lookup.
   */
  public ?string $lookupSqlValue = NULL;

  /**
   * If set to TRUE, the SQL table will not contain the ID autoincrement column
   */
  public bool $isCrossTable = FALSE;

  var $pdo;
  var $searchAction;

  /**
   * Property used to store original data when formSave() method is called
   *
   * @var mixed
   */
  var $formSaveOriginalData = NULL;
  protected string $fullTableSqlName = "";

  private static $allItemsCache = NULL;

  public ?array $crossTableAssignments = [];

  public ?string $addButtonText = null;
  public ?string $formSaveButtonText = null;
  public ?string $formAddButtonText = null;

  /**
   * Creates instance of model's object.
   *
   * @param  mixed $adiosOrAttributes
   * @param  mixed $eloquentQuery
   * @return void
   */
  public function __construct($adiosOrAttributes = NULL, $eloquentQuery = NULL)
  {
    $this->gtp = $adiosOrAttributes->gtp;
    $this->fullTableSqlName = (empty($this->gtp) ? '' : $this->gtp . '_') . $this->sqlName;
    $this->table = (empty($this->gtp) ? '' : $this->gtp . '_') . $this->sqlName; // toto je kvoli Eloquentu

    if (!is_object($adiosOrAttributes)) {
      // v tomto pripade ide o volanie constructora z Eloquentu
      return parent::__construct($adiosOrAttributes ?? []);
    } else {
      $this->fullName = str_replace("\\", "/", get_class($this));
      $this->shortName = end(explode("/", $this->fullName));
      $this->adios = $adiosOrAttributes;

      $this->myRootFolder = str_replace("\\", "/", dirname((new \ReflectionClass(get_class($this)))->getFileName()));

      if ($eloquentQuery === NULL) {
        $this->eloquentQuery = $this->select('id');
      } else {
        $this->eloquentQuery = $eloquentQuery;
        $this->eloquentQuery->pdoCrossTables = [];
      }

      $this->pdo = $this->getConnection()->getPdo();

      // During the installation no SQL tables exist. If child's init()
      // method uses data from DB, $this->init() call would fail.
      try {
        $this->init();
      } catch (\Exception $e) {
        //
      }

      $this->adios->db->addTable(
        $this->fullTableSqlName,
        $this->columns(),
        $this->isCrossTable
      );
    }

    if ($this->hasAvailableUpgrades()) {
      $this->adios->userNotifications->addHtml("
        Model <b>{$this->fullName}</b> has new upgrades available.
        <a
          href='javascript:void(0)'
          onclick='desktop_update(\"Desktop/InstallUpgrades\");'
        >Install upgrades</a>
      ");
    } else if (!$this->hasSqlTable()) {
      $this->adios->userNotifications->addHtml("
        Model <b>{$this->fullName}</b> has no SQL table.
        <a
          href='javascript:void(0)'
          onclick='desktop_update(\"Desktop/InstallUpgrades\");'
        >Create table</a>
      ");
    } else if (!$this->isInstalled()) {
      $this->adios->userNotifications->addHtml("
        Model <b>{$this->fullName}</b> is not installed.
        <a
          href='javascript:void(0)'
          onclick='desktop_update(\"Desktop/InstallUpgrades\");'
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
    return $this->adios->config['models'][str_replace("/", "-", $this->fullName)][$configName] ?? "";
  }

  /**
   * Sets the value of configuration parameter.
   *
   * @return void
   */
  public function setConfig(string $configName, $value): void
  {
    $this->adios->config['models'][str_replace("/", "-", $this->fullName)][$configName] = $value;
  }

  /**
   * Persistantly saves the value of configuration parameter to the database.
   *
   * @return void
   */
  public function saveConfig(string $configName, $value): void
  {
    $this->adios->saveConfig([
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
   * @param  string $string String to be translated
   * @param  string $context Context where the string is used
   * @param  string $toLanguage Output language
   * @return string Translated string.
   */
  public function translate(string $string, array $vars = []): string
  {
    return $this->adios->translate($string, $vars, $this);
  }

  public function hasSqlTable()
  {
    return in_array($this->fullTableSqlName, $this->adios->db->existingSqlTables);
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
    return (int) ($this->getConfig('installed-version') ?? 0);
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
    if (!empty($this->getFullTableSqlName())) {
      $this->adios->db->createSqlTable(
        $this->getFullTableSqlName()
      );

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
            . (empty($tmpOrder) ? '' : ' '.$tmpOrder)
          ;
        }

        switch ($indexDef["type"]) {
          case "index":
            $this->adios->db->query("
              alter table `" . $this->getFullTableSqlName() . "`
              add index `{$indexOrConstraintName}` ({$tmpColumns})
            ");
            break;
          case "unique":
            $this->adios->db->query("
              alter table `" . $this->getFullTableSqlName() . "`
              add constraint `{$indexOrConstraintName}` unique ({$tmpColumns})
            ");
            break;
        }
      }

      $this->saveConfig('installed-version', max(array_keys($this->upgrades())));

      return TRUE;
    } else {
      return FALSE;
    }
  }

  public function hasAvailableUpgrades(): bool
  {
    $currentVersion = $this->getCurrentInstalledVersion();
    $lastVersion = max(array_keys($this->upgrades()));
    return ($lastVersion > $currentVersion);
  }

  /**
   * Installs all upgrades of the model. Internaly stores current version and
   * compares it to list of available upgrades.
   *
   * @throws \ADIOS\Core\Exceptions\DBException When an error occured during the upgrade.
   * @return void
   */
  public function installUpgrades(): void
  {
    if ($this->hasAvailableUpgrades()) {
      $currentVersion = (int) $this->getCurrentInstalledVersion();
      $lastVersion = max(array_keys($this->upgrades()));

      try {
        $this->adios->db->startTransaction();

        $upgrades = $this->upgrades();

        for ($v = $currentVersion + 1; $v <= $lastVersion; $v++) {
          if (is_array($upgrades[$v])) {
            foreach ($upgrades[$v] as $query) {
              $this->adios->db->query($query);
            }
          }
        }

        $this->adios->db->commit();
        $this->saveConfig('installed-version', $lastVersion);
      } catch (\ADIOS\Core\Exceptions\DBException $e) {
        $this->adios->db->rollback();
        throw new \ADIOS\Core\Exceptions\DBException($e->getMessage());
      }
    }
  }

  public function dropTableIfExists()
  {
    $this->adios->db->query("set foreign_key_checks = 0");
    $this->adios->db->query("drop table if exists `" . $this->getFullTableSqlName() . "`");
    $this->adios->db->query("set foreign_key_checks = 1");
  }

  /**
   * Create foreign keys for the SQL table. Called when all models are installed.
   *
   * @return void
   */
  public function createSqlForeignKeys()
  {

    if (!empty($this->getFullTableSqlName())) {
      $this->adios->db->createSqlForeignKeys($this->getFullTableSqlName());
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
   * @param  mixed $params
   * @return void
   */
  public function getFullUrlBase($params)
  {
    $urlBase = $this->urlBase;
    if (is_array($params)) {
      foreach ($params as $key => $value) {
        if (is_array($value)) continue;
        $urlBase = str_replace("{{ {$key} }}", (string) $value, $urlBase);
      }
    }

    return $urlBase;
  }

  //////////////////////////////////////////////////////////////////
  // misc helper methods

  public function findForeignKeyModels()
  {
    $foreignKeyModels = [];

    foreach ($this->adios->models as $model) {
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
    return $this->adios->db->query($query, $this);
  }



  //////////////////////////////////////////////////////////////////
  // routing

  public function routing(array $routing = [])
  {
    return $this->adios->dispatchEventToPlugins("onModelAfterRouting", [
      "model" => $this,
      "routing" => $this->addStandardCRUDRouting($routing),
    ])["routing"];
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
        $tmpModel = $this->adios->getModel($colDefinition['model']);
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
      '/^' . $urlBase . '$/' => [
        "permission" => "{$this->fullName}/Browse",
        "action" => $this->crud['browse']['action'] ?? "UI/Table",
        "params" => array_merge($urlParams, [
          "model" => $this->fullName,
        ])
      ],

      // Browse
      '/^' . $urlBase . '\/browse$/' => [
        "permission" => "{$this->fullName}/Browse",
        "action" => $this->crud['browse']['action'] ?? "UI/Table",
        "params" => array_merge($urlParams, [
          "model" => $this->fullName,
        ])
      ],

      // Edit
      '/^' . $urlBase . '\/(\d+)\/edit$/' => [
        "permission" => "{$this->fullName}/Edit",
        "action" => $this->crud['edit']['action'] ?? "UI/Form",
        "params" => array_merge($urlParams, [
          "displayMode" => "window",
          "windowParams" => [
            "uid" => \ADIOS\Core\HelperFunctions::str2uid($this->fullName) . '_edit',
          ],
          "model" => $this->fullName,
          "id" => '$' . ($varsInUrl + 1),
        ])
      ],

      // Add
      '/^' . $urlBase . '\/add$/' => [
        "permission" => "{$this->fullName}/Add",
        "action" => $this->crud['add']['action'] ?? "UI/Form",
        "params" => array_merge($urlParams, [
          "displayMode" => "window",
          "windowParams" => [
            "uid" => \ADIOS\Core\HelperFunctions::str2uid($this->fullName) . '_add',
            "modal" => TRUE,
          ],
          "model" => $this->fullName,
          "id" => -1,
          "defaultValues" => $urlParams,
        ])
      ],

      // Save
      '/^' . $urlBase . '\/save$/' => [
        "permission" => "{$this->fullName}/Save",
        "action" => $this->crud['save']['action'] ?? "UI/Form/Save",
        "params" => array_merge($urlParams, [
          "model" => $this->fullName,
        ])
      ],

      // Delete
      '/^' . $urlBase . '\/delete$/' => [
        "permission" => "{$this->fullName}/Delete",
        "action" => $this->crud['delete']['action'] ?? "UI/Form/Delete",
        "params" => array_merge($urlParams, [
          "model" => $this->fullName,
        ])
      ],

      // Copy
      '/^' . $urlBase . '\/copy$/' => [
        "permission" => "{$this->fullName}/Copy",
        "action" => $this->crud['copy']['action'] ?? "UI/Form/Copy",
        "params" => array_merge($urlParams, [
          "model" => $this->fullName,
        ])
      ],

      // Search
      '/^' . $urlBase . '\/search$/' => [
        "permission" => "{$this->fullName}/Search",
        "action" => $this->crud['search']['action'] ?? "UI/Table/Search",
        "params" => array_merge($urlParams, [
          "model" => $this->fullName,
          "searchGroup" => $this->tableTitle ?? $urlBase,
          "displayMode" => "window",
          "windowParams" => [
            "modal" => TRUE,
          ],
        ])
      ],

      // Export/CSV
      '/^' . $urlBase . '\/Export\/CSV$/' => [
        "permission" => "{$this->fullName}/Export/CSV",
        "action" => "UI/Table/Export/CSV",
        "params" => array_merge($urlParams, [
          "model" => $this->fullName,
        ])
      ],

      // Import/CSV
      '/^' . $urlBase . '\/Import\/CSV$/' => [
        "permission" => "{$this->fullName}/Export/CSV",
        "action" => "UI/Table/Import/CSV",
        "params" => array_merge($urlParams, [
          "model" => $this->fullName,
        ])
      ],

      // Import/CSV/Import
      '/^' . $urlBase . '\/Import\/CSV\/Import$/' => [
        "permission" => "{$this->fullName}/Import/CSV",
        "action" => "UI/Table/Import/CSV/Import",
        "params" => array_merge($urlParams, [
          "model" => $this->fullName,
        ])
      ],

      // Import/CSV/DownloadTemplate
      '/^' . $urlBase . '\/Import\/CSV\/DownloadTemplate$/' => [
        "permission" => "{$this->fullName}/Import/CSV",
        "action" => "UI/Table/Import/CSV/DownloadTemplate",
        "params" => array_merge($urlParams, [
          "model" => $this->fullName,
        ])
      ],

      // Import/CSV/Preview
      '/^' . $urlBase . '\/Import\/CSV\/Preview$/' => [
        "permission" => "{$this->fullName}/Import/CSV",
        "action" => "UI/Table/Import/CSV/Preview",
        "params" => array_merge($urlParams, [
          "model" => $this->fullName,
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

    if (!$this->isCrossTable) {
      $newColumns['id'] = [
        'type' => 'int',
        'byte_size' => '8',
        'sql_definitions' => 'primary key auto_increment',
        'title' => 'ID',
        'only_display' => 'yes',
        'class' => 'primary-key'
      ];
    }

    // default column settings
    foreach ($columns as $colName => $colDefinition) {
      $newColumns[$colName] = $colDefinition;

      if ($colDefinition["type"] == "char") {
        $this->adios->console->info("{$this->fullName}, {$colName}: char type is deprecated");
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

    $columns = $this->adios->dispatchEventToPlugins("onModelAfterColumns", [
      "model" => $this,
      "columns" => $newColumns,
    ])["columns"];

    $this->fillable = array_keys($newColumns);

    return $newColumns;
  }

  public function columnNames()
  {
    return array_keys($this->columns());
  }

  public function indexes(array $indexes = [])
  {
    return $this->adios->dispatchEventToPlugins("onModelAfterIndexes", [
      "model" => $this,
      "indexes" => $indexes,
    ])["indexes"];
  }

  public function indexNames()
  {
    return array_keys($this->indexNames());
  }

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
  public function normalizeRowData(array $data, string $lookupKeyPrefix = "") {
    foreach ($this->columns() as $column => $columnDefinition) {
      $columnType = $columnDefinition['type'];
      if (isset($this->adios->db->columnTypes[$columnType])) {
        $data[$lookupKeyPrefix.$column] = $this->adios->db->columnTypes[$columnType]->fromString($data[$lookupKeyPrefix.$column]);
      }

      if ($columnType == 'lookup' && empty($lookupKeyPrefix)) {
        $lookupModel = $this->adios->getModel($columnDefinition['model']);
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

    $item = $this->adios->dispatchEventToPlugins("onModelAfterGetExtendedData", [
      "model" => $this,
      "item" => $item,
    ])["item"];

    return $item;
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

    if ($callback !== NULL && $callback instanceof \Closure) {
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
    unset($data['id']);

    return $this->adios->db->insert($this)
      ->set($data)
      ->execute()
    ;
  }

  public function insertRowWithId($data)
  {
    return $this->adios->db->insert($this)
      ->set($data)
      ->execute()
    ;
  }

  public function insertOrUpdateRow($data)
  {
    unset($data['id']);

    $duplicateKeyData = $data;

    return $this->adios->db->insert($this)
      ->set($data)
      ->onDuplicateKey($duplicateKeyData)
      ->execute()
    ;
  }

  public function insertRandomRow($data = [], $dictionary = [])
  {
    return $this->insertRow(
      $this->adios->db->getRandomColumnValues($this, $data, $dictionary)
    );
  }

  public function updateRow($data, $id)
  {
    $queryOk = $this->adios->db->update($this)
      ->set($data)
      ->whereId((int) $id)
      ->execute()
    ;

    return ($queryOk ? $id : FALSE);
  }

  public function deleteRow($id)
  {
    return $this->adios->db->delete($this)
      ->whereId((int) $id)
      ->execute()
    ;
  }

  public function copyRow($id)
  {
    $row = $this->adios->db->select($this)
      ->columns([\ADIOS\Core\DB\Query::allColumnsWithoutLookups])
      ->where([
        ['id', '=', (int) $id]
      ])
      ->fetchOne()
    ;

    unset($row['id']);

    return $this->insertRow($row);
  }


  public function search($q)
  {
  }

  public function pdoPrepareAndExecute(string $query, array $variables)
  {
    $q = $this->pdo->prepare(str_replace(":table", $this->getFullTableSqlName(), $query));
    return $q->execute($variables);
  }

  public function pdoPrepareExecuteAndFetch(string $query, array $variables, string $keyBy = "")
  {
    $q = $this->pdo->prepare(str_replace(":table", $this->getFullTableSqlName(), $query));
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
    return ['input_lookup_value', 'asc'];
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
  ): \ADIOS\Core\DB\Query
  {
    return $this->adios->db->select($this)
      ->columns([
        [ 'id', 'id' ],
        [ $this->lookupSqlValue($this->fullTableSqlName), 'input_lookup_value' ]
      ])
      ->where($this->lookupWhere($initiatingModel, $initiatingColumn, $formData, $params))
      ->havingRaw($having)
      ->order($this->lookupOrder($initiatingModel, $initiatingColumn, $formData, $params))
    ;
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

  public function tableParams($params, $table): ?array
  {
    return $this->adios->dispatchEventToPlugins("onModelAfterTableParams", [
      "model" => $this,
      "params" => $params,
      "table" => $table,
    ])["params"];
  }

  public function tableRowCSSFormatter($data): ?string
  {
    return $this->adios->dispatchEventToPlugins("onTableRowCSSFormatter", [
      "model" => $this,
      "data" => $data,
    ])["data"]["css"];
  }

  public function tableCellCSSFormatter($data): ?string
  {
    return $this->adios->dispatchEventToPlugins("onTableCellCSSFormatter", [
      "model" => $this,
      "data" => $data,
    ])["data"]["css"];
  }

  public function tableCellHTMLFormatter($data): ?string
  {
    return $this->adios->dispatchEventToPlugins("onTableCellHTMLFormatter", [
      "model" => $this,
      "data" => $data,
    ])["data"]["html"];
  }

  public function tableCellCSVFormatter($data): ?string
  {
    return $this->adios->dispatchEventToPlugins("onTableCellCSVFormatter", [
      "model" => $this,
      "data" => $data,
    ])["data"]["csv"];
  }

  /**
   * onTableBeforeInit
   *
   * @param mixed $tableObject
   *
   * @return void
   */
  public function onTableBeforeInit($tableObject): void
  {
  }

  /**
   * onTableAfterInit
   *
   * @param mixed $tableObject
   *
   * @return void
   */
  public function onTableAfterInit($tableObject): void
  {
  }

  /**
   * onTableAfterDataLoaded
   *
   * @param mixed $tableObject
   *
   * @return void
   */
  public function onTableAfterDataLoaded($tableObject): void
  {
  }

  //////////////////////////////////////////////////////////////////
  // UI/Form methods

  public function columnValidate(string $column, $value): bool {
    $valid = TRUE;

    $colDefinition = $this->columns()[$column] ?? [];
    $colType = $colDefinition['type'];

    if ($this->adios->db->isRegisteredColumnType($colType)) {
      $valid = $this->adios->db->columnTypes[$colType]->validate($value);
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

  public function formParams($data, $params)
  {
    return $this->adios->dispatchEventToPlugins("onModelAfterFormParams", [
      "model" => $this,
      "data" => $data,
      "params" => $params,
    ])["params"];
  }

  public function formValidate($data)
  {
    foreach ($this->columns() as $column => $colDefinition) {
     if (!$this->columnValidate($column, $data[$column])) {
        throw new \ADIOS\Core\Exceptions\FormSaveException(
          $this->adios->translate(
            "`{{ colTitle }}` contains invalid value.",
            [ 'colTitle' => $colDefinition['title'] ]
          )
        );
      } else if (
        $colDefinition['required']
        && !$this->columnValidate($column, $data[$column])
      ) {
        throw new \ADIOS\Core\Exceptions\FormSaveException(
          $this->adios->translate(
            "`{{ colTitle }}` is required.",
            [ 'colTitle' => $colDefinition['title'] ]
          )
        );
      }

    }

    return $this->adios->dispatchEventToPlugins("onModelAfterFormValidate", [
      "model" => $this,
      "data" => $data,
    ])["params"];
  }

  public function formSave($data)
  {
    try {
      $id = (int) $data['id'];

      $this->formSaveOriginalData = $data;

      $this->formValidate($data);

      if ($id <= 0) {
        $data = $this->onBeforeInsert($data);
      } else {
        $data = $this->onBeforeUpdate($data);
      }

      $data = $this->onBeforeSave($data);


      if ($id <= 0) {
        $returnValue = $this->insertRow($data);
        $data['id'] = (int) $returnValue;
      } else {
        $returnValue = $this->updateRow($data, $id);
      }

      foreach ($this->crossTableAssignments as $ctaName => $ctaParams) {
        if (!isset($data[$ctaName])) continue;

        $assignments = @json_decode($data[$ctaName], TRUE);

        if (is_array($assignments)) {
          $assignmentModel = $this->adios->getModel($ctaParams["assignmentModel"]);

          foreach ($assignments as $assignment) {
            $this->adios->db->query("
              insert into `{$assignmentModel->getFullTableSqlName()}` (
                `{$ctaParams['masterKeyColumn']}`,
                `{$ctaParams['optionKeyColumn']}`
              ) values (
                {$id},
                '" . $this->adios->db->escape($assignment) . "'
              )
              on duplicate key update `{$ctaParams['masterKeyColumn']}` = {$id}
            ");
          }

          $assignmentModel
            ->where($ctaParams['masterKeyColumn'], $id)
            ->whereNotIn($ctaParams['optionKeyColumn'], $assignments)
            ->delete();
        }
      }


      if ($id <= 0) {
        $returnValue = $this->onAfterInsert($data, $returnValue);
      } else {
        $returnValue = $this->onAfterUpdate($data, $returnValue);
      }

      $returnValue = $this->onAfterSave($data, $returnValue);

      return $returnValue;
    } catch (\ADIOS\Core\Exceptions\FormSaveException $e) {
      return $this->adios->renderHtmlFatal($e->getMessage());
    }
  }

  public function formDelete(int $id)
  {
    $id = (int) $id;

    try {
      $this->onBeforeDelete($id);
      $returnValue = $this->deleteRow($id);
      $returnValue = $this->onAfterDelete($id);
      return $returnValue;
    } catch (\ADIOS\Core\Exceptions\FormDeleteException $e) {
      return $this->adios->renderHtmlWarning($e->getMessage());
    }
  }

  //////////////////////////////////////////////////////////////////
  // UI/Cards methods

  public function cards(array $cards = [])
  {
    return $this->adios->dispatchEventToPlugins("onModelAfterCards", [
      "model" => $this,
      "cards" => $cards,
    ])["cards"];
  }

  public function cardsParams($params)
  {
    return $this->adios->dispatchEventToPlugins("onModelAfterCardsParams", [
      "model" => $this,
      "params" => $params,
    ])["params"];
  }

  public function cardsCardHtmlFormatter($cardsObject, $data)
  {
    return $this->adios->dispatchEventToPlugins("onModelAfterCardsCardHtmlFormatter", [
      "model" => $this,
      "cardsObject" => $cardsObject,
      "data" => $data,
    ])["html"];
  }

  //////////////////////////////////////////////////////////////////
  // UI/Tree methods

  public function treeParams($params)
  {
    return $this->adios->dispatchEventToPlugins("onModelAfterTreeParams", [
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
    return $this->adios->dispatchEventToPlugins("onModelBeforeInsert", [
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
    return $this->adios->dispatchEventToPlugins("onModelBeforeUpdate", [
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
    return $this->adios->dispatchEventToPlugins("onModelBeforeSave", [
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
    return $this->adios->dispatchEventToPlugins("onModelAfterInsert", [
      "model" => $this,
      "data" => $data,
      "returnValue" => $returnValue,
    ])["returnValue"];
  }

  /**
   * onModelAfterUpdate
   *
   * @param mixed $data
   * @param mixed $returnValue
   *
   * @return [type]
   */
  public function onAfterUpdate(array $data, $returnValue)
  {
    return $this->adios->dispatchEventToPlugins("onModelAfterUpdate", [
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
    return $this->adios->dispatchEventToPlugins("onModelAfterSave", [
      "model" => $this,
      "data" => $data,
      "returnValue" => $returnValue,
    ])["returnValue"];
  }



  public function onBeforeDelete(int $id): int
  {
    return $this->adios->dispatchEventToPlugins('onModelBeforeDelete', [
      'model' => $this,
      'id' => $id,
    ])['id'];
  }

  public function onAfterDelete(int $id): int
  {
    return $this->adios->dispatchEventToPlugins('onModelAfterDelete', [
      'model' => $this,
      'id' => $id,
    ])['id'];
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
          $tmpModel = $this->adios->getModel($colDef['model']);
          $lookupsToAdd[$colName] = $tmpModel->shortName;
        }
      }
    }

    foreach ($query->addedLookups as $colName => $lookupName) {
      unset($lookupsToAdd[$colName]);
    }

    $selects = [$this->getFullTableSqlName() . ".*"];
    $joins = [];

    foreach ($lookupsToAdd as $colName => $lookupName) {
      $lookupedModel = $this->adios->getModel($this->columns()[$colName]['model']);

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
        $this->getFullTableSqlName() . ".{$colName}"
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
    $crossTableModel = $this->adios->getModel($crossTableModelName);
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
}
