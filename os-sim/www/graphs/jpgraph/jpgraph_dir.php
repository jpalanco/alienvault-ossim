<?php
/*=======================================================================
// File: 	JPGRAPH_DIR.PHP
// Description:	Specification of file directories for JpGraph
// Created: 	22/11/2001
// Author:	Johan Persson (johanp@aditus.nu)
// Ver:		$Id: jpgraph_dir.php,v 1.4 2002/02/11 13:00:11 aditus Exp $
//
// License:	This code is released under GPL 2.0
// Copyright (C) 2001 Johan Persson
//========================================================================
*/

//------------------------------------------------------------------
// Manifest Constants that control varius aspect of JpGraph
//------------------------------------------------------------------
// The full absolute name of directory to be used as a cache. This directory MUST
// be readable and writable for PHP. Must end with '/'
DEFINE("CACHE_DIR","/var/cache/jpgraph/");

// The URL relative name where the cache can be found, i.e
// under what HTTP directory can the cache be found. Normally
// you would probably assign an alias in apache configuration
// for the cache directory. 
DEFINE("APACHE_CACHE_DIR","/jpgraph_cache/");

// Directory for TTF fonts. Must end with '/'
DEFINE("TTF_DIR","/usr/share/fonts/truetype/msttcorefonts/");

// Add Free liberation font as suggested by Alain Peyrat
DEFINE("LIBERATION_DIR","/usr/share/fonts/truetype/ttf-liberation/");

?>
