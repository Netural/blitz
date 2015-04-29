<?php 

// Example

header("Access-Control-Allow-Origin: *");

$lang = 'en';

if (isset($_GET['draft'])) {
    $data = file_get_contents('../web/data/example/en/data[draft].json');
} else {
    $data = file_get_contents('../web/data/example/en/data.json');
}

$data = json_decode($data, true);

if (empty($data) || isset($data['error'])) {
	echo 'Failed to load JSON';
	exit;
}

require('libs/lightncandy.php');

$template = file_get_contents('template.hbs');
$compiled = LightnCandy::compile($template, array(
    'flags' => LightnCandy::FLAG_HANDLEBARS
));

$renderer = LightnCandy::prepare($compiled);
echo $renderer($data);