<?php

use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;
use Joomla\CMS\Version;

/** @var CMSApplication $app */
$app = Factory::getApplication();

$version = new Version();
$JoomlaVersion = (float) $version->getShortVersion();

$input = $JoomlaVersion < 4 ? $app->input : $app->getInput();
$content = $input->get('system_editor_data', '', 'raw');

$config = ApplicationHelper::getAppConfig();

$type = $config->get('editor');
$editor = Editor::getInstance($type);
$exclude = ['pagebreak', 'readmore'];

?>

<?php echo $editor->display('content', $content, '100%', 'max(calc(100vh - 300px), 600px)', '', '30', $exclude); ?>