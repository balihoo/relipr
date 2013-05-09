<?php
/* The View class is used to render HTML pages.
 You can render either a .php file or a .md file
 See /src/resource/Console.php to see how this is used
*/

class View
{
	private $viewType;
	private $viewPath;

	//The view will use either a .php or .md file in src/view/ directory
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

	// Render the view
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
		// Store a cache of the rendered file to keep things snappy
		$cachePath = sys_get_temp_dir() . '/markdowncache_' . md5($this->viewPath) . '.html';
		if(!file_exists($cachePath) || filemtime($cachePath) < filemtime($this->viewPath)) {
			require_once('Markdown.php');
			$md = new Michelf\Markdown();
			$src = '<html><head><link href="markdown.css" media="all" rel="stylesheet" type="text/css"/></head><body>';
			$src .= $md->transform(file_get_contents($this->viewPath));
			$src .= '</body></html>';
			file_put_contents($cachePath, $src, LOCK_EX);
			echo $src;
		} else {
			readfile($cachePath);
		}
		exit;
	}

}

