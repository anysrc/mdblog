<?php

namespace AnySrc\MarkdownBlog;

/**
 * Collection of blog pages
 */
class PageCollection implements \Iterator
{

   const DIRECTION_ASC = "ASC";
   const DIRECTION_DESC ="DESC";

   /**
    * Silex App
    * @var \AnySrc\MyApplication
    */
   private $app;

   /**
    * Root folder of all pages
    * @var string
    */
   private $basefolder;

   /**
    * Iterator variable
    * @var int
    */
   private $iposition;

   /**
    * Array of pages in this collection
    * @var \AnySrc\MarkdownBlog\Page[]
    */
   private $pages;


   function __construct(\AnySrc\MyApplication $app, $basefolder=null)
   {
      $this->rewind();
      $this->pages = array();
      $this->app = $app;
      $this->basefolder = $basefolder;
   }


   /**
    * Iterator method
    * @link http://php.net/manual/en/language.oop5.iterations.php PHP Iterator
    * @return bool
    */
   public function valid()
   {
      return isset($this->pages[$this->iposition]);
   }


   /**
    * Iterator method
    * @link http://php.net/manual/en/language.oop5.iterations.php PHP Iterator
    * @return \AnySrc\MarkdownBlog\Page
    */
   public function current()
   {
      return $this->pages[$this->iposition];
   }


   /**
    * Iterator method
    * @link http://php.net/manual/en/language.oop5.iterations.php PHP Iterator
    */
   public function rewind()
   {
      $this->iposition = 0;
   }


   /**
    * Iterator method
    * @link http://php.net/manual/en/language.oop5.iterations.php PHP Iterator
    * @return int
    */
   public function key()
   {
      return $this->iposition;
   }


   /**
    * Iterator method
    * @link http://php.net/manual/en/language.oop5.iterations.php PHP Iterator
    */
   public function next()
   {
      $this->iposition++;
   }


   /**
    * Silex application
    * @return \AnySrc\MyApplication
    */
   public function getApp()
   {
      return $this->app;
   }


   /**
    * Global configuration
    * @return \AnySrc\MarkdownBlog\GlobalConfig
    */
   public function getCfg()
   {
      return $this->app['gcfg'];
   }


   /**
    * Number of pages in this PageCollection
    * @return int
    */
   public function getCount()
   {
      return count($this->pages);
   }


   /**
    * Root folder of all pages
    * @return string
    */
   public function getBaseFolder()
   {
      return $this->basefolder;
   }


   /**
    * Load pages from glob pattern
    * @link http://php.net/glob PHP function glob()
    * @param string $globpattern
    */
   public function loadPages($globpattern)
   {
      $this->basefolder = DIRECTORY_SEPARATOR.trim(realpath(dirname($globpattern)), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

      // Load page files
      $pages = glob($globpattern);
      foreach($pages as $page)
      {
         $obj = new Page($page, $this);
         $obj->parse();

         $this->pages[] = $obj;
      }
      unset($page);
   }


   /**
    * Load pages from regular expression
    * @param \AnySrc\RecursiveRegexGlob $iterator
    */
   public function loadByRegexGlob(\AnySrc\RecursiveRegexGlob $iterator)
   {
      $this->basefolder = DIRECTORY_SEPARATOR.trim(realpath($iterator->getDir()), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

      foreach($iterator as $file)
      {
         $obj = new Page($file, $this);
         $obj->parse();

         $this->pages[] = $obj;
      }
      unset($file);
   }


   public function cloneCollection()
   {
      $c = new PageCollection($this->getApp(), $this->getBaseFolder());
      $c->importPages($this->toArray());
      return $c;
   }


   /**
    * Load pages from array
    * @param \AnySrc\MarkdownBlog\Page[] $pages
    */
   public function importPages(array $pages)
   {
      foreach($pages as $page)
      {
         $temp = clone $page;
         if($temp instanceof Page && $this->contains($temp)===false)
         {
            $temp->setCollection($this);
            $this->pages[] = $temp;
         }
      }
   }


   /**
    * Remove page from collection
    * @param \AnySrc\MarkdownBlog\Page $page
    */
   public function removePage(Page $page)
   {
      if($this->contains($page))
      {
         $key = array_search ($page, $this->pages);
         unset($this->pages[$key]);
      }
   }


   /**
    * Create new page with given name
    * @param string $name Path inside the base folder
    * @return \AnySrc\MarkdownBlog\Page
    * @throws \Exception
    */
   public function createPage($name)
   {
      $file = $this->getBaseFolder().$name.".md";
      if(is_file($file))
      {
         throw new \Exception("File already exist");
      }
      elseif(file_exists($file))
      {
         throw new \Exception("Exist, but is not a regular file (maybe a directory?)");
      }

      $newpage = new Page($file, $this);

      if(!is_dir($newpage->getAbsoluteFolder()))
      {
         mkdir($newpage->getAbsoluteFolder(), 0777, true);
         if(!is_dir($newpage->getAbsoluteFolder()))
         {
            throw new \Exception("Could not create folder");
         }
      }

      $newpage->clear();
      $newpage->write();
      return $newpage;
   }


   /**
    * Get array of pages
    * @return \AnySrc\MarkdownBlog\Page[]
    */
   public function toArray()
   {
      return $this->pages;
   }


   /**
    * First page in collection
    * @return \AnySrc\MarkdownBlog\Page
    */
   public function getFirstPage()
   {
      if(isset($this->pages[0]))
      {
         return $this->pages[0];
      }
      return null;
   }


   /**
    * Last page in collection
    * @return \AnySrc\MarkdownBlog\Page
    */
   public function getLastPage()
   {
      if(is_array($this->pages) && count($this->pages)>0)
      {
         return end($this->pages);
      }
      return null;
   }


   /**
    * Get all folders with pages
    * @param bool $ignorehiddeninfolderlist
    * @return array
    */
   public function getPageFolders($ignorehiddeninfolderlist=false)
   {
      $result = array();
      foreach($this->pages as $page)
      {
         $tmp = $page->getFolderConfig();
         if($ignorehiddeninfolderlist===true || !(isset($tmp['hiddeninfolderlist']) && $tmp['hiddeninfolderlist']===true))
         {
            if(!isset($result[$tmp['namepath']]))
            {
               $result[$tmp['namepath']] = $tmp;
            }
         }
      }
      return $result;
   }


   /**
    * Get folder configuration by absolute path
    * @param string $folder
    * @return array
    */
   public function getFolderConfig($folder)
   {
      $default = array(
         "folder" => array(
            "template" => array(
               "list" => "@view/list.html.twig",
               "single" => "@view/single.html.twig",
            ),
            "itemsperpage" => 5,
            "sort" => array(
               array(
                  "property" => "postdate",
                  "direction" => "desc",
               ),
            ),
            "private" => false,
         ),
      );

      $base = $this->getBaseFolder();
      $cfgfile = DIRECTORY_SEPARATOR.trim($folder, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR."folder.yml";
      $cfgscanner = new \AnySrc\RecursiveConfig($base, $cfgfile, $default);

      $cfg = $cfgscanner->getConfig();
      if(is_array($cfg) && isset($cfg['folder']))
      {
         return $cfg;
      }
      return null;
   }


   /**
    * Tag cloud sorted by use counter
    * @return array
    */
   public function getTagCloud()
   {
      $result = array();
      foreach($this->pages as $page)
      {
         $list = $page->getTags();
         foreach($list as $item)
         {
            if(isset($result[$item['name']]))
            {
               $result[$item['name']]['usectnr']++;
            }
            else
            {
               $result[$item['name']] = $item;
               $result[$item['name']]['usectnr'] = 1;
            }
         }
      }

      uasort($result, function($a,$b)
      {
         $cirteria = array('usectnr'=>'desc', 'name'=>'asc');
         foreach($cirteria as $what => $order)
         {
            if($a[$what]==$b[$what]) { continue; }
            return (($order == 'desc') ? -1 : 1) * strcmp($a[$what], $b[$what]);
         }
         return null;
      });

      return $result;
   }


   /**
    * Get X pages, skip the first Y pages
    * @return \AnySrc\MarkdownBlog\PageCollection
    */
   public function getPagesByRange($top, $skip=0)
   {
      $result = array();
      $i=0; $j=0;
      foreach($this->pages as $page)
      {
         if($i<$skip) { $i++; continue; }
         if($j>=$top) { break; }

         $result[] = $page;
         $j++;
      }

      $c = new PageCollection($this->getApp(), $this->getBaseFolder());
      $c->importPages($result);

      return $c;
   }


   /**
    * Create new collection of all pages with property=search
    * @param string $property Property Name
    * @param string $search Search pattern
    * @param bool $regex Is search pattern regex
    * @param bool $list Search for list item
    * @return \AnySrc\MarkdownBlog\PageCollection
    */
   public function getPagesByProperty($property, $search, $regex=false, $list=false)
   {
      $result = array();
      $method = "get".ucfirst(strtolower($property));
      $propcall = ($list===true ? "getPropList" : "getProp");

      foreach($this->pages as $page)
      {
         $checkvalues = null;
         if($page->getIsPropExists($property))
         {
            $checkvalues = $page->$propcall($property);
         }
         elseif($list===false && method_exists($page, $method) && is_callable(array($page, $method)))
         {
            $checkvalues = $page->$method();
         }

         if($list===false)
         {
            $checkvalues = array($checkvalues);
         }

         foreach($checkvalues as $checkvalue)
         {
            if(($regex===true && preg_match($search, $checkvalue)===1) || ($regex===false && $search==$checkvalue))
            {
               $result[] = $page;
               break;
            }
         }
      }

      $c = new PageCollection($this->getApp(), $this->getBaseFolder());
      $c->importPages($result);

      return $c;
   }


   /**
    * Filter pages by folderconfig propety
    * @param string $path array path
    * @param mixed $search
    * @param bool $regex
    * @return \AnySrc\MarkdownBlog\PageCollection
    */
   public function getPagesByFolderConfigProperty($path, $search, $regex=false)
   {
      $result = array();

      foreach($this->pages as $page)
      {
         $value = \AnySrc\ArrayPath::getPath($page->getFolderConfig(), $path);

         if(($regex===false && $value==$search) || ($regex===true && preg_match($search, $value)===1))
         {
            $result[] = $page;
         }
      }

      $c = new PageCollection($this->getApp(), $this->getBaseFolder());
      $c->importPages($result);

      return $c;
   }


   /**
    * Contains this collection pages with property = search
    * @param string $property Property Name
    * @param string $search Search pattern
    * @param bool $regex Is search pattern regex
    * @param bool $list Search for list item
    * @return bool
    */
   public function hasPagesWithProperty($property, $search, $regex=false, $list=false)
   {
      $collection = $this->getPagesByProperty($property, $search, $regex, $list);
      return ($collection->getCount()>0);
   }


   /**
    * Contains this collection disabled pages
    * @return bool
    */
   public function hasDisabledPages()
   {
      return $this->hasPagesWithProperty('isdisabled', true);
   }


   /**
    * Contains this collection invisible pages
    * @return bool
    */
   public function hasInvisiblePages()
   {
      return $this->hasPagesWithProperty('isvisible', false);
   }


   /**
    * Contains this collection pages with syntax errors
    * @return bool
    */
   public function hasInvalidPages()
   {
      return $this->hasPagesWithProperty('isvalid', false);
   }


   /**
    * Get first page by property = search
    * @param string $property
    * @param string $search
    * @return \AnySrc\MarkdownBlog\Page
    */
   public function getFirstPageByProperty($property, $search)
   {
      $c = $this->getPagesByProperty($property, $search);
      return $c->getFirstPage();
   }


   /**
    * Get last page by property = search
    * @param string $property
    * @param string $search
    * @return \AnySrc\MarkdownBlog\Page
    */
   public function getLastPageByProperty($property, $search)
   {
      $c = $this->getPagesByProperty($property, $search);
      return $c->getLastPage();
   }


   /**
    * Get page by his name
    * @param string $name
    * @return \AnySrc\MarkdownBlog\Page
    */
   public function getPageByName($name)
   {
      return $this->getFirstPageByProperty("name", $name);
   }


   /**
    * Check page exist by his name
    * @param string $name
    * @return bool
    */
   public function isPageExist($name)
   {
      return ($this->getPageByName($name) instanceof Page);
   }


   /**
    * Get page by hash
    * @param string $hash
    * @return \AnySrc\MarkdownBlog\Page
    * @throws \Exception
    */
   public function getPageByHash($hash)
   {
      if(strpos($hash, "::")===0)
      {
         $hash = substr($hash, 2);
      }

      $collection = $this->getPagesByProperty("hash", $hash);
      if($collection->getCount()==1)
      {
         return $collection->getFirstPage();
      }
      elseif($collection->getCount()>1)
      {
         throw new \Exception("Multiple pages found. ERROR!");
      }
      return null;
   }


   /**
    * Check hash exist
    * @param string $hash
    * @return bool
    */
   public function isHashExist($hash)
   {
      return ($this->getPageByHash($hash) instanceof Page);
   }


   /**
    * Get page by name or hash
    * @return \AnySrc\MarkdownBlog\Page
    */
   public function getPageAuto($nameorhash)
   {
      $page = $this->getPageByHash($nameorhash);
      if(is_null($page))
      {
         $page = $this->getPageByName($nameorhash);
      }
      return $page;
   }

   /**
    * Check page exist and detect hash or pagename
    * @return bool
    */
   public function isPageExistAuto($nameorhash)
   {
      return ($this->getPageAuto($nameorhash) instanceof Page);
   }


   /**
    * Collection contains page
    * @param \AnySrc\MarkdownBlog\Page $page
    * @return bool
    */
   public function contains(Page $page)
   {
      return $this->isHashExist($page->getHash());
   }


   /**
    * Find pages by a searchpattern in given properties
    * @param string[] $searchproperties
    * @param string $searchpattern
    * @param bool $exact
    * @return \AnySrc\MarkdownBlog\PageCollection
    */
   public function findPages(array $searchproperties, $searchpattern, $exact=false, $caseless=true)
   {
      $result = array();
      foreach($searchproperties as $searchprop)
      {
         $rgx = "/.*?".preg_quote($searchpattern, "/").".*?/".($caseless===true ? "i" : "");
         if($exact===true)
         {
            $rgx = '/^'.preg_quote($searchpattern, "/").'$/'.($caseless===true ? "i" : "");
         }

         $pages = $this->getPagesByProperty($searchprop, $rgx, true);
         foreach($pages as $page)
         {
            if(!in_array($page, $result))
            {
               $result[] = $page;
            }
         }
      }

      $collection = new PageCollection($this->getApp(), $this->getBaseFolder());
      $collection->importPages($result);
      return $collection;
   }


   /**
    * Find the first page by a searchpattern in given properties
    * @param string[] $searchproperties
    * @param string $searchpattern
    * @param bool $exact
    * @return \AnySrc\MarkdownBlog\PageCollection
    */
   public function findPagesFirst(array $searchproperties, $searchpattern, $exact=false, $caseless=true)
   {
      $collection = $this->findPages($searchproperties, $searchpattern, $exact, $caseless);
      if($collection instanceof PageCollection && $collection->getCount()>0)
      {
         return $collection->getFirstPage();
      }
      return null;
   }


   /**
    * Get pages by folder
    * @param string $folder
    * @param bool $ignorehiddeninparent
    * @return \AnySrc\MarkdownBlog\PageCollection
    */
   public function getFolderPages($folder, $ignorehiddeninparent=false)
   {
      $rgx = '/^'.preg_quote($folder, '/').'/';
      $collection = $this->getPagesByProperty("namefolder", $rgx, true);

      $result = new PageCollection($this->getApp(), $this->getBaseFolder());
      foreach($collection as $page)
      {
         if($ignorehiddeninparent===true || !($page->getNameFolder()!=$folder && $page->getIsFolderHiddenInParent()))
         {
            $result->importPages(array($page));
         }
      }

      return $result;
   }


   /**
    * Sort pages by property
    * @param string[] $properties
    * @return \AnySrc\MarkdownBlog\PageCollection
    */
   public function sort(array $properties)
   {
      if(isset($properties['property']) && isset($properties['direction']))
      {
         $properties = array($properties);
      }

      usort($this->pages, function($a,$b) use($properties)
      {
         foreach($properties as $property)
         {
            $temp = ((strtolower($property['direction']) == 'desc') ? -1 : 1) * $a->strcmpBy($b, $property['property']);
            if($temp==0) continue;
            return $temp;
         }
         return null;
      });

      return $this;
   }


   /**
    * Sort pages ascending
    * @param string $property
    * @return \AnySrc\MarkdownBlog\PageCollection
    */
   public function sortAsc($property)
   {
      $this->sort(array("property"=>$property, "direction"=>self::DIRECTION_ASC));
      return $this;
   }


   /**
    * Sort pages descending
    * @param string $property
    * @return \AnySrc\MarkdownBlog\PageCollection
    */
   public function sortDesc($property)
   {
      $this->sort(array("property"=>$property, "direction"=>self::DIRECTION_DESC));
      return $this;
   }


}
