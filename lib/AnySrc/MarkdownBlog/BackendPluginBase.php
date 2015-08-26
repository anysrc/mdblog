<?php

namespace AnySrc\MarkdownBlog;

abstract class BackendPluginBase extends \AnySrc\MarkdownBlog\PluginBase
{

   public function getHelp()
   {
      return array();
   }


   public function getRoutePrefix()
   {
      return "/".$this->getPluginKey()."/";
   }


   abstract public function getDisplayName();

}
