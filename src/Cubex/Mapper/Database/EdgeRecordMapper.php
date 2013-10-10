<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Database;

use Cubex\Mapper\DataMapper;

/**
 * Class EdgeRecordMapper
 * Extend this class within your project, to create a global mapper edge table.
 *
 * This replaces the requirement of many pivot mapper classes, and tables.
 *
 */
abstract class EdgeRecordMapper extends RecordMapper
{
  protected $_idType = self::ID_COMPOSITE;

  public $source;
  public $destination;
  /**
   * @longtext
   */
  public $data;

  public function __construct($id = null, $columns = ['*'])
  {
    $loadId = [];
    if(is_array($id))
    {
      $currentClass = $currentId = null;
      foreach($id as $idPart)
      {
        if($idPart instanceof DataMapper)
        {
          $currentId    = $idPart->id();
          $currentClass = get_class($idPart);
        }
        else if(is_scalar($idPart))
        {
          if($currentId === null)
          {
            $currentId = $idPart;
          }
          else if($currentClass === null)
          {
            $currentClass = $idPart;
          }
        }
        else
        {
          throw new \Exception("Unexpected ID on edge " . $idPart);
        }

        if($currentClass !== null && $currentId !== null)
        {
          $loadId[]     = $currentId;
          $loadId[]     = $currentClass;
          $currentClass = $currentId = null;
        }
      }
    }

    if($id === null)
    {
      parent::__construct($id, $columns);
    }
    else if(count($loadId) === 4)
    {
      parent::__construct($loadId, $columns);
    }
    else
    {
      throw new \Exception("Invalid Edge Load by ID " . implode(", ", $loadId));
    }
  }

  protected function _configure()
  {
    $this->_addPolymorphicAttribute("source");
    $this->_addPolymorphicAttribute("destination");
    $this->_addCompositeAttribute(
      "id",
      [
      "source_id",
      "source_type",
      "destination_id",
      "destination_type",
      ]
    );
    $this->_setSerializer("data");
  }

  public static function fromSource(DataMapper $source)
  {
    return self::collection(
      [
      "source_id"   => $source->id(),
      "source_type" => get_class($source),
      ]
    );
  }

  public static function fromDestination(DataMapper $destination)
  {
    return self::collection(
      [
      "destination_id"   => $destination->id(),
      "destination_type" => get_class($destination),
      ]
    );
  }

  public static function fromAny(DataMapper $entity)
  {
    return self::collection(
      "((%C = %d AND %C = %s) OR (%C = %d AND %C = %s))",
      "source_id",
      $entity->id(),
      "source_type",
      get_class($entity),
      "destination_id",
      $entity->id(),
      "destination_type",
      get_class($entity)
    );
  }

  public static function create(
    DataMapper $source, DataMapper $destination, $data = null
  )
  {
    $creation = new static();
    /**
     * @var $creation EdgeRecordMapper
     */
    $creation->source      = $source;
    $creation->destination = $destination;
    $creation->data        = $data;
    $creation->saveChanges();

    return $creation;
  }
}
