<?php

namespace AnySrc\MarkdownBlog;

class PageSearch
{

   /**
    * Page collection
    * @var \AnySrc\MarkdownBlog\PageCollection
    */
   protected $collection;

   /**
    * Contains last result pattern hits
    * @var bool
    */
   protected $containspatterns;

   /**
    * Contains last result title hits
    * @var bool
    */
   protected $containstitles;

   /**
    * Contains last result content hits
    * @var bool
    */
   protected $containscontents;

   /**
    * Contains last result tag hits
    * @var bool
    */
   protected $containstags;

   /**
    * Contains last result folder hits
    * @var bool
    */
   protected $containsfolders;

   /**
    * Contains last result state hits
    * @var bool
    */
   protected $containsstates;


   public function __construct(PageCollection $collection)
   {
      $this->collection = $collection;
   }


   /**
    * Perform search
    * @param string $q
    * @param float $maximumscore
    * @param bool $ignorehiddeninparent
    * @return \AnySrc\MarkdownBlog\PageCollection
    */
   public function search(\AnySrc\SearchPropertyParser $q, $maximumscore=2, $ignorehiddeninparent=true)
   {
      $filteredcollection = $this->collection->cloneCollection();

      //--> Parse query
      $tags = $q->getByProperty('tag');
      $titles = $q->getByProperty('title');
      $contents = $q->getByProperty('content');
      $folders = $q->getByProperty('folder');
      $patterns = $q->getByProperty(null);
      $states = $q->getByProperty('state');

      $this->containspatterns = count($patterns)>0;
      $this->containstitles = count($titles)>0;
      $this->containscontents = count($contents)>0;
      $this->containstags = count($tags)>0;
      $this->containsfolders = count($folders)>0;
      $this->containsstates = count($states)>0;

      //--> Pages by state
      $stateprops = array(
          'hidden' => array('isvisible', false),
          'visible' => array('isvisible', true),
          'disabled' => array('isdisabled', true),
          'enabled' => array('isdisabled', false),
      );

      //--> Pages by folder
      if(count($folders)>0)
      {
         $temp = null;
         foreach($folders as $folder)
         {
            if($temp===null)
            {
               $temp = $filteredcollection->getFolderPages($folder, $ignorehiddeninparent);
            }
            else
            {
               $temp->importPages($filteredcollection->getFolderPages($folder, $ignorehiddeninparent)->toArray());
            }
         }
         $filteredcollection = $temp;
      }
      else
      {
         $filteredcollection = $filteredcollection->getFolderPages(null, $ignorehiddeninparent);
      }

      //--> Pages by state
      if(count($states)>0)
      {
         $temp=null;
         foreach($states as $state)
         {
            if(!isset($stateprops[$state]))
            {
               continue;
            }

            if($temp===null)
            {
               $temp = $filteredcollection->getPagesByProperty($stateprops[$state][0], $stateprops[$state][1]);
            }
            else
            {
               $temp->importPages($filteredcollection->getPagesByProperty($stateprops[$state][0], $stateprops[$state][1])->toArray());
            }
         }
         $filteredcollection = $temp;
      }

      //--> Pages by Tags
      if(count($tags)>0)
      {
         $temp=null;
         foreach($tags as $tag)
         {
            if($temp===null)
            {
               $temp = $filteredcollection->getPagesByProperty("tags", $tag, false, true);
            }
            else
            {
               $temp->importPages($filteredcollection->getPagesByProperty("tags", $tag, false, true)->toArray());
            }
         }
         $filteredcollection = $temp;
      }

      //--> Levenshtein search
      if(count($titles)>0 || count($contents)>0 || count($patterns)>0)
      {
         foreach($filteredcollection as $page)
         {
            $score = \AnySrc\Levenshtein::compareTextMultiple(array(
                array("text" => $page->getTitle(), "patterns" => $titles),
                array("text" => $page->getTemplateRaw(), "patterns" => $contents),
                array("text" => $page->getTitle()." ".$page->getTemplateRaw(), "patterns" => $patterns),
            ));

            $page->setProp('searchscore', $score);
            if($page->getProp('searchscore')>$maximumscore)
            {
               $page->remove();
            }
         }
      }

      return $filteredcollection;
   }


   /**
    * Contains last search pattern hits?
    * @return bool
    */
   public function getContainspatterns()
   {
      return $this->containspatterns;
   }

   /**
    * Contains last search title hits?
    * @return bool
    */
   public function getContainstitles()
   {
      return $this->containstitles;
   }

   /**
    * Contains last search content hits?
    * @return bool
    */
   public function getContainscontents()
   {
      return $this->containscontents;
   }

   /**
    * Contains last search tag hits?
    * @return bool
    */
   public function getContainstags()
   {
      return $this->containstags;
   }

   /**
    * Contains last search folder hits?
    * @return bool
    */
   public function getContainsfolders()
   {
      return $this->containsfolders;
   }

   /**
    * Contains last search state hits?
    * @return bool
    */
   public function getContainsstates()
   {
      return $this->containsstates;
   }

}
