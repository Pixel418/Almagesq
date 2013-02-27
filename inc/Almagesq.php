<?php

class Almagesq {


	/*************************************************************************
	  CONSTANTS
	 *************************************************************************/
	const MAX_DEPTH = 2;
	const SETTINGS_PATH = '/../settings';
	const USER_SETTINGS_PATH = '/../settings/user';


	/*************************************************************************
	  ATTRIBUTES		   
	 *************************************************************************/
	public $patternPath;
	public $menus = array( );
	public $currentMenus = array( );
	public $patterns = array( );
	public $currentPattern;
	public $themes = [ ];
	public $settings;


	/*************************************************************************
	  STATIC METHODS				   
	 *************************************************************************/
	public static function hasPatterns( $menu ) {
		return ( is_array( $menu ) && ! empty( $menu ) && is_numeric( current( array_keys( $menu ) ) ) );
	}
	public static function FileHumanName( $file ) {
		$fileName = ucfirst( str_replace( '_', ' ', basename( $file ) ) );
		if ( $pos = strpos( $fileName, '.' ) ) {
			$fileName = substr( $fileName, 0, $pos );
		}
		return $fileName;
	}


	/*************************************************************************
	  PUBLIC METHODS				   
	 *************************************************************************/
	public function getCurrentPatternPath( ) {
		$path = $this->patternPath . '/' . implode( $this->currentMenus, '/' ) . '/' . $this->currentPattern;
		return realpath( $path );
	}
	public function getCurrentPatternHtml( ) {
		if ( $path = $this->getCurrentPatternPath( ) ) {
			return file_get_contents( $path );
		}
	}
	public function getTitle( ) {
		$title = 'Style Guide';
		if ( isset( $this->settings[ 'title' ] ) ) {
			$title = $this->settings[ 'title' ];
		}
		return $title;
	}
	public function getStyles( ) {
		$styles = array( );
		if ( isset( $this->settings[ 'styles' ] ) ) {
			$styles = $this->settings[ 'styles' ];
			if ( ! is_array( $styles ) ) {
				$styles = array( $styles );
			}
		}
		return $styles;
	}
	public function getScripts( ) {
		$scripts = array( );
		if ( isset( $this->settings[ 'scripts' ] ) ) {
			$scripts = $this->settings[ 'scripts' ];
			if ( ! is_array( $scripts ) ) {
				$scripts = array( $scripts );
			}
		}
		return $scripts;
	}
	public function getCurrentMenuHttpQuery( ) {
		return 'menu[]=' . $this->currentMenus[ 0 ] . '&amp;menu[]=' . $this->currentMenus[ 1 ];
	}


	/*************************************************************************
	  CONSTRUCTOR METHODS				   
	 *************************************************************************/
	public function __construct( ) {
		$this->themes = $this->getThemes( );
		$this->settings = $this->getSettings( );
		$this->patternPath = $this->getPatternPath( );
		$this->menus = UFIle::folderTree( $this->patternPath, '*.html', static::MAX_DEPTH, UFile::FILE_FLAG );
		$this->currentMenus = $this->getCurrentMenus( );
		$this->patterns = $this->getPatterns( );
		$this->currentPattern = $this->getCurrentPattern( );
	}


	/*************************************************************************
	  PROTECTED METHODS				   
	 *************************************************************************/
	protected function getThemes( ) {
		$themes = \UFile::fileList( __DIR__ . static::USER_SETTINGS_PATH, '*.ini' );
		if ( empty( $themes ) ) {
			if ( ! $themes = realpath( __DIR__ . static::SETTINGS_PATH . '/default.ini' ) ) {
				echo 'Settings file not found :\'(';
				die;
			}
		} else {
			foreach( $themes as $key => $theme ) {
				unset( $themes[ $key ] );
				$themes[ static::FileHumanName( $theme ) ] = $theme;
			}
		}
		return $themes;
	}

	protected function isThemeExist( $theme ) {
		return in_array( $theme, array_keys( $this->themes ) );
	}

	protected function getSettings( ) {
		if ( is_array( $this->themes ) ) {
			if ( isset( $_GET[ 'theme' ] ) && $this->isThemeExist( $_GET[ 'theme' ] ) ) {
				$this->setCurrentTheme( $_GET[ 'theme' ] );
			}
			if ( $this->issetCurrentTheme( ) ) {
				$this->setDefaultCurrentTheme( );
			}
			$settings = parse_ini_file( $this->themes[ $this->getCurrentTheme( ) ] );
		} else {
			$settings = parse_ini_file( $this->themes );
		}
		return $settings;
	}

	protected function setCurrentTheme( $theme ) {
		if ( ! is_array( $_SESSION[ 'Almagesq' ] ) ) {
			$_SESSION[ 'Almagesq' ] = array( );
		}
		$_SESSION[ 'Almagesq' ][ 'theme' ] = $theme;
	}

	protected function issetCurrentTheme( ) {
		return ( ! isset( $_SESSION[ 'Almagesq' ][ 'theme' ] ) || ! $this->isThemeExist( $_SESSION[ 'Almagesq' ][ 'theme' ] ) );
	}

	protected function setDefaultCurrentTheme( ) {
		$this->setCurrentTheme( key( $this->themes ) );
	}

	protected function getCurrentTheme( ) {
		return $_SESSION[ 'Almagesq' ][ 'theme' ];
	}

	protected function getPatternPath( ) {
		$basePath = __DIR__ . '/..';
		$patternPath = $basePath . '/pattern';
		if ( isset( $this->settings[ 'pattern_path' ] ) ) {
			$patternPath = $basePath . '/' . $this->settings[ 'pattern_path' ];
		}
		if ( ! $patternPath = realpath( $patternPath ) ) {
			echo 'Pattern folder not found :\'(';
			die;
		}
		return $patternPath;
	}

	protected function getCurrentMenus( ) {
		$currentMenus = array( );
		if ( isset( $_GET[ 'menu' ] ) && is_array( $_GET[ 'menu' ] ) ) {
			$currentMenus = array_values( $_GET[ 'menu' ] );
		}
		$menus = $this->menus;
		foreach ( $currentMenus as $key => $currentMenu ) {
			if ( empty( $currentMenu ) || ! in_array( $currentMenu, array_keys( $menus ) ) ) {
				unset( $currentMenus[ $key ] );
				$menus = array( );
			} else {
				$menus =& $menus[ $currentMenu ];
			}
		}
		$currentMenus = array_pad( $currentMenus, static::MAX_DEPTH, NULL );
		return $currentMenus;
	}

	protected function getSubmenu( $keys ) {
		$submenus = $this->menus;
		foreach ( $keys as $menu ) {
			if ( is_null( $menu ) ) {
				break;
			}
			$submenus =& $submenus[ $menu ]; 
		}
		return $submenus;
	}

	protected function getPatterns( ) {
		$patterns = $this->getSubmenu( $this->currentMenus );
		if ( ! static::hasPatterns( $patterns ) ) {
			$patterns = array( );
		}
		return $patterns;
	}

	protected function getCurrentPattern( ) {
		if ( ! empty( $this->patterns ) && isset( $_GET[ 'pattern' ] ) && in_array( $_GET[ 'pattern' ], $this->patterns ) ) {
			return $_GET[ 'pattern' ];
		}
	}
}

?>