<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper\Database;

class Relationship
{
  protected $_source;

  public function __construct(RecordMapper $source)
  {
    $this->_source = $source;
  }

  public function hasOne(RecordMapper $entity, $foreignKey = null)
  {
    $source = $this->_source;
    if($foreignKey === null)
    {
      $foreignKey = strtolower(class_shortname($source)) . '_id';
      $foreignKey = $source->stringToColumnName($foreignKey);
    }

    $source->setRecentRelationKey($foreignKey);

    if($source->id() !== null)
    {
      $table = new RecordCollection($entity);
      $result = $table->loadOneWhere(
        $source->idPattern(),
        $foreignKey,
        $source->id()
      );
    }
    else
    {
      $result = null;
    }

    if($result !== null && $result instanceof RecordMapper)
    {
      $result->setFromRelationshipType(RecordMapper::RELATIONSHIP_HASONE);
      return $result;
    }
    else if($source->createsNewInstanceOnFailedRelation())
    {
      $entity->setRecentRelationKey($foreignKey);
      $entity->setFromRelationshipType(RecordMapper::RELATIONSHIP_HASONE);
      $entity->setData($foreignKey, $source->id());
      $entity->touch();
      return $entity;
    }
    else
    {
      return null;
    }
  }

  public function hasMany(RecordMapper $entity, $foreignKey = null)
  {
    $source = $this->_source;
    if($foreignKey === null)
    {
      $foreignKey = strtolower(class_shortname($source)) . '_id';
      $foreignKey = $source->stringToColumnName($foreignKey);
    }

    $entity->setRecentRelationKey($foreignKey);
    $entity->setFromRelationshipType(RecordMapper::RELATIONSHIP_HASMANY);

    $collection = new RecordCollection($entity);
    $collection->loadWhereAppend(
      $source->idPattern(),
      $foreignKey,
      $source->id()
    );
    $collection->setCreateData([$foreignKey => $source->id()]);
    return $collection;
  }

  public function belongsTo(
    RecordMapper $entity, $foreignKey = null, $localKey = null
  )
  {
    $source = $this->_source;
    if($foreignKey === null)
    {
      $foreignKey = strtolower(class_shortname($entity)) . '_id';
      $foreignKey = $source->stringToColumnName($foreignKey);
    }

    $entity->setFromRelationshipType(RecordMapper::RELATIONSHIP_BELONGSTO);

    $key = $source->getAttribute($foreignKey)->data();
    if($key !== null)
    {
      return $entity->load($key);
    }
    else
    {
      if($source->createsNewInstanceOnFailedRelation())
      {
        if($localKey === null)
        {
          $localKey = strtolower(class_shortname($source)) . '_id';
          $localKey = $source->stringToColumnName($localKey);
        }

        $entity->setRecentRelationKey($foreignKey);

        if($entity->attributeExists($localKey))
        {
          $entity->setData($localKey, $source->id());
        }
        $entity->touch();
        return $entity;
      }
      else
      {
        return false;
      }
    }
  }
}
