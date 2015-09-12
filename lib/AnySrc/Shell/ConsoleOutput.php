<?php

namespace AnySrc\Shell;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class ConsoleOutput extends \Symfony\Component\Console\Output\ConsoleOutput
{

   public function __construct($verbosity = self::VERBOSITY_NORMAL, $decorated = null, OutputFormatterInterface $formatter = null)
   {
      parent::__construct($verbosity, $decorated, $formatter);

      $this->getFormatter()->setStyle('ct', new OutputFormatterStyle('green'));
      $this->getFormatter()->setStyle('ch', new OutputFormatterStyle('yellow'));
      $this->getFormatter()->setStyle('ce', new OutputFormatterStyle('red'));
      $this->getFormatter()->setStyle('cf', new OutputFormatterStyle('magenta'));
   }

   public function __destruct()
   {
      $this->writeln();
   }

   protected function taggedwrite($messages, $newline = false, $tag='', $type = self::OUTPUT_NORMAL)
   {
      if(!empty($tag))
      {
         $messages="<".$tag.">".$messages."</".$tag.">";
      }

      parent::write($messages, $newline, $type);
   }

   public function page(\AnySrc\MarkdownBlog\Page $page)
   {
      $flags = array();
      $flags[] = $page->getIsDisabled() ? "<ce>d</ce>" : "-";
      $flags[] = $page->getIsVisible() ? "-" : "<ce>h</ce>";
      $flags[] = $page->getIsInPrivateFolder() ? "<ce>p</ce>" : "-";

      $this->write("<ch>[ ::".$page->getHash()." ]</ch> ".
         implode("", $flags)." ".
         $page->getTitle()." <cf>~/".$page->getName()."</cf>");
   }

   public function pageln(\AnySrc\MarkdownBlog\Page $page)
   {
      $this->page($page);
      $this->writeln();
   }

   public function writeln($messages='', $type=self::OUTPUT_NORMAL)
   {
      parent::writeln($messages, $type);
   }

   public function err($messages)
   {
      $this->taggedwrite($messages, false, 'ce');
   }

   public function errln($messages)
   {
      $this->taggedwrite($messages, true, 'ce');
   }

   public function title($messages)
   {
      $this->taggedwrite($messages, false, 'ct');
   }

   public function titleln($messages)
   {
      $this->taggedwrite($messages, true, 'ct');
   }

   public function hl($messages)
   {
      $this->taggedwrite($messages, false, 'ch');
   }

   public function hlln($messages)
   {
      $this->taggedwrite($messages, true, 'ch');
   }

   public function file($messages)
   {
      $this->taggedwrite($messages, false, 'cf');
   }

   public function fileln($messages)
   {
      $this->taggedwrite($messages, true, 'cf');
   }

}
