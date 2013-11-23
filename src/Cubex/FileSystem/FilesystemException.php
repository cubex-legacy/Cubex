<?php
/**
 * @author  facebook
 * @author  brooke.bryan
 */

namespace Cubex\FileSystem;

/**
 * Exception thrown by Filesystem to indicate an error accessing the file
 * system.
 *
 */
final class FilesystemException extends \Exception
{

  protected $_path;

  /**
   * Create a new FilesystemException, providing a path and a message.
   *
   * @param  $path    string  Path that caused the failure.
   * @param  $message string  Description of the failure.
   */
  public function __construct($path, $message)
  {
    $this->_path = $path;
    parent::__construct($message);
  }

  /**
   * Retrieve the path associated with the exception. Generally, this is
   * something like a path that couldn't be read or written, or a path that
   * was expected to exist but didn't.
   *
   * @return string  Path associated with the exception.
   */
  public function getPath()
  {
    return $this->_path;
  }
}
