<?php

require 'vendor/autoload.php';

$argv = Console_Getopt::readPHPArgv();

$allowed_short_opts = 'c:';

$parsed_result = Console_Getopt::getopt($argv, $allowed_short_opts);
if(PEAR::isError($parsed_result))
{
    echo $parsed_result->getMessage()."\n";
    echo 'FATAL';
    exit;
}

$args = [
    'config_file' => 'config.ini',
];
for ($i = 0; $i < count($parsed_result[0]); $i++) {
    $item = $parsed_result[0][$i];
    if ($item[0] == 'c') {
        $args['config_file'] = $item[1];
    } /* elseif ($item[0] == 'o') {
        $args['output_dir'] = $item[1];
    } */
}

$cmd = new \x3d\dictgen\DictGenCommand($args);
$cmd->exec();
