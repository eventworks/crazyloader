<?php

class CrazyLoader {

  private $classMap;

  public function __construct() {}

  public function getClassMap()
  {
    return $this->classMap;
  }

  /**
   * compile() : void
   *
   * Locates all files from the given directory recursively, parses them to
   * find any defined classes within. With this information it will build a
   * mapping of defined classes to their respective files. Example structure:
   *
   * $this->classMap = array(
   *  'My\\Awesome\\Namespace\\MyClass' => '/var/www/example.com/src/My/Awesome/Namespace/MyClass.php',
   *  'Another\\Namespace\\AnotherClass' => '/var/www/example.com/src/poorlyNamedclassFyle.class.php'
   * );
   */
  public function compile()
  {
    $directoryIterator = new RecursiveDirectoryIterator(realpath(__DIR__));
    $fileIterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::SELF_FIRST);
    $regexIterator = new RegexIterator($fileIterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

    foreach($regexIterator as $filePath => $fileObject){
      $sourceCode = file_get_contents($filePath);
      $tokens = token_get_all($sourceCode);

      $namespace = '';
      $className = '';
      $classNames = array();
      foreach($tokens as $key => $token)
      {
        if(($tokens[$key][0] == T_STRING) && ($tokens[$key-1][0] == T_WHITESPACE)) 
        {
          if($tokens[$key-2][0] == T_NAMESPACE)
          {
            $namespace = $tokens[$key][1];

            while(($tokens[$key][0] != T_WHITESPACE) && ($tokens[$key] != ';'))
            {
              $key++;
              $namespace .= isset($tokens[$key][1]) ? $tokens[$key][1] : '';
            }
          } 
          elseif($tokens[$key-2][0] == T_CLASS)
          {
            $className .= $namespace . '\\' . $tokens[$key][1];
            $classNames[] = $className;
          }
        }
      }
      $this->addMapping($filePath, $classNames);
    }
  }

  public function addMapping($filePath, $classNames)
  {
    foreach($classNames as $className)
    {
      $this->classMap[$className] = $filePath;
    }
  }
}

$autoload = new CrazyLoader();
$autoload->compile();

print_r($autoload->getClassMap());
