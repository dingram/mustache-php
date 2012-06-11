<?php

require 'MustacheTemplate.php';

$tpl = MustacheTemplate::fromString('This is a {{test}}');

var_dump($tpl->render(array()));
var_dump($tpl->render(array('test'=>'test')));
var_dump($tpl->render(array('test'=>'test with <b>html</b>')));

$tpl = MustacheTemplate::fromString('This is a {{{test}}}');

var_dump($tpl->render(array()));
var_dump($tpl->render(array('test'=>'test')));
var_dump($tpl->render(array('test'=>'test with <b>html</b>')));

$tpl = MustacheTemplate::fromString('This is a {{& test}}');

var_dump($tpl->render(array()));
var_dump($tpl->render(array('test'=>'test')));
var_dump($tpl->render(array('test'=>'test with <b>html</b>')));

$tpl = MustacheTemplate::fromString('{{test}}This is a {{test}}');

var_dump($tpl->render(array()));
var_dump($tpl->render(array('test'=>'test')));

$tpl = MustacheTemplate::fromString('{{test}}This is a {{&test}}{{test}}');

var_dump($tpl->render(array('test'=>'test')));
