<?php

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Database Configuration
 */
class Database extends Config
{
    /**
     * The directory that holds the Migrations and Seeds directories.
     */
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

    /**
     * Lets you choose which connection group to use if no other is specified.
     */
    public string $defaultGroup = 'default';

    /**
     * The default database connection.
     *
     * @var array<string, mixed>
     */
    public array $default = [
        'DSN'          => '',
        'hostname'     => 'mariadb',
        'username'     => 'root',
        'password'     => 'secret',
        'database'     => 'free_youtube',
        'DBDriver'     => 'MySQLi',
        'DBPrefix'     => '',
        'pConnect'     => false,
        'DBDebug'      => true,
        'charset'      => 'utf8mb4',
        'DBCollat'     => 'utf8mb4_unicode_ci',
        'swapPre'      => '',
        'encrypt'      => false,
        'compress'     => false,
        'strictOn'     => false,
        'failover'     => [],
        'port'         => 3306,
        'numberNative' => false,
        'dateFormat'   => [
            'date'     => 'Y-m-d',
            'datetime' => 'Y-m-d H:i:s',
            'time'     => 'H:i:s',
        ],
    ];

    /**
     * This database connection is used when running PHPUnit database tests.
     *
     * @var array<string, mixed>
     */
    public array $tests = [
        'DSN'         => '',
        'hostname'    => '127.0.0.1',
        'username'    => '',
        'password'    => '',
        'database'    => ':memory:',
        'DBDriver'    => 'SQLite3',
        'DBPrefix'    => 'db_',
        'pConnect'    => false,
        'DBDebug'     => true,
        'charset'     => 'utf8mb4',
        'DBCollat'    => '',
        'swapPre'     => '',
        'encrypt'     => false,
        'compress'    => false,
        'strictOn'    => false,
        'failover'    => [],
        'port'        => 3306,
        'foreignKeys' => true,
        'busyTimeout' => 1000,
        'dateFormat'  => [
            'date'     => 'Y-m-d',
            'datetime' => 'Y-m-d H:i:s',
            'time'     => 'H:i:s',
        ],
    ];

    public function __construct()
    {
        parent::__construct();

        // Ensure that we always set the database group to 'tests' if
        // we are currently running an automated test suite, so that
        // we don't overwrite live data on accident.
        if (ENVIRONMENT === 'testing') {
            $this->defaultGroup = 'tests';
        }

        // 從環境變數讀取資料庫設定
        $this->default['hostname'] = getenv('database.default.hostname') ?: $this->default['hostname'];
        $this->default['username'] = getenv('database.default.username') ?: $this->default['username'];
        $this->default['password'] = getenv('database.default.password') ?: $this->default['password'];
        $this->default['database'] = getenv('database.default.database') ?: $this->default['database'];
        $this->default['DBDriver'] = getenv('database.default.DBDriver') ?: $this->default['DBDriver'];
        $this->default['DBPrefix'] = getenv('database.default.DBPrefix') ?: $this->default['DBPrefix'];
        $this->default['port']     = (int)(getenv('database.default.port') ?: $this->default['port']);
        $this->default['charset']  = getenv('database.default.charset') ?: $this->default['charset'];
        $this->default['DBCollat'] = getenv('database.default.DBCollat') ?: $this->default['DBCollat'];

        // 在開發和測試環境啟用 debug，生產環境關閉
        $this->default['DBDebug'] = ENVIRONMENT !== 'production';
    }
}
