<?php
/**
 * @author  Richard.Gooding
 */

namespace Cubex\Email\Service\DatabaseMailer;

use Cubex\Mapper\Database\RecordMapper;

/**
 * Mapper for messages stored in the database by the DatabaseMailer service
 */
class MailerMapper extends RecordMapper
{
  /**
   * @datatype varchar(255)
   */
  public $recipients;
  /**
   * @datatype varchar(255)
   */
  public $ccs;
  /**
   * @datatype varchar(255)
   */
  public $bccs;
  /**
   * @datatype varchar(255)
   */
  public $subject;
  /**
   * @datatype mediumtext
   */
  public $htmlBody;
  /**
   * @datatype mediumtext
   */
  public $textBody;
  /**
   * @datatype varchar(255)
   */
  public $from;
  /**
   * @datatype varchar(255)
   */
  public $sender;
  /**
   * @datatype varchar(255)
   */
  public $returnPath;
  /**
   * @datatype text
   */
  public $headers;
  /**
   * @datatype varchar(255)
   */
  public $files;
  /**
   * @datatype mediumtext
   */
  public $rawMessage;

  /**
   * @datatype int
   */
  public $campaignId;
}
