<?php

namespace e7o\Moments\Output\Forms;

use \e7o\Moments\Request\Request;

/**
* Builds a form which can be submitted and easily stored. Just pass a filename of
* a JSON config in your forms/ directory. Passing an array is possible, but it's
* not recommended (and might be removed when it blocks other functionality).
* It can contain the following data:
* 
* ```javascript
* [
* 	{
* 		"id": "<NAME>",
* 		"type": "text|longtext|file|submit|bool|list|group",
* 		"label": "Some short description",
* 		"default": "<DEFAULT>",
* 		"options": [1, 2, 3] | {"a": "Apple", "b": "Banana"}
* 		"constraints": {
* 			"required": true,
* 			"maxlength": 128, # for text inputs
* 			"type": "image", # for file uploads
* 		},
* 		"sub": [
* 			...
* 		]
* 		"callback": {
* 			"creation": "your_callback_name",
* 			"validation": "your_callback_name"
* 		},
* 		"html": {
* 			"beforeFrame": "...",
* 			"beforeInput": "...",
* 			"afterInput": "...",
* 			"afterFrame": "...",
* 		}
* 	},
* 	...
* ]
* ```
* 
* The `your_callback_name` function must be passed in the options of `build`
* and `evaluate`. You need a callback in the form of `function (&$element) { ... }` or
* `function ($element, &$data) { ... }`, you're allowed to modify the values in creation.
* - `creation` will be called on element creation, so here's the place to fill in e.g.
*   the list for a select box from database, the time or anything else you can imagine.
* - `validation` is for validation. Providing a validator function will
*   disable the internal validation process. This is highly recommended, as it has
*   no idea about the allowed data in a list. If validation fails on your side, just
*   throw an exception like the generator does itself as well. Btw, you could modify
*   the `$data` argument as well if you find a use case for that.
* 
* Basic usage:
* - Render the form with build()
* - Check, if a POST happened by hasFormData()
* - Get all the data by evaluate(), don't forget to handle special cases like
*   file uploads on your own
* 
* ```php
* $f = $this->get('forms');
* if ($f->hasFormData('fancy-form.json', $this->getRequest())) {
* 	var_dump($f->evaluate('fancy-form.json', $this->getRequest()));
* } else {
* 	echo $f->build('fancy-form.json');
* }
* ```
* 
* This class is not thread-safe when building forms.
*/
class Generator
{
	private $hasUploads;
	
	public function __construct($templateRenderer, $formsDirectory)
	{
		$this->template = $templateRenderer;
		$this->forms = $formsDirectory;
	}
	
	public function build($form, array $options = [], $data = [])
	{
		$formId = is_string($form) ? $this->getTechId($form) : null;
		$form = $this->readForm($form);
		
		$this->hasUploads = false;
		$result = [];
		
		foreach ($form as $element) {
			if ($element['type'] == 'group') {
				$subs = [];
				foreach ($element['sub'] as $sub) {
					$subs[] = $this->buildElement($sub, $data, $options);
				}
				$element['sub'] = $subs;
			}
			$rendered = $this->buildElement($element, $data, $options);
			$result[] = $this->template->render('forms/item.htm', ['item' => $element]);
		}
		
		$result = $this->template->render(
			'forms/base.htm',
			[
				'form-id' => $formId,
				'method' => $options['method'] ?? 'POST', // Recommended
				'enctype' => $this->hasUploads ? 'multipart/form-data' : 'application/x-www-form-urlencoded',
				'action' => $options['action'] ?? null,
				'content' => implode(PHP_EOL, $result),
				'class' => $options['class'] ?? null,
			]
		);
		
		return $result;
	}
	
	private function buildElement(&$element, &$data, &$options)
	{
		$element = $this->fillUp($element, $data);
		if (
			!empty($element['callback'])
			&& !empty($options[$element['callback']['creation']])
			&& is_callable($options[$element['callback']['creation']])
		) {
			$options[$element['callback']['creation']]($element, $data);
		}
		if ($element['type'] == 'file') {
			$this->hasUploads = true;
		}
		return $element;
	}
	
	/**
	* Checks if a form was submitted and is in the request. This will only
	* work for file-based templates.
	*/
	public function hasFormData($form, Request $request): bool
	{
		return $request->getParameter('sender') === $this->getTechId($form);
	}
	
	/**
	* Returns the user input as array, if all requirements are fullfilled.
	*/
	public function evaluate($form, Request $request, $options = []): array
	{
		$form = $this->readForm($form);
		$collected = [];
		foreach ($form as $elementOuter) {
			if ($elementOuter['type'] == 'group') {
				$elementInner = $elementOuter['sub'];
			} else {
				$elementInner = [$elementOuter];
			}
			foreach ($elementInner as $element) {
				$element = $this->fillUp($element);
				$data = $request->getParameter($element['tech-id'], $element['default'] ?? null);
				$validated = false;
				if (
					!empty($element['callback'])
					&& !empty($options[$element['callback']['validation']])
					&& is_callable($options[$element['callback']['validation']])
				) {
					$options[$element['callback']['validation']]($element, $data);
					$validated = true;
				}
				switch ($element['type']) {
					case 'file':
						// ToDo: Other constraints (like file type)
						break;
					case 'bool':
						$data = $data === '1';
						break;
					case 'submit':
						$data = ($data === $element['id']);
						if ($data) {
							$collected['_submit'] = $element['id'];
						}
						break;
					default:
						if ($validated) {
							break;
						}
						// Handle regular, easy constraints
						if (isset($element['constraints']) && is_array($element['constraints'])) {
							$constraints = $element['constraints'];
							if (
								isset($constraints['maxlength']) && strlen($data) > $constraints['maxlength']
								|| isset($constraints['pattern']) && preg_match('/^' . $constraints['pattern'] . '$/', $data) == 0
								|| isset($constraints['required']) && $constraints['required'] && strlen($data) == 0
								|| isset($constraints['max']) && $data > $constraints['max']
								|| isset($constraints['min']) && $data < $constraints['min']
							) {
								throw new \Exception('Form constraint not fullfilled on element ' . $element['id']);
							}
						}
						if ($element['type'] == 'list') {
							// Validate input against specified list values
							if (!is_array($data)) {
								$dataToCheck = [$data];
							}
							foreach ($dataToCheck as $singleval) {
								if (!isset($element['options'][$singleval])) {
									throw new \Exception('Element ' . $element['id'] . ' received invalid list value ' . $singleval);
								}
							}
						}
						if ($element['type'] == 'number' && !is_numeric($data)) {
							throw new \Exception('Element ' . $element['id'] . ' is not numeric');
						}
				}
				$collected[$element['id']] = $data;
			}
		}
		
		return $collected;
	}
	
	private function fillUp($e, &$data = null): array
	{
		$e['tech-id'] = $this->getTechId($e['id']);
		
		if (isset($e['options']) && is_array($e['options']) && is_numeric(key($e['options']))) {
			$e['default'] = array_search($e['default'], $e['options'], true);
		}
		
		if ($data !== null) {
			if (is_array($data) && isset($data[$e['id']])) {
				$e['default'] = $data[$e['id']];
			} else if ($data instanceof Request) {
				$e['default'] = $data->getParameter($e['tech-id'], $e['default'] ?? null);
			}
		}
		
		if ($e['type'] == 'list' && !empty($e['default']) && !is_array($e['default'])) {
			$e['default'] = [$e['default']];
		}
		
		return $e;
	}
	
	private function getTechId($value)
	{
		return 'x' . md5($value);
	}
	
	private function readForm($form): array
	{
		switch (true) {
			case is_string($form):
				$fn = $this->forms . '/' . $form;
				if (!file_exists($fn)) {
					throw new \Exception('Cannot find form ' . $fn);
				}
				$form = file_get_contents($fn);
				$form = json_decode($form, true);
				if ($form === null) {
					throw new \Exception(
						'Cannot parse form ' . $fn . ': ' . json_last_error_msg()
					);
				}
				break;
			case is_array($form):
				break;
			default:
				throw new \Exception('Unknown form specification');
		}
		
		return $form;
	}
}
