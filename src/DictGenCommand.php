<?php

namespace x3d\dictgen;

use think\facade\Db;
use think\helper\Str;

class DictGenCommand
{
    private $config = [
        'db' => [],
        'dictgen' => [
            'type' => 'html',
        ]
    ];

    public function __construct(array $args)
    {
        $this->config = array_merge(
            $this->config,
            parse_ini_file($args['config_file'], true)
        );

        $this->connectDb($this->config['db']);
    }

    public function exec()
    {
        $tables = Db::getTables();
        $schemas = [];
        foreach ($tables as $table) {
            $fields = Db::getFields($table);
            $actualTable = $table;
            if ($this->config['db']['prefix']) {
                $actualTable = Str::substr($table, strlen($this->config['db']['prefix']));
            }
            $schemas[$actualTable] = $fields;
        }

        $type = ucfirst($this->config['dictgen']['type']);
        $class = "\\x3d\\dictgen\\renderer\\$type";
        if (empty($this->config['dictgen']['filename'])) {
            $this->config['dictgen']['filename'] = $this->config['db']['database'];
        }
        $renderer = new $class($this->config['dictgen']);
        $renderer->render($schemas);
    }

    private function connectDb(array $config)
    {
        $config = array_merge([
            'type' => 'mysql',
            'debug' => true,
        ], $config);

        Db::setConfig([
            // 默认数据连接标识
            'default' => 'db',
            // 数据库连接信息
            'connections' => [
                'db' => $config,
            ],
        ]);
    }
}
