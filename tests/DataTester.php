#!/usr/bin/env php
<?php

require(dirname(__DIR__).'/src/Template.php');
require(dirname(__DIR__).'/src/Renderer.php');
require(dirname(__DIR__).'/src/RendererPHP.php');


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
	protected $tests = 0;
	protected $passed = 0;
	protected $failed = 0;
	protected $failures = array();
	protected $show_failures = false;

	const NO_TEST = 'no_test';
	const NO_DATA = 'no_data';

	public function __construct($test_id = null, $show_failures = false)
	{
		$this->test_id = $test_id;
		$this->show_failures = $show_failures;
	}

	protected function pass()
	{
		++$this->tests;
		++$this->passed;
		echo ".";
	}

	protected function fail($test_id, $expected, $actual)
	{
		++$this->tests;
		++$this->failed;
		$this->failures[] = array(
			'test_id' => $test_id,
			'expected' => $expected,
			'actual' => $actual,
		);
		echo "F";
	}

	protected function getData($test_id)
	{
		$test_path = sprintf(__DIR__.'/data/test-%04d.json', $test_id);
		if (!file_exists($test_path)) {
			return static::NO_TEST;
		}
		$ret = json_decode(file_get_contents($test_path), true);
		if (!is_array($ret)) {
			return static::NO_TEST;
		}
		return $ret;
	}

	protected function runTest($test_id)
	{
		$testdata = $this->getData($test_id);
		if (!is_array($testdata)) {
			return $testdata;
		}
		try {
			$tpl = \Mustache\Template::fromTemplateString($testdata['tpl']);
			$rdr = TestMustacheRenderer::create($tpl);
			$result = $rdr->render($testdata['data']);
			if (!isset($testdata['err']) && $result === $testdata['out']) {
				$this->pass();
				return true;
			} else {
				if (isset($testdata['err'])) {
					$this->fail($test_id, '<error>', $result);
				} else {
					$this->fail($test_id, $testdata['out'], $result);
				}
				return false;
			}
		} catch (\Exception $e) {
			if (!isset($testdata['err'])) {
				$this->fail($test_id, $testdata['out'], '<'.get_class($e).': '.$e->getMessage().'>');
				return false;
			} else {
				// TODO: check exception class/message
				$this->pass();
				return true;
			}
		}
	}

	protected function runTests()
	{
		$test_id = 1;
		do {
			$result = $this->runTest($test_id);
			++$test_id;
			if (($this->tests % 40) == 0) {
				echo "\n";
			} elseif (($this->tests % 10) == 0) {
				echo " ";
			}
		} while ($result !== static::NO_TEST);
	}

	public function run()
	{
		$this->tests = 0;
		$this->passed = 0;
		$this->failed = 0;

		if (!is_null($this->test_id)) {
			$this->runTest($this->test_id);
		} else {
			$this->runTests();
		}

		echo "\n\n";
		printf("%4d tests run:\n", $this->tests);
		printf("  %4d passed\n", $this->passed);
		printf("  %4d failed\n", $this->failed);

		if ($this->failures && $this->show_failures) {
			print "\n";
			foreach ($this->failures as $failure) {
				print str_repeat('-', 72) . "\n";
				printf("Test %04d failed\n", $failure['test_id']);
				print "Expected:\n{$failure['expected']}\n";
				print "Actual:\n{$failure['actual']}\n";
			}
		}
	}

}

$t = new DataTester(null, count($argv) > 1);
$t->run();
