<?php

class View
{
	private $viewType;
	private $viewPath;

	public function __construct($viewName) {
		$viewDir = realpath('../src/view/');
		if(file_exists("$viewDir/$viewName.php")) {
			$this->viewType = 'php';
			$this->viewPath = "$viewDir/$viewName.php";
		} else if (file_exists("$viewDir/$viewName.md")) {
			$this->viewType = 'markdown';
			$this->viewPath = "$viewDir/$viewName.md";
		} else {
			throw new Exception("Unable to find view script for '$viewName'", 404);
		}
	}

	public function render() {
		switch($this->viewType) {
			case 'php': return $this->renderPhp();
			case 'markdown': return $this->renderMarkdown();
			default: throw new Exception("Unhandled view type '$this->viewType'", 500);
		}
	}

	// Execute the view in the context of this class
	private function renderPhp() {
		require_once $this->viewPath;
		exit;
	}

	// Convert the markdown file into html
	private function renderMarkdown() {
		require_once('Markdown.php');
		$md = new Michelf\Markdown();
		echo '<html><head><link href="markdown.css" media="all" rel="stylesheet" type="text/css"/></head><body>';
		echo $md->transform(file_get_contents($this->viewPath));
		echo '</body></html>';
		exit;
	}

}

