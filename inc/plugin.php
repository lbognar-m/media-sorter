<?php

class Mediamatic_Plugin {

	
	public function __construct()
	{	
		$this->init_files();
	}


	private function init_files() 
	{
		include_once ( MEDIASORTER_PATH . 'inc/walkers.php');
		include_once ( MEDIASORTER_PATH . 'inc/topbar.php');
		include_once ( MEDIASORTER_PATH . 'inc/interface.php');
	}

}

new Mediamatic_Plugin();