<?php

class Crawler
{
	private $url;
	public $filename = "data.txt";
	private $ret = array();

	public function setUrl($url)
	{
		$this -> url = $url;
	}

	public function __construct($url)
	{
		if (!empty($url)) {
			$this -> setUrl($url);
		}
	}

	function page_title()
	{
		$fp = file_get_contents($this -> url);
		if (!$fp)
			return null;

		$res = preg_match("/<title>(.*)<\/title>/siU", $fp, $title_matches);
		if (!$res)
			return null;

		$title = preg_replace('/\s+/', ' ', $title_matches[1]);
		$title = trim($title);
		return $title;
	}

	public function crawl($depth)
	{
		$all_links = $this->_crawl($this->url, $depth);
		if( $this->filename ){
			file_put_contents($this->filename,  implode("\n", $all_links) );
		}
		return $all_links;
	}

	private function _crawl($url, $depth)
	{
		if($depth==0){
			return array();
		}
		$all_links = array();

		$html = file_get_contents($url);
		$links = $this->_get_links($html, $url);
		$all_links = array_merge($all_links, $links);

		foreach ($links as $link) {
			$child_links = $this->_crawl($link, $depth - 1);
			$all_links = array_merge($all_links, $child_links);
		}

		return $all_links;
	}

	private function _get_links($html, $url)
	{
		$links = array();
		$dom = new DOMDocument();
		@$dom->loadHTML($html);
		$xPath = new DOMXPath($dom);
		$elements = $xPath->query("//a/@href");

		foreach ($elements as $e) {
			$links[] = $this->_rel2abs($e->nodeValue, $url);
		}
		return $links;
	}

	// borrowed from http://stackoverflow.com/questions/4444475/transfrom-relative-path-into-absolute-url-using-php
	private function _rel2abs($rel, $base)
	{
		if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;
		if ($rel[0]=='#' || $rel[0]=='?') return $base.$rel;
		extract(parse_url($base));

		if( !@$path ){
			$path = '';
		}
		$path = preg_replace('#/[^/]*$#', '', $path);
		if ($rel[0] == '/') $path = '';
		$abs = "$host$path/$rel";
		$re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
		for($n=1; $n>0; $abs=preg_replace($re, '/', $abs, -1, $n)) {}
		return $scheme.'://'.$abs;
	}
}

$a = new Crawler('http://www.levyx.com');
$a->crawl(2);
