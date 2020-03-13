<?php

namespace e7o\Moments\Output\Forms;

use \e7o\Moments\Request\Request;

class Generator
{
	public function __construct($templateRenderer, $formsDirectory)
	{
		$this->template = $templateRenderer;
		$this->forms = $formsDirectory;
	}
	
	public function build($form, $options = [], $data = [])
	{
		$formId = is_string($form) ? md5($form) : null;
		$form = $this->readForm($form);
		
		$hasUploads = false;
		$result = [];
		
		foreach ($form as $element) {
			$element = $this->fillUp($element);
			if ($element['type'] == 'file') {
				$hasUploads = true;
			}
			$result[] = $this->template->render('forms/item.htm', $element);
		}
		
		$result = $this->template->render(
			'forms/base.htm',
			[
				'form-id' => $formId,
				'method' => $options['method'] ?? 'POST', // Recommended
				'enctype' => $hasUploads ? 'multipart/form-data' : 'application/x-www-form-urlencoded',
				'action' => $options['action'] ?? null,
				'content' => implode(PHP_EOL, $result),
			]
		);
		
		return $result;
	}
	
	/**
	* Checks if a form was submitted and is in the request. This will only
	* work for file-based templates.
	*/
	public function hasFormData($form, Request $request)
	{
		return $request->getParameter('sender') === md5($form);
	}
	
	/**
	* Returns the user input as array, if all requirements are fullfilled.
	*/
	public function evaluate($form, Request $request)
	{
		$form = $this->readForm($form);
		$data = [];
		
		foreach ($form as $element) {
			$element = $this->fillUp($element);
			$data = $request->getParameter($element['tech-id'], $element['default'] ?? null);
			if (
				isset($element['maxlength']) && strlen($data) > $element['maxlength']
			) {
				throw new \Exception('Form constraint not fullfilled on element ' . $element['id']);
			}
			$data[$element['id']] = $data;
		}
		
		return $data;
	}
	
	private function fillUp($e)
	{
		$e['tech-id'] = md5($e['id']);
		
		return $e;
	}
	
	private function readForm($form)
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
