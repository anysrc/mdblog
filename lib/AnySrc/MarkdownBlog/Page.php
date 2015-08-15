<?php

namespace AnySrc\MarkdownBlog;

/**
 * Single Blog page
 */
class Page
{

   /**
    * Collection of this page
    * @var \AnySrc\MarkdownBlog\PageCollection
    */
   private $collection;

   /**
    * Validation status
    * @var bool
    */
   private $valid;

   /**
    * File
    * @var string
    */
   private $twigfile;

   /**
    * Properties
    * @var string[]
    */
   private $properties;

   /**
    * Raw content of file
    * @var string
    */
   private $raw;

   /**
    * Properties string
    * @var string
    */
   private $propraw;

   /**
    * Markdown / Twig string
    * @var string
    */
   private $twigraw;


   function __construct($twigfile, PageCollection $collection=null)
   {
      $this->twigfile = $twigfile;
      $this->collection = $collection;
      $this->properties = array();
      $this->valid=false;
   }

   public function __toString()
   {
      return $this->getAbsoluteFilename();
   }


   /**
    * Set page collection
    * @param \AnySrc\MarkdownBlog\PageCollection $collection
    */
   public function setCollection(PageCollection $collection)
   {
      $this->collection = $collection;
   }


   /**
    * Validation status of file
    * @return bool
    */
   public function getIsValid()
   {
      return $this->valid;
   }


   /**
    * Modification date of file
    * @return \DateTime
    */
   public function getFileModificationTime()
   {
      //clearstatcache();
      $mtime = filemtime($this->getAbsoluteFilename());
      $date = \DateTime::createFromFormat('U', $mtime);
      return $date;
   }


   /**
    * Creationdate of file
    * @return \DateTime
    */
   public function getFileCreationTime()
   {
      //clearstatcache();
      $ctime = filectime($this->getAbsoluteFilename());
      $date = \DateTime::createFromFormat('U', $ctime);
      return $date;
   }


   /**
    * Absolute filename
    * @return string
    */
   public function getAbsoluteFilename()
   {
      return $this->twigfile;
   }


   /**
    * Absolute foldername
    * @return string
    */
   public function getAbsoluteFolder()
   {
      return dirname($this->twigfile);
   }


   /**
    * File exists
    * @return bool
    */
   public function isFileExists()
   {
      return is_file($this->getAbsoluteFilename());
   }


   /**
    * Get filename
    * @return string
    */
   public function getFilename()
   {
      return basename($this->twigfile);
   }


   /**
    * Get name of file with relative path
    * @return string
    */
   public function getName()
   {
      $temp = substr($this->getAbsoluteFilename(), strlen($this->collection->getBaseFolder()));
      $matches = array();
      $result = preg_match('/(.*)\.[^\.]+$/', $temp, $matches);

      if($result===1)
      {
         return $matches[1];
      }
      return $temp;
   }


   /**
    * Get relative folder
    * @return string
    */
   public function getNameFolder()
   {
      $tmp = dirname($this->getName());
      if($tmp==".")
      {
         return null;
      }
      return $tmp;
   }


   /**
    * Get the url from this page
    * @return string
    */
   public function getUrl()
   {
      return $this->collection->getApp()->url('post', array("name"=>$this->getName()));
   }


   /**
    * Get config of folder that containing this page
    * @return array
    */
   public function getFolderConfig()
   {
      $result = array();
      $cfg = $this->collection->getFolderConfig($this->getAbsoluteFolder());
      if(is_array($cfg) && isset($cfg['folder']))
      {
         $result = $cfg['folder'];
      }
      $result['namepath'] = $this->getNameFolder();
      return $result;
   }


   /**
    * Is Page in private Category
    * @return bool
    */
   public function getIsInPrivateFolder()
   {
      $cfg = $this->getFolderConfig();
      return (isset($cfg['private']) && $cfg['private']===true);
   }


   /**
    * Get date of this page
    * @return \DateTime
    */
   public function getPostDate()
   {
      $match = array();
      if($this->getIsPropExists('date'))
      {
         $time = strtotime($this->getProp('date'));
         return \DateTime::createFromFormat("U", $time);
      }
      elseif(preg_match("/([0-9]{4}-[0-9]{2}-[0-9]{2})/", $this->getFilename(), $match)===1)
      {
         return \DateTime::createFromFormat("Y-m-d", $match[1]);
      }
      else
      {
         return $this->getFileCreationTime();
      }
   }


   /**
    * Clear this file content
    */
   public function clear()
   {
      $this->parseText("title: Empty file\n---\n\nPlease edit this file.\n");
   }


   /**
    * Parse raw from file
    * @throws \Exception
    */
   public function parse()
   {
      if(!$this->isFileExists())
      {
         throw new \Exception("Twig file not found");
      }

      $subject = file_get_contents($this->getAbsoluteFilename());
      $this->parseText($subject);
   }


   /**
    * Parse raw from valiable
    * @param string $text
    */
   public function parseText($text)
   {
      $this->raw = $text;

      $splitter = "\n---\n";
      $posa = strpos($this->raw, $splitter);
      $posb = $posa+strlen($splitter);

      $this->valid=true;
      if($posa!==false && $posa>0)
      {
         $this->propraw = trim(substr($this->raw, 0, $posa));
         $this->twigraw = trim(substr($this->raw, $posb));
      }
      else
      {
         $this->propraw = "title: [ERROR] ".$this->getName();
         $this->twigraw = $text."\n";
      }
      $this->parseProperties();
   }


   /**
    * Write page to file
    * @throws \Exception
    */
   public function write()
   {
      $this->buildPropertiesRaw();
      $this->buildRaw();
      $c = file_put_contents($this->getAbsoluteFilename(), $this->getRaw());

      if(!(is_int($c) && $c>0))
      {
         throw new \Exception("Could not write twig file");
      }
   }


   /**
    * Dlete this page
    * @throws \Exception
    */
   public function delete()
   {
      if($this->isFileExists())
      {
         @unlink($this->getAbsoluteFilename());
         $this->remove();
      }
      if($this->isFileExists())
      {
         throw new \Exception("Could not delete ".$this->getAbsoluteFilename());
      }
   }


   /**
    * Remove from collection
    */
   public function remove()
   {
      $this->collection->removePage($this);
   }


   /**
    * Parse properties
    */
   protected function parseProperties()
   {
      $matches = array();
      $result = preg_match_all('/^([a-zA-Z_\-\.0-9]+):(.*)$/im', $this->propraw, $matches, PREG_SET_ORDER);

      if(is_int($result) && $result>0)
      {
         foreach($matches as $match)
         {
            $this->properties[trim($match[1])] = trim($match[2]);
         }
      }

      $b = $this->applyDefaultProperties();
      if($b===true && $this->getIsValid())
      {
         $this->write();
      }
   }


   /**
    * Build raw string from object data
    */
   protected function buildRaw()
   {
      $this->raw = trim($this->propraw)."\n---\n\n".trim($this->twigraw)."\n\n";
   }


   /**
    * Build raw properties from object data
    */
   protected function buildPropertiesRaw()
   {
      $result = "";
      foreach($this->properties as $name => $value)
      {
         $result .= $name.": ".$value."\n";
      }
      $this->propraw = trim($result);
      $this->buildRaw();
   }


   /**
    * Get raw content of this page
    * @return string
    */
   public function getRaw()
   {
      return $this->raw;
   }


   /**
    * Get raw properties of this page
    * @return string
    */
   public function getPropertiesRaw()
   {
      return $this->propraw;
   }


   /**
    * Set raw properties of this page and reparse
    * @param string $props
    */
   public function setPropertiesRaw($props)
   {
      $this->propraw = $props;
      $this->parseProperties();
      $this->buildRaw();
   }


   /**
    * Build shortcut hash for this page
    * @return string
    */
   protected function calculateHash()
   {
      $str = "-".$this->getAbsoluteFilename()."-".microtime(true)."-".mt_rand(0, 999999999)."-";
      $hash = hash("sha512", $str);

      $length = 7;
      $min = 0;
      $max = strlen($hash)-$length-1;
      $start = mt_rand($min, $max);

      $result = substr($hash, $start, $length);
      return $result;
   }


   /**
    * Apply default property values
    * @return bool
    */
   public function applyDefaultProperties()
   {
      $defaults = array(
          "hash" => null,
          "title" => "Untitled",
          "tags" => "untagged",
          "visible" => "true",
          "disabled" => "false",
      );

      if(!$this->getIsPropExists('hash'))
      {
         $defaults['hash'] = $this->calculateHash();
      }

      if(!$this->getIsPropExists("date") && $this->isFileExists())
      {
         $defaults['date'] = $this->getFileCreationTime()->format("Y-m-d H:i:s");
      }
      elseif(!$this->getIsPropExists("date"))
      {
         $defaults['date'] = (new \DateTime('now'))->format("Y-m-d H:i:s");
      }

      $i=0;
      foreach($defaults as $name => $value)
      {
         if(!$this->getIsPropExists($name))
         {
            $this->setProp($name, $value);
            $i++;
         }
      }

      return ($i>0);
   }


   /**
    * Get raw page content
    * @return string
    */
   public function getTemplateRaw()
   {
      return $this->twigraw;
   }


   /**
    * Get raw page introduction
    * @param int $clines Number of lines
    * @return string
    */
   public function getTemplateRawIntro($clines=10)
   {
      $temp = $this->twigraw;

      $pos = strpos($temp, "\n#");
      if($pos!==false && $pos>0)
      {
         $temp = substr($temp, 0, $pos)."\n\n";
      }

      $lines = explode("\n", $temp);
      if(count($lines)>$clines)
      {
         $lines = array_slice($lines, 0, $clines);
      }

      return implode("\n", $lines);
   }


   /**
    * Set raw page content and reparse
    * @param string $s
    */
   public function setTemplateRaw($s)
   {
      $this->twigraw = $s;
      $this->buildRaw();
   }


   /**
    * Get Property
    * @param string $name
    * @param string $default
    * @return string
    */
   public function getProp($name=null, $default=null)
   {
      if(isset($this->properties[$name]))
      {
         return $this->properties[$name];
      }
      else
      {
         return $default;
      }
   }


   /**
    * Get itemlist from property
    * @param string $name
    * @param string $default
    * @param string $separator
    * @return array
    */
   public function getPropList($name, $default=array(), $separator=",")
   {
      $prop = $this->getProp($name);
      if($prop!==null)
      {
         $result = array();
         $temp = explode($separator, $prop);
         foreach($temp as $t)
         {
            $t = trim($t);
            if(!empty($t))
            {
               $result[] = $t;
            }
         }
         return $result;
      }
      return $default;
   }


   /**
    * Check for property exist
    * @param string $name
    * @return bool
    */
   public function getIsPropExists($name)
   {
      return isset($this->properties[$name]);
   }


   /**
    * Set property
    * @param string $name
    * @param string $value
    */
   public function setProp($name, $value)
   {
      $this->properties[$name] = $value;
      $this->buildPropertiesRaw();
   }


   /**
    * Delete property
    * @param string $name
    */
   public function unsetProp($name)
   {
      unset($this->properties[$name]);
      $this->buildPropertiesRaw();
   }


   /**
    * Get all properties
    * @return string[]
    */
   public function getProps()
   {
      return $this->properties;
   }


   /**
    * Unique page hash
    * @return string
    */
   public function getHash()
   {
      return $this->getProp('hash');
   }


   public function getHashUrl()
   {
      return $this->collection->getApp()->url('hash', array('hash'=>$this->getHash()));
   }


   /**
    * Get title property
    * @return string
    */
   public function getTitle()
   {
      return $this->getProp('title', "Untitled Page");
   }


   /**
    * Get shorttitle property
    * @return string
    */
   public function getShortTitle()
   {
      return $this->getProp('shorttitle', "Untitled");
   }


   /**
    * Get description property
    * @return string
    */
   public function getDescription()
   {
      return $this->getProp('description', $this->getProp("title"));
   }


   /**
    * Get keyworkds property
    * @return string
    */
   public function getKeywords()
   {
      return $this->getProp('keywords');
   }


   /**
    * Get Tags
    * @return array
    */
   public function getTags()
   {
      $result = array();
      $list = $this->getPropList('tags');
      foreach($list as $item)
      {
         $item = strtolower($item);
         if(!isset($result[$item]))
         {
            $result[$item] = array(
                "name" => $item,
            );
         }
      }
      return $result;
   }


   public function getTagNames()
   {
      $data = $this->getTags();
      $result = array();
      foreach($data as $item)
      {
         $result[] = $item['name'];
      }
      return $result;
   }


   /**
    * Is page visible
    * @return bool
    */
   public function getIsVisible()
   {
      return ($this->getProp("visible", "true")==="true");
   }


   /**
    * Set visible status
    * @param bool $b
    */
   public function setIsVisible($b)
   {
      $this->setProp("visible", ($b===true ? "true" : "false"));
   }


   /**
    * Is page disabled
    * @return bool
    */
   public function getIsDisabled()
   {
      return ($this->getProp("disabled", "false")==="true");
   }


   /**
    * Set disabled status
    * @param bool $b
    */
   public function setIsDisabled($b)
   {
      $this->setProp("disabled", ($b===true ? "true" : "false"));
   }


   /**
    * Is page hidden in lists of parent folders
    * @return bool
    */
   public function getIsFolderHiddenInParent()
   {
      $cfg = $this->getFolderConfig();
      return (isset($cfg['hiddeninparent']) && $cfg['hiddeninparent']===true);
   }


   /**
    * Compare two Pages by property name, works like strcmp()
    * @param \AnySrc\MarkdownBlog\Page $page
    * @param type $property
    * @return int
    */
   public function strcmpBy(Page $page, $property)
   {
      $valuea = "";
      $valueb = "";

      $method = "get".$property;
      if(is_callable(array($page, $method)) || method_exists($this, $method))
      {
         if(is_callable(array($this, $method)))
         {
            $valuea = $this->$method();
         }
         if(is_callable(array($page, $method)))
         {
            $valueb = $page->$method();
         }
      }
      elseif($page->getIsPropExists($property) || $this->getIsPropExists($property))
      {
         $valuea = $this->getProp($property, "");
         $valueb = $page->getProp($property, "");
      }
      else
      {
         return 0;
      }

      // Comparable
      if($valuea instanceof \DateTime || $valueb instanceof \DateTime ||
            is_numeric($valuea) || is_numeric($valueb))
      {
         if(is_numeric($valuea) || is_numeric($valueb))
         {
            $valuea = ((double)$valuea);
            $valueb = ((double)$valueb);
         }
         return ($valuea < $valueb ? -1 : ($valuea > $valueb ? 1 : 0));
      }
      else
      {
         return strcmp($valuea, $valueb);
      }

   }



}
