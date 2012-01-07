<?php

namespace CrazyLoader;

class ClassNotFoundException
{
  public function __construct($message)
  {
    echo $message;
    error_log($message);
  }
}

class CrazyLoader {

  private $classMap;
  private $cacheFile;

  public function __construct($cacheFile = 'classMap.cache', $recompile = false)
  {
    $this->cacheFile = $cacheFile;

    if($recompile || !file_exists($this->cacheFile))
    {
      @unlink($this->cacheFile);
      $this->compile();
    } else {
      $this->classMap = unserialize(file_get_contents($this->cacheFile));
    }

    spl_autoload_register(array($this, 'loadClass'));
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
    $directoryIterator = new \RecursiveDirectoryIterator(realpath(__DIR__));
    $fileIterator = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::SELF_FIRST);
    $regexIterator = new \RegexIterator($fileIterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);

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
            $className = '';
          }
        }
      }
      $this->addMapping($filePath, $classNames);
    }
    file_put_contents($this->cacheFile, serialize($this->getClassMap()));
  }

  /**
   * getClassMap() : array
   *
   * Returns the Class->File mappings as an array.
   *
   * @return array $classMap
   */
  public function getClassMap()
  {
    return $this->classMap;
  }

  /**
   * addMapping() : void
   *
   * Given a filename and an array of class names (namespaces included),
   * creates a mapping between those classes and a given file name.
   *
   * @param string $filePath
   *    The realpath to a given PHP file
   * @param array $classNames
   *    A set of class names (including namespaces) contained within
   *    the file at $filePath
   */
  public function addMapping($filePath, $classNames)
  {
    foreach($classNames as $className)
    {
      $this->classMap[$className] = $filePath;
    }
  }

  /**
   * loadClass() : void
   *
   * Includes a required file given a class name.
   *
   * @param string $className
   *    A fully-qualified class name.
   */
  public function loadClass($className)
  {
    if(!isset($this->classMap[$className]))
    {
      throw new ClassNotFoundException("The required class $className could not be loaded.");
    }

    require_once($this->classMap[$className]);
  }
}

$autoload = new CrazyLoader();

print_r($autoload->getClassMap());
