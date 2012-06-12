<?php

abstract class MustacheCache
{
	protected $fallback = null;

	public function __construct($fallback = null) {
		$this->setFallback($fallback);
	}

	public abstract function get($key);
	public abstract function put($key, $data, $lifetime = null, $flags = null);
	public abstract function purge($key);

	protected function fallbackGet($key)
	{
		$f = $this->getFallback();
		if (is_callable($f)) {
			return $f($key);
		}
		if ($f instanceof MustacheCache) {
			return $f->get($key);
		}
		return null;
	}

	protected function fallbackPut($key, $data, $lifetime = null, $flags = null)
	{
		$f = $this->getFallback();
		if ($f instanceof MustacheCache) {
			return $f->put($key, $data, $lifetime, $flags);
		}
		return false;
	}

	protected function fallbackPurge($key)
	{
		$f = $this->getFallback();
		if ($f instanceof MustacheCache) {
			return $f->purge($key);
		}
		return false;
	}

	public function getFallback()
	{
		return $this->fallback;
	}

	public function setFallback($fallback)
	{
		if (!is_null($fallback) && !is_callable($fallback) && !$fallback instanceof MustacheCache) {
			throw new InvalidArgumentException('Fallback must either be null, callable, or a subclass of MustacheCache');
		}
		$this->fallback = $fallback;
		return $this;
	}

	public function setUltimateFallback($fallback)
	{
		if (!is_null($fallback) && !is_callable($fallback) && !$fallback instanceof MustacheCache) {
			throw new InvalidArgumentException('Fallback must either be null, callable, or a subclass of MustacheCache');
		}
		if ($this->fallback instanceof MustacheCache) {
			$this->fallback->setUltimateFallback($fallback);
		} else {
			$this->fallback = $fallback;
		}
		return $this;
	}

}
