<?php

class MustacheTemplate
{
	const RI_TEXT       = 1;
	const RI_GZTEXT     = 2;
	const RI_VAR        = 3;
	const RI_RAWVAR     = 4;
	const RI_SECTION    = 5;
	const RI_INVSECTION = 6;
	const RI_PARTIAL    = 7;
	const RI_PRAGMA     = 8;

	protected $vars = array();
	protected $partials = array();
	protected $renderlist = array();

	public static function fromString($string)
	{
		$obj = new static();
		$obj->setTemplateContent($string);
		return $obj;
	}

	public static function fromFile($filename)
	{
		return static::fromString(file_get_contents($filename));
	}

	public function getVariables()
	{
		$this->compile();
		return array_keys($this->vars);
	}

	public function getPartials()
	{
		$this->compile();
		return array_keys($this->partials);
	}

	protected function setTemplateContent($content)
	{
		$this->template = $content;
	}

	protected function compile()
	{
		if ($this->renderlist) {
			return;
		}
		$otag = '{{';
		$ctag = '}}';

		$ris = array();
		$t = $this->template;
		$s = 0;
		do {
			// snaffle text up to next opening tag
			$e = mb_strpos($t, $otag, $s);
			if ($e === false) {
				$ci = array(static::RI_TEXT, substr($t, $s));
			} else {
				$ci = array(static::RI_TEXT, substr($t, $s, $e-$s));
				$ris[] = $ci;
				// gobble the opening delimiter
				$s = $e + mb_strlen($otag);
				// find the tag name
				$e = mb_strpos($t, $ctag, $s);
				$tag = substr($t, $s, $e-$s);
				$tag_type = static::RI_VAR;
				// gobble the closing delimiter
				$s = $e + mb_strlen($ctag);
				// if it's triple-brace, gobble an extra closing brace
				if ($tag[0] === '{' && $t[$s] === '}') {
					++$s;
					$tag = substr($tag, 1);
					$tag_type = static::RI_RAWVAR;
				}
				if ($tag[0] === '&') {
					$tag = substr($tag, 1);
					$tag_type = static::RI_RAWVAR;
				}
				$tag = trim($tag);
				$ci = array($tag_type, $tag);
			}
			$ris[] = $ci;
		} while ($e !== false);

		$this->renderlist = $ris;
		return $ris;
	}

	protected function lookupVar($var, array &$context, $encode = true)
	{
		if ($var === '.') {
			return $context['data'];
		}
		$curcontext =& $context;
		while (!array_key_exists($var, $curcontext['data'])) {
			if (!isset($curcontext['outer'])) {
				// no outer context; variable is not defined
				return null;
			} else {
				$curcontext =& $curcontext['outer'];
			}
		}
		if ($encode) {
			return htmlspecialchars($curcontext['data'][$var], ENT_QUOTES, 'UTF-8');
		} else {
			return $curcontext['data'][$var];
		}
	}

	protected function &nestContext($new_values, &$old_context = null)
	{
		$v = array(
			'outer' => $old_context,
			'data' => $new_values
		);
		return $v;
	}

	protected function renderSection($section, array &$render_instructions, array &$context, array &$opts)
	{
		if ($section === array() || $section === false || $section === 0 || $section === '') {
			// falsy values -> empty string
			return '';
		}
		if (is_array($section) || $section instanceof Traversable) {
			// iterate over the item, rendering each time
			$output = '';
			foreach ($section as $item) {
				if (is_array($item)) {
					$output .= $this->renderList($render_instructions, $this->nestContext($item, $context), $opts);
				} else {
					$output .= $this->renderList($render_instructions, $context, $opts);
				}
			}
			return $output;
		}
	}

	protected function renderPartial($partial, array &$context, array &$opts)
	{
		return "«partial {$partial}»";
	}

	protected function renderList(array &$render_instructions, array &$context, array &$opts)
	{
		$output = '';
		foreach ($render_instructions as $ri) {
			switch ($ri[0]) {
				case static::RI_TEXT:
					// simple text block; use it directly
					$output .= $ri[1];
					break;
				case static::RI_GZTEXT:
					// gzipped text block; ungzip and use it directly
					$output .= gzuncompress($ri[1]);
					break;
				case static::RI_VAR:
					// HTML-encoded variable replacement
					$output .= $this->lookupVar($ri[1], $context, $opts['encode_default']);
					break;
				case static::RI_RAWVAR:
					// raw (non-encoded) variable replacement
					$output .= $this->lookupVar($ri[1], $context, !$opts['encode_default']);
					break;
				case static::RI_SECTION:
					$output .= $this->renderSection($this->lookupVar($ri[1], $context, false), $ri[2], $context, $opts);
					break;
				case static::RI_INVSECTION:
					$output .= $this->renderSection(!$this->lookupVar($ri[1], $context, false), $ri[2], $context, $opts);
					break;
				case static::RI_PARTIAL:
					$output .= $this->renderPartial($ri[1], $context, $opts);
					break;
				case static::RI_PRAGMA:
					// Execute a pragma instruction. For now, do nothing.
					break;
				default:
					break;
			}
		}
		return $output;
	}

	public function render(array $data)
	{
		$this->compile();
		$opts = array(
			'encode_default' => true,
		);
		return $this->renderList($this->renderlist, $this->nestContext($data), $opts);
	}
}
