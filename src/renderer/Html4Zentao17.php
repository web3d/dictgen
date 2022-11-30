<?php

namespace x3d\dictgen\renderer;

use think\Exception;
use think\helper\Str;

class Html4Zentao17
{
    private $config = [
        'mode' => 'single', // single splitted
        'filename' => '', // 可选，单文件模式时需要
    ];

    public function __construct(array $config)
    {
        $this->config = array_merge($this->config, $config);
    }

    public function render(array $schemas)
    {
        if ($this->config['mode'] == 'splitted') {
            $this->renderSplitted($schemas);
        } else {
            $this->renderSingle($schemas);
        }
    }

    private function renderSingle(array $schemas)
    {
        $uselessTables = [
            'account', 'domain',
        ];
        $modules = [
            '-',
            'user',
            'product', 'program', 'project',
            'risk', 'story', 'case', 'task', 'todo',
            'test', 'release', 'doc',
            'stakeholder',
            'api', 'base', 'feedback',
            'kanban', 'meas',
            'audit', 'deploy', 'mr', 'repo', 'vm',
            'issue', 'review', 'ticket', 'faq',
            'workflow',
            'im', 'asset', 'approval', 'train',
            'attend', 'leave', 'lieu',
            'overtime', 'trip', 'meeting', 'holiday',
            'sys_', 'crm_',
            'view_', 'ztv_',
        ];
        $schemasByModule = [];
        $subModulesByGroup = [
            'pm' => [
                'product', 'program', 'project',
                'risk', 'story', 'case', 'task',
                'test', 'release', 'doc', 'stakeholder', 'todo',
            ],
            'oa' => [
                'approval', 'meeting', 'attend',
                'leave', 'overtime', 'trip',
                'train', 'lieu', 'asset',
                'activity', 'holiday',
            ],
            'ci' => [
                'deploy', 'mr', 'repo',
                'audit', 'review', 'issue', 'vm',
            ],
            'other' => [
                'ticket', 'api', 'base', 'faq',
                'feedback', 'meas',
                'view_', 'ztv_',
            ],
        ];
        // 加到最前
        foreach ($subModulesByGroup as $subModuleGroup => $_subModules) {
            $schemasByModule[$subModuleGroup] = [];
        }
        $allSubModules = array_merge(...array_values($subModulesByGroup));
        foreach ($modules as $module) {
            // 除 oa等模块外的放到二级
            if (in_array($module, $allSubModules)) {
                continue;
            }

            $schemasByModule[$module] = [];
        }

        $fixedModuleTables = [
            'meas' => [
                'basicmeas',
            ],
            'product' => [
                'planstory', 'stage', 'branch',
                'module',
            ],
            'project' => [
                'budget', 'build', 'burn',
                'cfd', 'durationestimation', 'intervention',
                'team', 'workestimation', 'expect',

            ],
            'case' => [
                'suitecase',
            ],
            'test' => [
                'bug', 'solutions',
            ],
            'stakeholder' => [
                'effort', 'opportunity', 'researchplan', 'researchreport',
            ],
            'audit' => [
                'nc',
            ],
            'deploy' => [
                'host', 'pipeline', 'serverroom',
                'compile', 'service', 'domain', 'job',
            ],
            'user' => [
                'dept', 'group', 'grouppriv', 'score', 'oauth',
            ],
            'kanban' => [
                'design', 'designspec',
            ],
        ];
        $allFixedModuleTables = array_merge(...array_values($fixedModuleTables));

        // 先确定表所直属的模块
        $tables = array_keys($schemas);
        foreach ($tables as $table) {
            $matched = false;
            $module = '';
            // 先匹配指定所属模块的表
            if (in_array($table, $allFixedModuleTables)) {
                foreach ($fixedModuleTables as $_module => $tables) {
                    if (in_array($table, $tables)) {
                        $module = $_module;
                        break;
                    }
                }
            } else { // 再按表名前缀匹配模块
                // 按前缀匹配模块，没有分隔符，不精确，如 repo report
                foreach ($modules as $_module) {
                    // 匹配不到模块的，就挂到默认模块下
                    if (substr($table, 0, strlen($_module)) === $_module) {
                        $module = $_module;
                        break;
                    }
                }
            }
            if (!$module) { // 匹配不到就放到一个统一的值下
                $module = '-';
            }

            $schemasByModule[$module][$table] = $schemas[$table];
        }

        // 再处理多级模块
        foreach ($schemasByModule as $_module => $_schemas) {
            // 反向匹配所在二级模块
            if (!in_array($_module, $allSubModules)) {
                continue;
            }

            // 放到三级下
            foreach ($subModulesByGroup as $subModuleGroup => $_subModules) {
                if (!in_array($_module, $_subModules)) {
                    continue;
                }

                $schemasByModule[$subModuleGroup][$_module] = $_schemas;
                unset($schemasByModule[$_module]);
                break;
            }

        }

        $html = <<<EOF
<!doctype html>
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/zui/1.10.0/css/zui.min.css">

<style>
body { padding-top: 70px; }
table > caption {
text-align: center;
font-weight: bold;
margin-top: 70px;
}

#mod-- > .dropdown-menu {
	max-height: calc( 100vh - 100px );
	overflow-y: auto;
}
</style>
</head>
<body>
<div class="container">
  <div class="row">
<nav class="navbar navbar-inverse navbar-fixed-top">
  <div class="container">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="#">{$this->config['filename']}</a>
    </div>

    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav">
EOF;
        foreach ($schemasByModule as $module => $_schemas) {
            $html .= <<<EOF
        <li class="dropdown dropdown-hover" id="mod-{$module}">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">{$module} <span class="caret"></span></a>
          <ul class="dropdown-menu">
EOF;

            if (in_array($module, array_keys($subModulesByGroup))) {
                foreach ($_schemas as $subModule => $__schemas) {
                    $html .= <<<EOF
            <li class="dropdown-submenu">
            <a href="javascript:void(0)">{$subModule}</a>
            <ul class="dropdown-menu">
EOF;
                    foreach ($__schemas as $table => $fields) {
                        $html .= <<<EOF
            <li><a href="#table-{$table}">{$table}</a></li>
EOF;
                    }
                    $html .= <<<EOF
            </ul>
            </li>
EOF;
                }
            } else {
                foreach ($_schemas as $table => $fields) {
                    $html .= <<<EOF
            <li><a href="#table-{$table}">{$table}</a></li>
EOF;
                }
            }
            $html .= <<<EOF
          </ul>
        </li>
 EOF;
        }
        $html .= <<<EOF
      </ul>

    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>
EOF;
        foreach ($schemas as $table => $fields) {
            $html .= $this->renderTable($table, $fields);
        }
        $html .= <<<EOF
</div>
</div>
<!-- ZUI Javascript 依赖 jQuery -->
<script src="//cdnjs.cloudflare.com/ajax/libs/zui/1.10.0/lib/jquery/jquery.js"></script>
<!-- ZUI 标准版压缩后的 JavaScript 文件 -->
<script src="//cdnjs.cloudflare.com/ajax/libs/zui/1.10.0/js/zui.min.js"></script>
</body>
</html>
EOF;
//        echo $html;

        $output_dir = $this->config['output_dir'];
        $file = $this->config['filename'] . '.html';

        file_put_contents($output_dir . '/' . $file, $html);
    }

    private function renderSplitted(array $schemas)
    {

    }

    private function renderTable($table, $fields)
    {
        $html = <<<EOF
<a id="table-{$table}"></a>
<table class="table table-striped table-hover">
<caption>$table</caption>
<thead>
<th style="width: 3em">序号</th>
<th style="width: 20%;">字段</th>
<th style="width: 20%;">字段名</th>
<th style="width: 20%;">类型</th>
<th style="width: 10%;">可否为空</th>
<th>说明</th>
</thead>
<tbody>
EOF;
        $i = 1;
        foreach ($fields as $key => $field) {
            $excluded_words = ['ID' => 'Id', 'NAME' => 'Name'];
            if ($field['comment']) {
                $label = $field['comment'];
            } else {
                $label = str_replace(array_keys($excluded_words), array_values($excluded_words), $field['name']);
                $label = Str::title(str_replace('_', ' ', Str::snake($label)));
            }
            $notnull = $field['notnull'] ? '否' : '是';
            $html .= <<<EOF
<tr>
<td>$i</td>
<td>{$field['name']}</td>
<td>{$label}</td>
<td>{$field['type']}</td>
<td>{$notnull}</td>
<td></td>
</tr>
EOF;
            $i++;
        }
        $html .= <<<EOF
</tbody>
</table>
EOF;

        return $html;
    }
}
