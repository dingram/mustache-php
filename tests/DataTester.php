#!/usr/bin/env php
<?php

require '../src/Template.php';
require '../src/Renderer.php';
require '../src/RendererPHP.php';


class TestMustacheRenderer extends \Mustache\Renderer
{
	protected function fetchPartialContent($partial)
	{
		return "«partial {$partial}»";
	}
}


class DataTester
{
	protected $test_id;
	protected $data_id;
	protected $tests = 0;
	protected $passed = 0;
	protected $failed = 0;
	protected $failures = array();

	const NO_TEST = 'no_test';
	const NO_DATA = 'no_data';

	public function __construct($test_id = null, $data_id = null)
	{
		$this->test_id = $test_id;
		$this->data_id = $data_id;
	}

	protected function pass()
	{
		++$this->tests;
		++$this->passed;
		echo ".";
	}

	protected function fail($test_id, $data_id, $expected, $actual)
	{
		++$this->tests;
		++$this->failed;
		$this->failures[] = array(
			'test_id' => $test_id,
			'data_id' => $data_id,
			'expected' => $expected,
			'actual' => $actual,
		);
		echo "F";
	}

	protected function getData($test_id, $data_id)
	{
		$test_path = __DIR__.'/data/test-%04d.tpl';
		$data_path = __DIR__.'/data/test-%04d-%03d.%s';

		$tpl_path  = sprintf($test_path, $test_id);
		$json_path = sprintf($data_path, $test_id, $data_id, 'json');
		$out_path  = sprintf($data_path, $test_id, $data_id, 'out');
		$err_path  = sprintf($data_path, $test_id, $data_id, 'err');

		if (!file_exists($tpl_path)) {
			return static::NO_TEST;
		}
		if (!file_exists($json_path)) {
			return static::NO_DATA;
		}
		$out_exists = file_exists($out_path);
		$err_exists = file_exists($err_path);
		if (!$out_exists && !$err_exists) {
			return static::NO_DATA;
		}
		$ret = array();
		$ret['tpl'] = file_get_contents($tpl_path);
		$ret['json'] = json_decode(file_get_contents($json_path), true);
		if (!is_array($ret['json'])) {
			return static::NO_DATA;
		}
		if ($out_exists) {
			$ret['out'] = file_get_contents($out_path);
		}
		if ($err_exists) {
			$ret['err'] = file_get_contents($err_path);
		}
		return $ret;
	}

	protected function runTestData($test_id, $data_id)
	{
		$testdata = $this->getData($test_id, $data_id);
		if (!is_array($testdata)) {
			return $testdata;
		}
		try {
			$tpl = \Mustache\Template::fromTemplateString($testdata['tpl']);
			$rdr = TestMustacheRenderer::create($tpl);
			$result = $rdr->render($testdata['json']);
			if (!isset($testdata['err']) && $result === $testdata['out']) {
				$this->pass();
				return true;
			} else {
				if (isset($testdata['err'])) {
					$this->fail($test_id, $data_id, '<error>', $result);
				} else {
					$this->fail($test_id, $data_id, $testdata['out'], $result);
				}
				return false;
			}
		} catch (\Exception $e) {
			if (!isset($testdata['err'])) {
				$this->fail($test_id, $data_id, $testdata['out'], '<'.get_class($e).': '.$e->getMessage().'>');
				return false;
			} else {
				// TODO: check exception class/message
				$this->pass();
				return true;
			}
		}
	}

	protected function runTest($test_id)
	{
		$result = static::NO_TEST;
		$data_id = 1;
		do {
			$result = $this->runTestData($test_id, $data_id);
			if (($this->tests % 40) == 0) {
				echo "\n";
			} elseif (($this->tests % 10) == 0) {
				echo " ";
			}
			++$data_id;
		} while (is_bool($result));
		return $result;
	}

	protected function runTests()
	{
		$test_id = 1;
		do {
			$result = $this->runTest($test_id);
			++$test_id;
		} while ($result !== static::NO_TEST);
	}

	public function run()
	{
		$this->tests = 0;
		$this->passed = 0;
		$this->failed = 0;

		if (is_null($this->test_id)) {
			$this->runTests();
		} elseif (is_null($this->data_id)) {
			$this->runTest($this->test_id);
		} else {
			$this->runTestData($this->test_id, $this->data_id);
		}

		echo "\n\n";
		printf("%4d tests run:\n", $this->tests);
		printf("  %4d passed\n", $this->passed);
		printf("  %4d failed\n", $this->failed);
	}

}

$t = new DataTester();
$t->run();
