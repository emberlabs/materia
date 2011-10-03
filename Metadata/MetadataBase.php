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

namespace emberlabs\materia\Metadata;
use \emberlabs\materia\Internal\DependencyException;

/**
 * materia - Addon metadata base class,
 *      Defines common methods and properties for addon metadata objects to use.
 *
 *
 * @category    materia
 * @package     materia
 * @author      emberlabs.org
 * @license     MIT License
 * @link        https://github.com/emberlabs/materia
 */
abstract class MetadataBase
{
	/**
	 * @var string - The addon's version.
	 */
	protected $version = '';

	/**
	 * @var string - The addon's author information.
	 */
	protected $author = '';

	/**
	 * @var string - The addon's name.
	 */
	protected $name = '';

	/**
	 * @var string - The addon's description.
	 */
	protected $description = '';

	/**
	 * @var \emberlabs\materia\Loader - The addon loader.
	 */
	protected $loader;

	/**
	 * @var string - The alias of this metadata object.
	 */
	private $alias;

	/**
	 * @ignore - preventing override of __construct on metadata objects
	 */
	final public function __construct(\emberlabs\materia\Loader $loader)
	{
		$this->loader = $loader;
	}

	/**
	 * Stores the alias for this metadata object
	 * @param string $alias - The alias for the object.
	 * @return \emberlabs\materia\\Metadata\MetadataBase - Provides a fluent interface.
	 */
	final public function setAlias($alias)
	{
		$this->alias = $alias;

		return $this;
	}

	/**
	 * Get the alias for this metadata object.
	 * @return string - The metadata object's instance.
	 */
	final public function getAlias()
	{
		return $this->alias;
	}

	/**
	 * Loads an addon dependency for the current addon, complete with error handling
	 * @param string $slot - The slot to check for the dependency in.
	 * @param string $name - The name of the addon dependency to load if the slot isn't occupied
	 * @return boolean - Returns true if the dependency is properly loaded.
	 *
	 * @throws DependencyException
	 */
	final public function loadAddonDependency($slot, $name)
	{
		$loaded = $this->loader->check($slot);
		if($loaded === NULL)
		{
			try
			{
				$this->loader->load($name);
				return true;
			}
			catch(\RuntimeException $e)
			{
				throw new DependencyException(sprintf('Failed to load dependency addon "%1$s", error message "%2$s"', $name, $e->getMessage()));
			}
		}
		elseif($loaded === false)
		{
			throw new DependencyException('Failed to load dependency addon "%1$s", previous load attempted failed');
		}
		else
		{
			return true;
		}
	}

	/**
	 * Get the version stamp of the addon.
	 * @return string - The version stamp of the addon.
	 */
	final public function getVersion()
	{
		return $this->version;
	}

	/**
	 * Get the author for the addon.
	 * @return string - The author data for the addon.
	 */
	final public function getAuthor()
	{
		return $this->author;
	}

	/**
	 * Get the name of the addon.
	 * @return string - The addon's name.
	 */
	final public function getName()
	{
		return $this->name;
	}

	/**
	 * Get this addon's description
	 * @return string - The addon description.
	 */
	final public function getDescription()
	{
		return $this->description;
	}

	/**
	 * Hooking method for addon metadata objects, called to initialize the addon after the dependency check has been passed.
	 * @return void
	 */
	public function initialize() { }

	/**
	 * Hooking method for addon metadata objects for executing own code on pre-load dependency check.
	 * @return boolean - Does the addon pass the dependency check?
	 */
	public function checkDependencies() { }
}
