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
	protected $base_path = '';

	protected $addon_phar_dir = 'lib/addons/';

	protected $addon_dir = 'addons/';

	protected $autoloader_callback;

	/**
	 * @var array - Array of instantiated metadata objects.
	 */
	protected $metadata = array();

	public function __construct($base_path, $addon_dir, $addon_phar_dir = false)
	{
		// asdf
	}

	public function setBasePath($base_path)
	{
		// asdf
	}

	public function setAddonDirs($addon_dir, $addon_phar_dir = false)
	{
		// asdf
	}

	public function setCallback(\Closure $callback)
	{
		// asdf
	}

	public function get($addon)
	{
		// asdf
	}

	public function check($addon)
	{
		// asdf
	}

	/**
	 * Loads an addon's metadata object, verifies dependencies, and initializes the addon
	 * @return void
	 *
	 * @throws \RuntimeException
	 * @throws \LogicException
	 */
	public function load($addon, $ignore_phar = false)
	{
		// Check to see if the addon has already been loaded.
		if(isset($this->metadata[$addon]))
		{
			return;
		}

		$using_phar = false;
		$addon_uc = ucfirst($addon);
		$phar_path = $this->addon_phar_dir . "{$addon}.phar";
		$metadata_path = "/emberlabs/materia/Metadata/{$addon_uc}.php";
		$metadata_class = "\\emberlabs\materia\\Metadata\\{$addon_uc}";

		// Check to see if there's a phar we are dealing with here before moving on to try to load the standard class files.
		if(!$ignore_phar && file_exists($this->base_path . "/{$phar_path}"))
		{
			$using_phar = true;

			if(!file_exists("phar://{$phar_path}/{$metadata_path}"))
			{
				throw new \RuntimeException('Could not locate required addon metadata file');
			}

			require "phar://{$phar_path}/{$metadata_path}";
		}
		else
		{

			if(!file_exists($this->base_path . $this->addon_dir . "{$addon}{$metadata_path}"))
			{
				throw new \RuntimeException('Could not locate addon metadata file');
			}

			require $this->base_path . $this->addon_dir . "{$addon}{$metadata_path}";
		}


		if(!class_exists($metadata_class))
		{
			throw new \RuntimeException('Addon metadata class not defined');
		}

		// We want to instantiate the addon's metadata object, and make sure it's the right type of object.
		$metadata = new $metadata_class;
		if(!($metadata instanceof \emberlabs\materia\Metadata\MetadataBase))
		{
			throw new \LogicException('Addon metadata class does not extend class MetadataBase');
		}

		// Let our addons check for their dependencies here.
		try
		{
			if(!$metadata->checkDependencies())
			{
				throw new \RuntimeException('Addon metadata object declares that its required dependencies have not been met');
			}
		}
		catch(\Exception $e)
		{
			throw new \RuntimeException(sprintf('Dependency check failed, reason: %1$s', $e->getMessage()));
		}

		// If the addon's metadata object passes all checks and we're not using a phar file, then we add the addon's directory to the autoloader include path
		if($using_phar)
		{
			$set_path = "phar://{$phar_path}/";
		}
		else
		{
			$set_path = $this->base_path . $this->addon_dir . "{$addon}/";
		}

		if($this->autoloader_callback !== NULL)
		{
			$t = $this->autoloader_callback;
			$t($set_path);
		}

		// Initialize the addon
		$metadata->initialize();

		// Store the metadata object in a predictable slot.
		$this->metadata[$addon] = $metadata;
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
