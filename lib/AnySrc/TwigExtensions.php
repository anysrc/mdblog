<?php

namespace AnySrc;

class TwigExtensions extends \Twig_Extension
{

   private $twig;
   private $parsedown;

   public function initRuntime(\Twig_Environment $environment)
   {
      $this->twig = $environment;
      parent::initRuntime($environment);
   }

   public function setParsedown(\Parsedown $p)
   {
      $this->parsedown = $p;
   }

   public function getFilters()
   {
      return array(
          new \Twig_SimpleFilter('parsedown', array($this, 'parseParsedown'), array('is_safe' => array('html'))),
          new \Twig_SimpleFilter('tplexists', array($this, 'fileExists')),
          new \Twig_SimpleFilter('gcfg', array($this, 'getGlobalConfig'), array('is_safe' => array('html'))),
          new \Twig_SimpleFilter('tplcfg', array($this, 'getTemplateConfig'), array('is_safe' => array('html'))),
      );
   }

   public function getName()
   {
      return "anysrctwigextensions";
   }

   public function fileExists($s)
   {
      try
      {
         $this->twig->getLoader()->getSource($s);
         return true;
      }
      catch(\Twig_Error_Loader $ex)
      {
         return false;
      }
   }

   public function parseParsedown($s)
   {
      return $this->parsedown->parse($s);
   }

   public function getGlobalConfig($s)
   {
      $globals = $this->twig->getGlobals();
      $app = $globals['app'];
      $r = $app['gcfg']->getPath($s);
      return $r;
   }

   public function getTemplateConfig($s)
   {
      $globals = $this->twig->getGlobals();
      $app = $globals['app'];
      $r = $app['tplcfg']->getPath($s);
      return $r;
   }

}
