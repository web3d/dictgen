<?php

namespace x3d\dictgen\renderer;

use think\Exception;
use think\helper\Str;

class Html
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
        $html = <<<EOF
<!doctype html>
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" integrity="sha384-HSMxcRTRxnN+Bdg0JdbxYKrThecOKuH5zCYotlSAcp1+c8xmyTe9GYg1l9a69psu" crossorigin="anonymous">
<style>
body { padding-top: 70px; }
table > caption {
text-align: center;
font-weight: bold;
margin-top: 70px;
}
.dropdown-menu {
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
        
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">查看表 <span class="caret"></span></a>
          <ul class="dropdown-menu">
EOF;
        foreach ($schemas as $table => $fields) {
            $html .= <<<EOF
            <li><a href="#table-{$table}">{$table}</a></li>
EOF;
        }
            $html .= <<<EOF
          </ul>
        </li>
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
<script src="https://cdn.bootcdn.net/ajax/libs/jquery/3.6.1/jquery.min.js" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js" integrity="sha384-aJ21OjlMXNL5UyIl/XNwTMqvzeRMZH2w8c5cRVpzpU8Y5bApTppSuUkhZXN0VxHd" crossorigin="anonymous"></script>
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
