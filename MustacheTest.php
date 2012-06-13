<?php

require 'MustacheTemplate.php';

$tpl = MustacheTemplate::fromTemplateString('This is a {{test}}');

var_dump($tpl->__toString());
var_dump($tpl->render(array()));
var_dump($tpl->render(array('test'=>'test')));
var_dump($tpl->render(array('test'=>'test with <b>html</b>')));

echo str_repeat('-', 78)."\n";

$tpl = MustacheTemplate::fromTemplateString('This is a {{{test}}}');

var_dump($tpl->__toString());
var_dump($tpl->render(array()));
var_dump($tpl->render(array('test'=>'test')));
var_dump($tpl->render(array('test'=>'test with <b>html</b>')));

echo str_repeat('-', 78)."\n";

$tpl = MustacheTemplate::fromTemplateString('This is a {{& test}}');

var_dump($tpl->__toString());
var_dump($tpl->render(array()));
var_dump($tpl->render(array('test'=>'test')));
var_dump($tpl->render(array('test'=>'test with <b>html</b>')));

echo str_repeat('-', 78)."\n";

$tpl = MustacheTemplate::fromTemplateString('{{test}}This is a {{test}}');

var_dump($tpl->__toString());
var_dump($tpl->render(array()));
var_dump($tpl->render(array('test'=>'test')));

echo str_repeat('-', 78)."\n";

$tpl = MustacheTemplate::fromTemplateString('{{test}}This is a {{&test}}{{test}}');

var_dump($tpl->__toString());
var_dump($tpl->render(array('test'=>'test')));

echo str_repeat('-', 78)."\n";

$tpl = MustacheTemplate::fromTemplateString('{{test}}{{foo}}This is a {{&foo}}{{&test}}{{test}}{{{foo}}}');

var_dump($tpl->__toString());
var_dump($tpl->render(array('test'=>'test')));

echo str_repeat('-', 78)."\n";

$tpl = MustacheTemplate::fromTemplateString('Test {{>test}} partially {{> partial}}');

var_dump($tpl->__toString());
var_dump($tpl->render(array('test'=>'test')));

echo str_repeat('-', 78)."\n";

$tpl = MustacheTemplate::fromTemplateString('Test {{#test}}non-{{.}}-empty{{/test}}{{^test}}empty{{/test}}.');

var_dump($tpl->__toString());
var_dump($tpl->render(array()));
var_dump($tpl->render(array('test'=>'test')));

echo str_repeat('-', 78)."\n";

$tpl = MustacheTemplate::fromTemplateString('Test{{#foo}} foo{{#bar}} bar {{/bar}}foo{{/foo}}.{{#bar}} Outer bar{{/bar}}');

var_dump($tpl->__toString());
var_dump($tpl->render(array()));
var_dump($tpl->render(array('foo'=>true)));
var_dump($tpl->render(array('bar'=>true)));
var_dump($tpl->render(array('foo'=>true, 'bar'=>true)));
var_dump($tpl->render(array('foo'=>array('bar'=>true))));

echo str_repeat('-', 78)."\n";

$tpl = MustacheTemplate::fromTemplateString('start:{{#person}} [ name: {{name}} ]{{/person}}');

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

echo str_repeat('-', 78)."\n";

$tpl = MustacheTemplate::fromTemplateString('{{!comment here}}* |{{default_tags}}| @{{=<% %>=}}@ {{/<% erb_tags %>/}} +<%={{ }}=%>+ @@{{default_tags_again}}@@');
var_dump($tpl->__toString());
var_dump($tpl->render(array()));

echo str_repeat('-', 78)."\n";

$tpl = MustacheTemplate::fromTemplateString('do {{re}} {{mi.fa}} sol {{la.ti.do}}!');
var_dump($tpl->__toString());
var_dump($tpl->render(array()));
var_dump($tpl->render(array('re' => 'RAY')));
var_dump($tpl->render(array('re' => 'RAY', 'mi'=>'mi')));
var_dump($tpl->render(array('re' => 'RAY', 'mi'=>array('fa'=>'ME FAR'))));
var_dump($tpl->render(array('re' => 'RAY', 'mi'=>array('fa'=>'ME FAR'), 'la'=>array('ti'=>'LARTEE', 'do'=>'DOUGH'))));
var_dump($tpl->render(array('re' => 'RAY', 'mi'=>array('fa'=>'ME FAR'), 'la'=>array('ti'=>array('do'=>'LARTEE DOUGH')))));

echo str_repeat('-', 78)."\n";

$tpl = MustacheTemplate::fromTemplateString('sec: {{#a}}[{{b}}] ({{b.c}}) {{d}}{{/a}} {{c}}!');
var_dump($tpl->__toString());
var_dump($tpl->render(array()));
var_dump($tpl->render(array('a' => array(array('b'=>array('c'=>'C'))), 'c'=>'SEE', 'd'=>'DEE')));
