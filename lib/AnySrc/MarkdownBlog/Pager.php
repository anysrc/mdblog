<?php

namespace AnySrc\MarkdownBlog;

class Pager
{

   const PAGEMIN = 1;
   private $itemsperpage;

   public function __construct($itemsperpage)
   {
      $this->itemsperpage = $itemsperpage;
   }


   /**
    * Minimal page number
    * @return int
    */
   public function min()
   {
      return self::PAGEMIN;
   }


   /**
    * Calculate current page
    * @param \AnySrc\MarkdownBlog\PageCollection $collection
    * @param int $page
    * @return int
    */
   public function page(PageCollection $collection, $page)
   {
      $page = ((int)$page);
      $itemcount = $collection->getCount();
      $pagemax = ceil(($itemcount/$this->itemsperpage));

      if($page<self::PAGEMIN) { $page = self::PAGEMIN; }
      if($page>$pagemax) { $page = $pagemax; }

      return $page;
   }


   /**
    * Get page maximum
    * @param \AnySrc\MarkdownBlog\PageCollection $collection
    * @return int
    */
   public function max(PageCollection $collection)
   {
      $itemcount = $collection->getCount();
      $pagemax = ceil(($itemcount/$this->itemsperpage));
      return $pagemax;
   }

   
   /**
    * Get collection with pages of current page
    * @param \AnySrc\MarkdownBlog\PageCollection $collection
    * @param int $page
    * @return \AnySrc\MarkdownBlog\PageCollection
    */
   public function calc(PageCollection $collection, $page)
   {
      $page = $this->page($collection, $page);
      $skip = ($page-1)*$this->itemsperpage;
      return $collection->getPagesByRange($this->itemsperpage, $skip);
   }

}
