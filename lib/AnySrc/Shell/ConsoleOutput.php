<?php

namespace AnySrc\Shell;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;

/**
 * Custom console output module for symfony console component
 */
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

   /**
    * Write method for formatted output
    * @param string $messages
    * @param bool $newline
    * @param string $tag
    * @param int $type
    */
   protected function taggedwrite($messages, $newline = false, $tag='', $type = self::OUTPUT_NORMAL)
   {
      if(!empty($tag))
      {
         $messages="<".$tag.">".$messages."</".$tag.">";
      }

      parent::write($messages, $newline, $type);
   }

   /**
    * Fancy output for page names
    * @param \AnySrc\MarkdownBlog\Page $page
    */
   public function page(\AnySrc\MarkdownBlog\Page $page)
   {
      $flags = array();
      $flags[] = $page->getIsDisabled() ? "<ce>d</ce>" : "-";
      $flags[] = $page->getIsVisible() ? "-" : "<ce>h</ce>";
      $flags[] = $page->getIsInPrivateFolder() ? "<ce>p</ce>" : "-";

      $this->write("<ch>[ ::".$page->getHash()." ]</ch> ");
      if($page->getIsPropExists('searchscore'))
      {
         $this->write('<ct>[ Score: '.$page->getIsPropExists('searchscore').' ]</ct> ');
      }
      $this->write(implode("", $flags)." ");
      $this->write($page->getTitle()." <cf>~/".$page->getName()."</cf>");
   }

   /**
    * Fancy output for page names with newline
    * @param \AnySrc\MarkdownBlog\Page $page
    */
   public function pageln(\AnySrc\MarkdownBlog\Page $page)
   {
      $this->page($page);
      $this->writeln();
   }

   /**
    * Write line override with zero-parameters
    * @param string $messages
    * @param int $type
    */
   public function writeln($messages='', $type=self::OUTPUT_NORMAL)
   {
      parent::writeln($messages, $type);
   }

   /**
    * Error message
    * @param string $messages
    */
   public function err($messages)
   {
      $this->taggedwrite($messages, false, 'ce');
   }

   /**
    * Error message with newline
    * @param string $messages
    */
   public function errln($messages)
   {
      $this->taggedwrite($messages, true, 'ce');
   }

   /**
    * Command headline
    * @param string $messages
    */
   public function title($messages)
   {
      $this->taggedwrite($messages, false, 'ct');
   }

   /**
    * Command headline with newline
    * @param string $messages
    */
   public function titleln($messages)
   {
      $this->taggedwrite($messages, true, 'ct');
   }

   /**
    * Highlighted text
    * @param string $messages
    */
   public function hl($messages)
   {
      $this->taggedwrite($messages, false, 'ch');
   }

   /**
    * Highlighted text with newline
    * @param string $messages
    */
   public function hlln($messages)
   {
      $this->taggedwrite($messages, true, 'ch');
   }

   /**
    * Formatted text for filenames
    * @param string $messages
    */
   public function file($messages)
   {
      $this->taggedwrite($messages, false, 'cf');
   }

   /**
    * Formatted text for filenames with newline
    * @param string $messages
    */
   public function fileln($messages)
   {
      $this->taggedwrite($messages, true, 'cf');
   }

}
