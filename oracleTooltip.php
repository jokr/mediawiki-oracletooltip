<?php
if (!defined('MEDIAWIKI')) {
    echo("This is an extension to the MediaWiki package and cannot be run standalone.\n");
    die(-1);
}

$wgExtensionCredits['parser'][] = array(
    'path' => __FILE__,
    'name' => 'Oracle Tooltips',
    'version' => '2.0',
    'author' => 'Thomas Marchiori, Joel Krebs',
    'url' => 'http://wiki.magicjudges.org/',
    'description' => 'This is an extension to link oracle text of Magic: The Gathering cards to the oracle and display a tooltip.'
);

$wgHooks['ParserFirstCallInit'][] = 'parserInit';
$wgHooks['BeforePageDisplay'][] = 'loadResources';

$wgResourceModules['ext.oracleTooltip'] = array(
    'scripts' => 'js/ext.oracleTooltip.js',
    'styles' => 'css/ext.oracleTooltip.css',
    'dependencies' => 'jquery.ui.tooltip',
    'localBasePath' => __DIR__,
    'remoteExtPath' => 'OracleTooltip'
);

function parserInit(Parser $parser)
{
    $parser->setHook('c', 'render');
    return true;
}

function loadResources(OutputPage $out)
{
    $out->addModules(array('ext.oracleTooltip', 'prototype'));
    return true;
}

function render($input, array $parameters, Parser $parser)
{
    $server = $parser->getVariableValue('server');
    $script = $parser->getVariableValue('scriptpath');
    return '<a class="mtg-card" src="'.$server.$script.'/extensions/OracleTooltip/txtoracle.php">' . htmlspecialchars($input) . '</a>';
}