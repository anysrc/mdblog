<?php

namespace AnySrc;

/**
 * Search for files by regex
 */
class RecursiveRegexGlob implements \Iterator
{

   private $dir;
   private $filergx;
   private $directoryrgx;
   private $list;
   private $position;

   private $searchfiles;
   private $searchdirs;

   public function __construct($dir, $filergx="/.*/", $dirrgx="/.*/", $searchfiles=true, $searchdirs=false)
   {
      $this->dir = $dir;
      $this->filergx = $filergx;
      $this->directoryrgx = $dirrgx;
      $this->list = array();
      $this->searchfiles=($searchfiles===true);
      $this->searchdirs=($searchdirs===true);
      $this->rewind();

      $this->recursiveScan($this->dir);
   }

   private function recursiveScan($dir)
   {
      $d = new \DirectoryIterator($dir);
      foreach($d as $info)
      {
         if(in_array($info->getBasename(), array(".", "..")))
         {
            continue;
         }
         if($info->isDir() && preg_match($this->directoryrgx, $info->getBasename())===1)
         {
            if($this->searchdirs===true)
            {
               $this->list[] = $info->getRealPath();
            }
            $this->recursiveScan($info->getRealPath());
         }
         elseif($this->searchfiles===true && $info->isFile() && preg_match($this->filergx, $info->getBasename())===1)
         {
            $this->list[] = $info->getRealPath();
         }
      }
   }

   public function count()
   {
      return count($this->list);
   }

   public function sortAsc()
   {
      sort($this->list);
   }

   public function sortDesc()
   {
      rsort($this->list);
   }

   public function getDir()
   {
      return $this->dir;
   }

   public function current()
   {
      return $this->list[$this->position];
   }

   public function key()
   {
      return $this->position;
   }

   public function next()
   {
      $this->position++;
   }

   public function rewind()
   {
      $this->position=0;
   }

   public function valid()
   {
      return isset($this->list[$this->position]);
   }

}
