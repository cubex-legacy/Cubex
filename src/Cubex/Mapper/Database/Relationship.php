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
      $table  = new RecordCollection($entity);
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
    $collection->loadWhere($source->idPattern(), $foreignKey, $source->id());
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

  public function hasAndBelongsToMany(
    RecordMapper $entity, $localKey = null, $foreignKey = null, $table = null
  )
  {
    $source = $this->_source;

    $class  = strtolower(class_shortname($source));
    $eClass = strtolower(class_shortname($entity));

    if($table === null)
    {
      $sT     = $source->getTableName();
      $prefix = str_replace($class . 's', '', $sT);
      $prefix = trim($prefix, '_');

      if($eClass > $class)
      {
        $table = implode('_', [$prefix, $class . 's', $eClass . 's']);
      }
      else
      {
        $table = implode('_', [$prefix, $eClass . 's', $class . 's']);
      }
    }

    if($foreignKey === null)
    {
      $foreignKey = $eClass . '_id';
      $foreignKey = $source->stringToColumnName($foreignKey);
    }

    if($localKey === null)
    {
      $localKey = $class . '_id';
      $localKey = $source->stringToColumnName($localKey);
    }

    $pivot = new PivotMapper();
    $pivot->setTableName($table);
    $pivot->addAttribute($localKey);
    $pivot->addAttribute($foreignKey);
    $pivot->addCompositeAttribute("id", [$localKey, $foreignKey]);

    $collection = new RecordCollection($pivot);
    $collection->loadWhere($source->idPattern(), $localKey, $source->id());
    $collection->get();

    $findIds = $collection->getUniqueField($foreignKey);

    $finalCollection = $entity::collection();

    if(!empty($findIds))
    {
      $finalCollection->loadIds($findIds);
    }
    else
    {
      $finalCollection->setLoaded(true);
    }

    return $finalCollection;
  }
}
