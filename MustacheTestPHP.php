<?php

require 'MustacheTemplate.php';
require 'MustacheRendererPHP.php';

$tpl = MustacheTemplate::fromTemplateString('This is a {{test}}');
$rdr = MustacheRendererPHP::create($tpl);

var_dump($tpl->__toString());
var_dump($rdr->render(array()));

echo str_repeat('-', 78)."\n";

$tpl = MustacheTemplate::fromTemplateString('This is a {{{test}}}');
$rdr = MustacheRendererPHP::create($tpl);

var_dump($tpl->__toString());
var_dump($rdr->render(array()));

echo str_repeat('-', 78)."\n";

$tpl = MustacheTemplate::fromTemplateString('This is a <?php unlink(\'/\') ?> {{test}}');
$rdr = MustacheRendererPHP::create($tpl);

var_dump($tpl->__toString());
var_dump($rdr->render(array()));

echo str_repeat('-', 78)."\n";

$tpl = MustacheTemplate::fromTemplateString('{{#section}}[ this is {{adjective}} ] {{/section}}');
$rdr = MustacheRendererPHP::create($tpl);

var_dump($tpl->__toString());
var_dump($rdr->render(array()));
