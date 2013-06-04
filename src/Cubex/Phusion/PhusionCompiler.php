<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Phusion;

use Cubex\View\ViewModel;

class PhusionCompiler
{
  protected $_rawTemplate;
  protected $_viewModel;
  protected $_compilers = [
    'comments',
    'translation',
    'echo',
    'viewModelEcho',
  ];

  /**
   * @param string    $rawTemplate     Raw Template Content
   * @param ViewModel $viewModel       View model handling the template
   */
  public function __construct($rawTemplate, ViewModel $viewModel = null)
  {
    $this->_rawTemplate = $rawTemplate;
    $this->_viewModel   = $viewModel;
  }

  /**
   * @return string Compiled PHP Code
   */
  public function compile()
  {
    $compiled = $this->_rawTemplate;
    foreach($this->_compilers as $compiler)
    {
      $compiled = $this->{"compile" . $compiler}($compiled);
    }
    return $compiled;
  }

  /**
   * @param $content
   *
   * {{#Comment}}
   * {{--Comment}}
   *
   * @return mixed
   */
  public function compileComments($content)
  {
    return preg_replace('/{{(--|\#)(.*?)}}/s', '<?php /** $2 **/ ?>', $content);
  }

  /**
   * @param $content
   *
   * {_Hello World_}  - $this->t("Hello World")
   *
   * @return mixed
   */
  public function compileTranslation($content)
  {
    return preg_replace(
      '/{_\s*(.*?)\s*_}/s',
      '<?php echo $this->t("$1"); ?>',
      $content
    );
  }

  /**
   * @param $content
   *
   * {{$hat}} - echo $hat;
   *
   * @return mixed
   */
  public function compileEcho($content)
  {
    return preg_replace('/{{\s*(.*?)\s*}}/s', '<?php echo $1; ?>', $content);
  }

  /**
   * @param $content
   *
   * [[[data]]]    - $this->getRaw("data");
   * [[Int|data]]  - $this->getInt("data");
   *
   * @return mixed
   */
  public function compileViewModelEcho($content)
  {
    $content = preg_replace(
      '/\[\[\[\s*(.*?)\s*\]\]\]/s',
      '<?php echo $this->getRaw("$1"); ?>',
      $content
    );

    return preg_replace(
      '/\[\[(.*?)\|\s*(.*?)\s*\]\]/s',
      '<?php echo $this->get$1("$2"); ?>',
      $content
    );
  }
}
