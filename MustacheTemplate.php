<?php

/**
 * This class represents a Mustache-format template.
 */
class MustacheTemplate
{
	/** Minimum number of bytes of raw text before it's gzipped */
	const COMPRESS_LIMIT = 1024;

	/** Render opcode: uninterpreted text */
	const RI_TEXT       = 1;
	/** Render opcode: gzipped uninterpreted text */
	const RI_GZTEXT     = 2;
	/** Render opcode: regular (HTML-escaped) variable replacement */
	const RI_VAR        = 3;
	/** Render opcode: raw (non-escaped) variable replacement */
	const RI_RAWVAR     = 4;
	/** Render opcode: section */
	const RI_SECTION    = 5;
	/** Render opcode: inverted section */
	const RI_INVSECTION = 6;
	/** Render opcode: partial */
	const RI_PARTIAL    = 7;
	/** Render opcode: pragma */
	const RI_PRAGMA     = 8;
	/** Render opcode: capture */
	const RI_CAPTURE    = 9;

	/** @var string The original template */
	protected $template = '';
	/** @var array A hashtable of variables used in the template. Key = variable name, value = times used */
	protected $vars = array();
	/** @var array A hashtable of partials. Key = partial name, value = times used */
	protected $partials = array();
	/** @var array The list of instructions used to render the template */
	protected $renderlist = array();

	/**
	 * Protected constructor, to ensure this class is only built with a template
	 * via one of its static methods.
	 */
	protected function __construct()
	{
	}

	/**
	 * Create a MustacheTemplate instance from a template given as a string.
	 *
	 * @param string $string  The template content to load.
	 * @return MustacheTemplate
	 */
	public static function fromTemplateString($string)
	{
		$obj = new static();
		$obj->setTemplateContent($string);
		return $obj;
	}

	/**
	 * Create a MustacheTemplate instance from a template stored in a file.
	 *
	 * @param string $filename  The path to the file that contains the template
	 *                          to load.
	 * @return MustacheTemplate
	 */
	public static function fromTemplateFile($filename)
	{
		return static::fromTemplateString(file_get_contents($filename));
	}

	/**
	 * Create a MustacheTemplate instance from a JSON-serialized compiled
	 * template string.
	 *
	 * @param string $json  The JSON string to use to construct a template.
	 * @return MustacheTemplate
	 */
	public static function fromJsonString($json)
	{
		$json = json_decode($json, true);
		$obj = new static();
		$obj->vars = $json['vars'];
		$obj->partials = $json['partials'];
		$obj->renderlist = $json['renderlist'];
		$obj->template = '';
		return $obj;
	}

	/**
	 * Return the compiled template as a JSON string.
	 *
	 * NOTE: This method will cause the template to be compiled, if that hasn't
	 * already happened.
	 *
	 * @return string
	 */
	public function toJsonString()
	{
		$this->compile();
		return json_encode(array(
			'vars' => $this->vars,
			'partials' => $this->partials,
			'renderlist' => $this->renderlist,
		));
	}

	/**
	 * Store the given template inside the object, and reset the renderlist.
	 *
	 * @param string $content  The template content.
	 * @return MustacheTemplate
	 */
	protected function setTemplateContent($content)
	{
		$this->renderlist = array();
		$this->template = $content;
		return $this;
	}

	/**
	 * Return the list of variable names used in the template.
	 *
	 * NOTE: This method will cause the template to be compiled, if that hasn't
	 * already happened.
	 *
	 * @return array
	 */
	public function getVariables()
	{
		$this->compile();
		return array_keys($this->vars);
	}

	/**
	 * Return the list of parials used in the template.
	 *
	 * NOTE: This method will cause the template to be compiled, if that hasn't
	 * already happened.
	 *
	 * @return array
	 */
	public function getPartials()
	{
		$this->compile();
		return array_keys($this->partials);
	}

	/**
	 * Compile the template into an internal list of rendering instructions.
	 * This method also populates the lists of partials and variables used
	 * within the template.
	 *
	 * This method will throw a \LogicException if the template has malformed
	 * sections (i.e. a section is closed with the wrong tag, or is left open
	 * at the end of the file).
	 *
	 * NOTE: If the template has already been compiled for this instance, it
	 * will not be compiled again.
	 *
	 * @TODO: doesn't handle sections in a way suitable for lambdas
	 *
	 * @return void
	 */
	protected function compile()
	{
		if ($this->renderlist) {
			return;
		}
		$otag = '{{';
		$ctag = '}}';

		$this->vars = array();
		$this->partials = array();

		$ris = array(array('_' => null));
		$t = $this->template;
		$s = 0;
		do {
			// snaffle text up to next opening tag
			$e = mb_strpos($t, $otag, $s);
			if ($e === false) {
				// no more opening tags; grab it all and we're done
				$ci = array(static::RI_TEXT, substr($t, $s));
				if (strlen($ci[1]) > static::COMPRESS_LIMIT) {
					$ci[0] = static::RI_GZTEXT;
					$ci[1] = gzcompress($ci[1], 9);
				}
			} else {
				// grab the text up to the opening tag, if any
				if ($e > $s) {
					$ci = array(static::RI_TEXT, substr($t, $s, $e-$s));
					if (strlen($ci[1]) > static::COMPRESS_LIMIT) {
						$ci[0] = static::RI_GZTEXT;
						$ci[1] = gzcompress($ci[1], 9);
					}
					$ris[0][] = $ci;
				}

				// gobble the opening delimiter
				$s = $e + mb_strlen($otag);
				// find the tag name
				$e = mb_strpos($t, $ctag, $s);
				$tag = substr($t, $s, $e-$s);
				$tag_type = static::RI_VAR;

				$ci = null;
				$params = null;

				// determine tag type
				if ($tag[0] === '{' && $t[$s] === '}') {
					// unescaped variable
					// if it's triple-brace, gobble an extra closing brace
					++$s;
					$tag = substr($tag, 1);
					$tag_type = static::RI_RAWVAR;
				}
				if ($tag[0] === '&') {
					// unescaped variable
					$tag = substr($tag, 1);
					$tag_type = static::RI_RAWVAR;
				}
				if ($tag[0] === '>') {
					// partial
					$tag = ltrim(substr($tag, 1));
					$tag_type = static::RI_PARTIAL;
					if (!isset($this->partials[$tag])) {
						$this->partials[$tag] = 0;
					}
					++$this->partials[$tag];
					$params = array();
					$spacepos = mb_strpos($tag, ' ');
					$equalpos = $spacepos === false ? false : mb_strpos($tag, '=', $spacepos);
					if ($equalpos !== false) {
						$partial_name = mb_substr($tag, 0, $spacepos);
						// parse parameters
						do {
							$param_name = trim(mb_substr($tag, $spacepos, $equalpos-$spacepos));
							if (!$param_name) break; // trailing whitespace inside tag
							$spacepos = mb_strpos($tag, ' ', $equalpos);
							$quotepos = mb_strpos($tag, '"', $equalpos);

							if ($quotepos !== false && ($spacepos===false || $quotepos < $spacepos)) {
								// parsing quoted value
								$equote_start = $quotepos;
								while (true) {
									$endquotepos = mb_strpos($tag, '"', $equote_start+1);
									if (true || mb_substr($tag, $endquotepos-1, 1) !== '\\') {
										break;
									}
									$equote_start = $endquotepos + 1;
								}
								$spacepos = mb_strpos($tag, ' ', $endquotepos);
								$param_value = mb_substr($tag, $quotepos+1, $endquotepos-$quotepos-1);
							} else {
								// parsing unquoted value
								if ($spacepos !== false) {
									$param_value = mb_substr($tag, $equalpos+1, $spacepos-$equalpos-1);
								} else {
									$param_value = mb_substr($tag, $equalpos+1);
								}
							}
							$params[$param_name] = $param_value;

							$equalpos = $spacepos === false ? false : strpos($tag, '=', $spacepos);
						} while ($equalpos !== false);
						// save just the partial name
						$tag = $partial_name;
					}
				}
				if ($tag[0] === '%') {
					// pragma
					$tag = substr($tag, 1);
					$tag_type = static::RI_PRAGMA;
				}
				if ($tag[0] === '/') {
					// end section
					$tag = substr($tag, 1);
					if ($ris[0]['_'] !== $tag) {
						throw new LogicException('Tried to use '.$otag.'/'.$tag.$ctag.' to close '.$ris[0]['_'].' section');
					}
					$ci = array($ris[0]['_opcode'], $tag, array_shift($ris));
					unset($ci[2]['_']);
					unset($ci[2]['_opcode']);
				}
				if ($tag[0] === '#') {
					// start section
					$tag = substr($tag, 1);
					array_unshift($ris, array('_'=>$tag, '_opcode'=>static::RI_SECTION));
					$ci = false;
				}
				if ($tag[0] === '^') {
					// start inverted section
					$tag = substr($tag, 1);
					array_unshift($ris, array('_'=>$tag, '_opcode'=>static::RI_INVSECTION));
					$ci = false;
				}
				if ($tag[0] === '$') {
					// start capture section
					$tag = substr($tag, 1);
					array_unshift($ris, array('_'=>$tag, '_opcode'=>static::RI_CAPTURE));
					$ci = false;
				}
				if ($tag[0] === '!') {
					// comment, ignore
					$ci = false;
				}
				if ($tag[0] === '=') {
					// change delimiters
					if (substr($tag, -1) !== '=') {
						throw new LogicException('Malformed delimiter change section: must end with "="');
					}
					$tag = substr($tag, 1, -1);
					$tag_parts = preg_split('/ +/', $tag);
					if (count($tag_parts) != 2) {
						throw new LogicException('Malformed delimiter change section: must be exactly two delimiters, with a space between');
					}
					list($otag, $ctag) = $tag_parts;
					$ci = false;
				}

				$tag = trim($tag);
				if ($ci === null) {
					$ci = array($tag_type, $tag);
					if ($params !== null) {
						$ci[2] = $params;
					}
				}
			}
			if ($ci) {
				$ris[0][] = $ci;
				if (in_array($ci[0], array(static::RI_VAR, static::RI_RAWVAR, static::RI_SECTION, static::RI_INVSECTION), true)) {
					if (!isset($this->vars[$ci[1]])) {
						$this->vars[$ci[1]] = 0;
					}
					++$this->vars[$ci[1]];
				}
			}

			// gobble the closing delimiter (doing it here allows $s and $e to move
			// based on the tag content, e.g. parsing parameters)
			$s = $e + mb_strlen($ctag);
		} while ($e !== false);

		if ($ris[0]['_'] !== null) {
			throw new LogicException('Section '.$ris[0]['_'].' still open at end of template');
		}
		unset($ris[0]['_']);
		$this->renderlist = $ris[0];
		return $ris[0];
	}

	/**
	 * Return the value of a variable in the given context. If it does not
	 * exist, recurse into any nested contexts. If it still cannot be found,
	 * return null. The special variable name "." will return the entire
	 * current context.
	 *
	 * The result of this function will be HTML-encoded by default; set
	 * $encode to false to get the raw value.
	 *
	 * @param string  $var      The variable to be looked up
	 * @param array  &$context  The context to search
	 * @param bool    $encode   Whether to HTML-encode the result (default:
	 *                          true)
	 * @return mixed
	 */
	protected function lookupVar($var, array &$context, $encode = true)
	{
		if ($var === '.') {
			if (is_array($context['data'])) {
				return implode(',', $context['data']);
			}
			return $context['data'];
		}
		$curcontext =& $context;
		// deal with dot notation
		$var_parts = explode('.', $var);
		while (!is_array($curcontext['data']) || !array_key_exists($var_parts[0], $curcontext['data'])) {
			if (!isset($curcontext['outer'])) {
				// no outer context; variable is not defined
				return null;
			} else {
				$curcontext =& $curcontext['outer'];
			}
		}
		// deal with any further dots
		$value =& $curcontext['data'];
		foreach ($var_parts as $var_part) {
			if (!is_array($value) || !array_key_exists($var_part, $value)) {
				// either we can't go any further, or the key wasn't found
				// NOTE: do not recurse to outer context
				return null;
			}
			$value =& $value[$var_part];
		}
		if ($encode) {
			return htmlspecialchars(is_array($value) ? json_encode($value) : $value, ENT_QUOTES, 'UTF-8');
		} else {
			return $value;
		}
	}

	/**
	 * Internal helper function to create a nested context.
	 *
	 * @param mixed  $new_values   The data for the new context
	 * @param mixed &$old_context  The old context
	 * @return array
	 */
	protected function &nestContext($new_values, &$old_context = null)
	{
		$v = array(
			'outer' => $old_context,
			'data' => $new_values
		);
		return $v;
	}

	/**
	 * Internal helper function to render a section.
	 *
	 * @TODO: doesn't handle lambda $section values
	 *
	 * @param mixed  $section     The value of the section variable
	 * @param array &$renderlist  The rendering instructions for the section
	 * @param array &$context     The current variable context (outside the
	 *                            section)
	 * @param array &$opts        The current set of options
	 * @return string
	 */
	protected function renderSection($section, array &$renderlist, array &$context, array &$opts)
	{
		if ($section === null || $section === array() || $section === false || $section === 0 || $section === '') {
			// falsy values -> empty string
			return '';
		}
		if (is_array($section) || $section instanceof Traversable) {
			// iterate over the item, rendering each time
			$output = '';
			foreach ($section as $item) {
				$output .= $this->renderList($renderlist, $this->nestContext($item, $context), $opts);
			}
			return $output;
		}
		return $this->renderList($renderlist, $this->nestContext($section, $context), $opts);
	}

	/**
	 * Internal helper function to render a partial.
	 *
	 * @TODO: not implemented
	 *
	 * @param mixed  $section  The partial name
	 * @param array &$context  The current variable context (to be passed into
	 *                         the partial)
	 * @param array &$opts     The current set of options
	 * @return string
	 */
	protected function renderPartial($partial, array &$context, array &$opts)
	{
		return "«partial {$partial}»";
	}

	/**
	 * Internal helper function to render a string from a list of rendering
	 * instructions.
	 *
	 */
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
					if ($ri[1] === 'UNESCAPED') {
						$opts['encode_default'] = false;
					}
					if ($ri[1] === 'ESCAPED') {
						$opts['encode_default'] = true;
					}
					break;
				default:
					break;
			}
		}
		return $output;
	}

	/**
	 * Render the template with the given data.
	 *
	 * @param array $data  The data to use when rendering the template.
	 * @return string
	 */
	public function render(array $data)
	{
		$this->compile();
		$opts = array(
			'encode_default' => true,
		);
		return $this->renderList($this->renderlist, $this->nestContext($data), $opts);
	}

	/**
	 * Return the original string used to build this template instance.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->template;
	}
}
