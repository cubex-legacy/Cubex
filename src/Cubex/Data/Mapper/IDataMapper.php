<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data\Mapper;

interface IDataMapper
{
  /**
   * Name for ID field
   *
   * @return string Name of ID field
   */
  public function getIdKey();

  /**
   * Get the ID for this mapper
   *
   * @return mixed
   */
  public function id();

  /**
   * Load the mapper content
   *
   * @param null $id
   *
   * @return static
   * @throws \Exception
   */
  public function load($id = null);

  /**
   * Save modifications to this mapper
   *
   * @return bool
   * @throws \Exception
   */
  public function saveChanges();

  /**
   * Delete this mapper from its data source
   *
   * @return bool
   * @throws \Exception
   */
  public function delete();
}
