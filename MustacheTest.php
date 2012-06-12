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

$tpl = MustacheTemplate::fromString('Test {{#test}}passed{{/test}}{{^test}}failed{{/test}}.');

var_dump($tpl->__toString());
var_dump($tpl->render(array()));
var_dump($tpl->render(array('test'=>'test')));
