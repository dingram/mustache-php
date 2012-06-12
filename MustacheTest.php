<?php

require 'MustacheTemplate.php';

$tpl = MustacheTemplate::fromString('This is a {{test}}');

var_dump($tpl->__toString());
var_dump($tpl->render(array()));
var_dump($tpl->render(array('test'=>'test')));
var_dump($tpl->render(array('test'=>'test with <b>html</b>')));

echo str_repeat('-', 78)."\n";

$tpl = MustacheTemplate::fromString('This is a {{{test}}}');

var_dump($tpl->__toString());
var_dump($tpl->render(array()));
var_dump($tpl->render(array('test'=>'test')));
var_dump($tpl->render(array('test'=>'test with <b>html</b>')));

echo str_repeat('-', 78)."\n";

$tpl = MustacheTemplate::fromString('This is a {{& test}}');

var_dump($tpl->__toString());
var_dump($tpl->render(array()));
var_dump($tpl->render(array('test'=>'test')));
var_dump($tpl->render(array('test'=>'test with <b>html</b>')));

echo str_repeat('-', 78)."\n";

$tpl = MustacheTemplate::fromString('{{test}}This is a {{test}}');

var_dump($tpl->__toString());
var_dump($tpl->render(array()));
var_dump($tpl->render(array('test'=>'test')));

echo str_repeat('-', 78)."\n";

$tpl = MustacheTemplate::fromString('{{test}}This is a {{&test}}{{test}}');

var_dump($tpl->__toString());
var_dump($tpl->render(array('test'=>'test')));

echo str_repeat('-', 78)."\n";

$tpl = MustacheTemplate::fromString('{{test}}{{foo}}This is a {{&foo}}{{&test}}{{test}}{{{foo}}}');

var_dump($tpl->__toString());
var_dump($tpl->render(array('test'=>'test')));

echo str_repeat('-', 78)."\n";

$tpl = MustacheTemplate::fromString('Test {{>test}} partially {{> partial}}');

var_dump($tpl->__toString());
var_dump($tpl->render(array('test'=>'test')));

echo str_repeat('-', 78)."\n";

$tpl = MustacheTemplate::fromString('Test {{#test}}non-{{.}}-empty{{/test}}{{^test}}empty{{/test}}.');

var_dump($tpl->__toString());
var_dump($tpl->render(array()));
var_dump($tpl->render(array('test'=>'test')));

echo str_repeat('-', 78)."\n";

$tpl = MustacheTemplate::fromString('Test{{#foo}} foo{{#bar}} bar {{/bar}}foo{{/foo}}.{{#bar}} Outer bar{{/bar}}');

var_dump($tpl->__toString());
var_dump($tpl->render(array()));
var_dump($tpl->render(array('foo'=>true)));
var_dump($tpl->render(array('bar'=>true)));
var_dump($tpl->render(array('foo'=>true, 'bar'=>true)));
var_dump($tpl->render(array('foo'=>array('bar'=>true))));

echo str_repeat('-', 78)."\n";

$tpl = MustacheTemplate::fromString('start:{{#person}} [ name: {{name}} ]{{/person}}');

var_dump($tpl->__toString());
var_dump($tpl->render(array()));
var_dump($tpl->render(array('person'=>array(
	array('name' => 'Alice'),
))));
var_dump($tpl->render(array('person'=>array(
	array('name' => 'Alice'),
	array('name' => 'Bob'),
	array('name' => 'Carol'),
	array('name' => 'Derek'),
	array('name' => 'Edna'),
))));
