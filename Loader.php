<?php
/**
 *
 *===================================================================
 *
 *  materia
 *-------------------------------------------------------------------
 * @category    materia
 * @package     materia
 * @author      emberlabs.org
 * @copyright   (c) 2011 emberlabs.org
 * @license     MIT License
 * @link        https://github.com/emberlabs/materia
 *
 *===================================================================
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 *
 */

namespace emberlabs\materia;
use \emberlabs\materia\Internal\MetadataException;
use \emberlabs\materia\Internal\DependencyException;

/**
 * materia - Addon manager class,
 * 	    Manages the loading and initialization of addons.
 *
 *
 * @category    materia
 * @package     materia
 * @author      emberlabs.org
 * @license     MIT License
 * @link        https://github.com/emberlabs/materia
 */
class Loader implements \Iterator
{
	/**
	 * @var string - The base path to use with all addon loading.
	 */
	protected $base_path = '';

	/**
	 * @var false|string - The directory containing all addon phar packages (or false if no phar loading in use)
	 */
	protected $addon_phar_dir = false;

	/**
	 * @var string - The directory containing all addon include files.
	 */
	protected $addon_dir = '/addons/';

	/**
	 * @var \Closure|NULL - NULL if no callback, or \Closure of the callback to run on addon load.
	 */
	protected $autoloader_callback = NULL;

	/**
	 * @var array - Array of instantiated metadata objects.
	 */
	protected $metadata = array();

	/**
	 * Constructor
	 * @param string $base_path - The base load path to use.
	 * @param string $addon_dir - The directory containing the include files for all addons.
	 * @param string $addon_phar_dir - The directory containing addon phar packages. May be set as false to disable phar loading.
	 */
	public function __construct($base_path, $addon_dir, $addon_phar_dir = false)
	{
		$this->setBasePath($base_path)
			->setAddonDirs($addon_dir, $addon_phar_dir);
	}

	/**
	 * Set the base load path.
	 * @param string $base_path - The base load path to use.
	 * @return \emberlabs\materia\Loader - Provides a fluent interface.
	 */
	public function setBasePath($base_path)
	{
		$this->base_path = rtrim($base_path, '\\/');

		return $this;
	}

	/**
	 * Set the addon load directories.
	 * @param string $addon_dir - The directory containing the include files for all addons.
	 * @param string $addon_phar_dir - The directory containing addon phar packages. May be set as false to disable phar loading.
	 * @return \emberlabs\materia\Loader - Provides a fluent interface.
	 */
	public function setAddonDirs($addon_dir, $addon_phar_dir = false)
	{
		$this->addon_dir = '/' . rtrim(ltrim($addon_dir, '\\/'), '\\/') . '/';
		$this->addon_phar_dir = ($addon_phar_dir) ? rtrim(ltrim($addon_phar_dir, '\\/'), '\\/') . '/' : false;

		return $this;
	}

	/**
	 * Set a callback to be triggered on addon load, so that addons can be added to an autoloader
	 * @param \Closure $callback - The callback to use.
	 * @return \emberlabs\materia\Loader - Provides a fluent interface.
	 */
	public function setCallback(\Closure $callback)
	{
		$this->autoloader_callback = $callback;

		return $this;
	}

	/**
	 * Get an addon's metadata object
	 * @param string $addon - The addon to load.
	 * @return \emberlabs\materia\Metadata\MetadataBase|NULL - NULL if no object available, or the metadata object requested
	 */
	public function get($addon)
	{
		if($this->check($addon) === true)
		{
			return $this->metadata[$addon];
		}
		else
		{
			return NULL;
		}
	}

	/**
	 * Check to see if an addon has been loaded
	 * @param string $addon - The addon to check.
	 * @return boolean - True if addon is loaded, false if it was attempted to be loaded previously and failed, NULL if not loaded and no attempt has been made to load yet.
	 */
	public function check($addon)
	{
		if(isset($this->metadata[$addon]))
		{
			return ($this->metadata[$addon] !== false) ? true : false;
		}
		else
		{
			return NULL;
		}
	}

	/**
	 * Loads an addon's metadata object, verifies dependencies, and initializes the addon
	 * @param string $addon - The addon to load
	 * @param boolean $ignore_phar - Do we want to ignore phar loading?
	 * @return void
	 *
	 * @throws MetadataException
	 * @throws DependencyException
	 * @throws \LogicException
	 */
	public function load($addon, $ignore_phar = false)
	{
		// Check to see if the addon has already been loaded.
		if(isset($this->metadata[$addon]))
		{
			return;
		}

		$using_phar = ($this->addon_phar_dir === false) ? false : !$ignore_phar;
		$phar_path = $this->addon_phar_dir . $addon . '.phar';
		$metadata_class = '\\emberlabs\materia\\Metadata\\' . ucfirst($addon);

		require $this->findMetadata($addon, $using_phar);

		if(!class_exists($metadata_class))
		{
			throw new MetadataException('Addon metadata class not defined');
		}

		// We want to instantiate the addon's metadata object, and make sure it's the right type of object.
		$metadata = new $metadata_class($this);
		if(!($metadata instanceof \emberlabs\materia\Metadata\MetadataBase))
		{
			throw new \LogicException('Addon metadata class does not extend class MetadataBase');
		}

		// Let our addons check for their dependencies here.
		try
		{
			if(!$metadata->checkDependencies())
			{
				throw new DependencyException('Addon metadata object declares that its required dependencies have not been met');
			}
		}
		catch(\RuntimeException $e)
		{
			$this->metadata[$addon] = false;
			throw new DependencyException(sprintf('Dependency check failed, reason: %1$s', $e->getMessage()));
		}

		// If the addon's metadata object passes all checks and we're not using a phar file, then we add the addon's directory to the autoloader include path
		if($using_phar)
		{
			$set_path = 'phar://' . $phar_path . '/';
		}
		else
		{
			$set_path = $this->base_path . $this->addon_dir . $addon . '/';
		}

		// If we need to update an autoloader with new load paths, we trigger the autoloader callback that should have been defined earlier and provide it the $set_path var
		if($this->autoloader_callback !== NULL)
		{
			$_ac = $this->autoloader_callback;
			$_ac($set_path);
		}

		// Initialize the addon
		$metadata->initialize();

		// Store the metadata object in a predictable slot.
		$this->metadata[$addon] = $metadata;
	}

	/**
	 * Locate the addon's metadata file
	 * @param string $addon - The addon file to find the metadata object for
	 * @param boolean &$using_phar - Are we using a phar?
	 * @return string - The location of the metadata object file.
	 *
	 * @throws MetadataException
	 */
	protected function findMetadata($addon, &$using_phar)
	{
		$phar_path = $this->addon_phar_dir . $addon . '.phar';
		$metadata_path = '/emberlabs/materia/Metadata/' . ucfirst($addon) . '.php';

		// Check to see if there's a phar we are dealing with here before moving on to try to load the standard class files.
		if($using_phar !== false && file_exists($this->base_path . '/' . $phar_path))
		{
			if(!file_exists('phar://' . $phar_path . $metadata_path))
			{
				throw new MetadataException('Could not locate required addon metadata file');
			}

			return 'phar://' . $phar_path . $metadata_path;
		}
		else
		{

			if(!file_exists($this->base_path . $this->addon_dir . $addon . $metadata_path))
			{
				throw new MetadataException('Could not locate addon metadata file');
			}

			return $this->base_path . $this->addon_dir . $addon . $metadata_path;
		}
	}

	/**
	 * Iterator methods
	 */

	/**
	 * Iterator method, rewinds the array back to the first element.
	 * @return void
	 */
	public function rewind()
	{
		return reset($this->metadata);
	}

	/**
	 * Iterator method, returns the key of the current element
	 * @return scalar - The key of the current element.
	 */
	public function key()
	{
		return key($this->metadata);
	}

	/**
	 * Iterator method, checks to see if the current position is valid.
	 * @return boolean - Whether or not the current array position is valid.
	 */
	public function valid()
	{
		return (!is_null(key($this->metadata)));
	}

	/**
	 * Iterator method, gets the current element
	 * @return \emberlabs\materia\Metadata\MetadataBase - The current addon metadata object of focus.
	 */
	public function current()
	{
		return current($this->metadata);
	}

	/**
	 * Iterator method, moves to the next session available.
	 * @return void
	 */
	public function next()
	{
		next($this->metadata);
	}
}
