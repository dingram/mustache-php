<?php

/**
 * This class is responsible for rendering a compiled Mustache-format
 * template to a string.
 */
class MustacheRenderer
{
	/**
	 * Create a renderer for the given template.
	 *
	 * @param MustacheTemplate $template  The template to be rendered.
	 */
	public function __construct(MustacheTemplate $template)
	{
		$this->template = $template;
	}

	/**
	 * Create a renderer for the given template.
	 *
	 * @param MustacheTemplate $template  The template to be rendered.
	 * @return MustacheTemplate
	 */
	public static function create(MustacheTemplate $template)
	{
		return new static($template);
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
	 * Return the value of a variable in the given context. If it does not
	 * exist, recurse into any nested contexts. If it still cannot be found,
	 * return null. The special variable name "." will return the entire
	 * current context.
	 *
	 * The result of this function will not be encoded in any way.
	 *
	 * @param string  $var      The variable to be looked up
	 * @param array  &$context  The context to search
	 * @return mixed
	 */
	protected function lookupVar($var, array &$context)
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
		return $value;
	}

	/**
	 * Store the given value as a variable in the innermost context. No
	 * recursion will occur into the outer contexts.
	 *
	 * @param string  $var_name  The variable to be assigned
	 * @param string  $value     The value to assign to the variable
	 * @param array  &$context   The context to update
	 * @return void
	 */
	protected function putVar($var_name, $value, array &$context)
	{
		if ($var_name === '.') {
			throw new InvalidArgumentException('You cannot update the special variable "."');
		}
		// deal with dot notation
		$var_parts = explode('.', $var);
		$last_part = array_pop($var_parts);
		$variable =& $context['data'];
		foreach ($var_parts as $var_part) {
			if (!is_array($variable)) {
				$variable = array();
			}
			if (!array_key_exists($var_part, $variable)) {
				$variable[$var_part] = array();
			}
			$variable =& $variable[$var_part];
		}
		$variable[$last_part] = $value;
	}

	/**
	 * Internal helper function to render a block of uninterpreted text.
	 *
	 * @param string $text  The text to render.
	 * @return string
	 */
	protected function renderText($text)
	{
		return $text;
	}

	/**
	 * Internal helper function to render a block of uninterpreted gzipped
	 * text.
	 *
	 * @param string $text  The gzipped text to render.
	 * @return string
	 */
	protected function renderGzText($text)
	{
		return gzuncompress($text);
	}

	/**
	 * Render a variable from the given context.
	 *
	 * The result of this function will be HTML-encoded by default; set
	 * $encode to false to get the raw value.
	 *
	 * @param string  $var      The variable to be looked up
	 * @param array  &$context  The context to search
	 * @param bool    $encode   Whether to HTML-encode the result (default:
	 *                          true)
	 * @return mixed
	 *
	 * @see lookupVar()
	 */
	protected function renderVar($var, array &$context, $encode)
	{
		$value = $this->lookupVar($var, $context);
		if (is_array($value)) {
			$value = json_encode($value);
		}
		if ($encode) {
			return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
		} else {
			return $value;
		}
	}

	/**
	 * Internal helper function to render a section.
	 *
	 * @TODO: doesn't handle lambda $section values
	 *
	 * @param mixed  $section_var  The value of the section variable
	 * @param array &$renderlist   The rendering instructions for the section
	 * @param array &$context      The current variable context (outside the
	 *                             section)
	 * @param array &$opts         The current set of options
	 * @param array  $regular      Whether this is a regular section (true) or
	 *                             inverted (false)
	 * @return string
	 */
	protected function renderSection($section_var, array &$renderlist, array &$context, array &$opts, $regular)
	{
		$section = $this->lookupVar($section_var, $context);
		if (!$regular) {
			// invert the condition
			$section = !$section;
		}
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
	 * Internal helper function to render a capture section.
	 *
	 * A capture section renders its content, but stores it in the given
	 * variable name in the current context scope and returns nothing.
	 *
	 * @param mixed  $section_var  The name of the storage variable
	 * @param array &$renderlist   The rendering instructions for the section
	 * @param array &$context      The current variable context (outside the
	 *                             section)
	 * @param array &$opts         The current set of options
	 * @return string
	 */
	protected function renderCapture($section_var, array &$renderlist, array &$context, array &$opts)
	{
		$this->putVar(
			$section_var,
			$context,
			$this->renderList($renderlist, $context, $opts)
		);
		return '';
	}

	/**
	 * Internal helper function to render a partial.
	 *
	 * @TODO: not implemented
	 *
	 * @param mixed  $partial  The partial name
	 * @param array &$params   Additional variable context (to be passed into
	 *                         the partial, layered above the current context)
	 * @param array &$context  The current variable context (to be passed into
	 *                         the partial)
	 * @param array &$opts     The current set of options
	 * @return string
	 */
	protected function renderPartial($partial, array &$params, array &$context, array &$opts)
	{
		if ($params) {
			return "«partial {$partial} ".json_encode($params)."»";
		} else {
			return "«partial {$partial}»";
		}
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
				case MustacheTemplate::RI_TEXT:
					// simple text block; use it directly
					$output .= $this->renderText($ri[1]);
					break;
				case MustacheTemplate::RI_GZTEXT:
					// gzipped text block; ungzip and use it directly
					$output .= $this->renderGzText($ri[1]);
					break;
				case MustacheTemplate::RI_VAR:
					// HTML-encoded variable replacement
					$output .= $this->renderVar($ri[1], $context, $opts['encode_default']);
					break;
				case MustacheTemplate::RI_RAWVAR:
					// raw (non-encoded) variable replacement
					$output .= $this->renderVar($ri[1], $context, !$opts['encode_default']);
					break;
				case MustacheTemplate::RI_SECTION:
					$output .= $this->renderSection($ri[1], $ri[2], $context, $opts, true);
					break;
				case MustacheTemplate::RI_INVSECTION:
					$output .= $this->renderSection($ri[1], $ri[2], $context, $opts, false);
					break;
				case MustacheTemplate::RI_CAPTURE:
					$output .= $this->renderCapture($ri[1], $ri[2], $context, $opts);
					break;
				case MustacheTemplate::RI_PARTIAL:
					$output .= $this->renderPartial($ri[1], $ri[2], $context, $opts);
					break;
				case MustacheTemplate::RI_PRAGMA:
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
		$opts = array(
			'encode_default' => true,
		);
		return $this->renderList($this->template->getRenderList(), $this->nestContext($data), $opts);
	}

}
