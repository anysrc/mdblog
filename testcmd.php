#!/usr/bin/env php
<?php

define("DIR_ABSOLUTEBASE", __DIR__.DIRECTORY_SEPARATOR);

require __DIR__.'/vendor/autoload.php';
use AnySrc\Shell\System;
use AnySrc\Shell\Stdout;
use AnySrc\MarkdownBlog\PageCollection;


//--> Checks
if(!System::isCli())
{
   Stdout::writel("This is shell-only!");
   exit(1);
}

if(!System::isLinux())
{
   Stdout::errl("Currently only Linux is supported.");
   exit(1);
}


//--> TAN provider
$tan = new AnySrc\Security\Tan(__DIR__."/config/tokens.yml");


//--> Config
if(!is_file(__DIR__."/config/global.yml"))
{
   die(__DIR__."/config/global.yml not found");
}

$cfg = new \AnySrc\MarkdownBlog\GlobalConfig();
$cfg->loadFile(__DIR__."/config/global.yml", 'global');


//--> User defined configs
if(is_file(__DIR__."/config/user.yml"))
{
   $cfg->loadFile(__DIR__."/config/user.yml", 'user');
}


//--> Load collection
$collection = new PageCollection($app);
$collection->loadByRegexGlob(new AnySrc\RecursiveRegexGlob(__DIR__."/post", '/.+\.md$/'));


//--> Load App
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface as II;
use AnySrc\Shell\ConsoleOutput as OI;
use \AnySrc\MarkdownBlog\Version;

$app = new Application('anysrc mdblog', Version::getVersion());


//--> TAN for login
$app->register('newtan')->setCode(function(II $ii, OI $oi) use($tan, $cfg)
{
   $tstr = $tan->newLoginTan();
   $oi->writeln("New Tan: <ch>".$tstr."</ch>");
   $oi->writeln("Login URL: <ch>".$cfg->getPath('hostconfig/cmdhost', '/')."session/login#".$tstr."</ch>");
})
->setDescription('Generate a new login TAN');


//--> Clear all sessions
$app->register('cleartokens')->setCode(function(II $ii, OI $oi) use($tan)
{
   $oi->writeln('Clear all TANs and open sessions...');
   $tan->clear();
   $oi->writeln("Done!");
})
->setDescription('Delete all TANs and login sessions');


//--> List all sessions
$app->register('sessions')->setCode(function(II $ii, OI $oi) use($tan)
{
   $oi->writeln("Display unused TANs and current sessions.");
   $oi->writeln("Keys are stored as hash.");
   $oi->writeln();

   $unusedtans = $tan->getTans();
   $oi->titleln("Unused TANs (hashed, not plaintext)");
   if(is_array($unusedtans) && count($unusedtans)>0)
   {
      foreach($unusedtans as $unusedtan => $taninfo)
      {
         $oi->hlln($unusedtan);
         $oi->writeln("   Timeout: ".$taninfo['timeout']);
      }
   }
   else
   {
      $oi->errln("No unused TANs found.");
   }

   $oi->writeln();
   $oi->titleln("Current sessions (hashed, not plaintext)");

   $currentsessions = $tan->getSessions();
   if(is_array($currentsessions) && count($currentsessions)>0)
   {
      foreach($currentsessions as $currentsession => $sessioninfo)
      {
         $oi->hlln($currentsession);
         $oi->writeln("   IP: ".$sessioninfo['ip']);
         $oi->writeln("   Timeout: ".$sessioninfo['timeout']);
      }
   }
   else
   {
      $oi->errln("No sessions found.");
   }
})
->setDescription('List all TANs and active sessions');


//--> List pages
$app->register('pages')->setCode(function(II $ii, OI $oi) use($collection)
{
   $qry = $ii->getOption('search');
   $noi = ((int)$ii->getOption('top'));

   if($noi<=0)
   {
      $noi=100;
   }

   $oi->errln("Limit output to ".$noi." page".($noi==1 ? "" : "s"));

   if(!empty($qry))
   {
      $queryparser = new \AnySrc\SearchPropertyParser($qry);
      $search = new \AnySrc\MarkdownBlog\PageSearch($collection);
      $collection = $search->search($queryparser, 2, true);
      $collection->sort(array(array('property'=>'searchscore', 'direction'=>'ASC')));
   }
   else
   {
      $collection->sort(array(array('property'=>'postdate', 'direction'=>'DESC')));
   }

   $collection = $collection->getPagesByRange($noi, 0);

   if(!empty($qry))
   {
      $collection->sort(array(array('property'=>'searchscore', 'direction'=>'DESC')));
   }
   else
   {
      $collection->sort(array(array('property'=>'postdate', 'direction'=>'ASC')));
   }

   $oi->titleln('Found '.$collection->getCount().' page'.($collection->getCount()==1 ? '' : 's'));
   $oi->writeln();

   foreach($collection as $page)
   {
      $oi->pageln($page);
   }

   $oi->writeln();
   if($collection->getFirstPage()->getIsPropExists('searchscore'))
   {
      $oi->title('Score: ');
      $oi->writeln('search score, lower = better');
   }

   $oi->writeln("Flags: <ce>-d-</ce>isabled, <ce>-h-</ce>idden, <ce>-p-</ce>rivate category");
})
->setDescription('List or find pages')
->addOption('top', 't', InputOption::VALUE_OPTIONAL, 'Number of listed pages')
->addOption('search', 's', InputOption::VALUE_OPTIONAL, 'Search pattern');


//--> Write / Writefind
$app->register('write')->setCode(function(II $ii, OI $oi) use($collection)
{
   $name = $ii->getArgument('name');
   $ff = $ii->getOption('findfirst');
   $page = null;

   if($ff)
   {
      $queryparser = new \AnySrc\SearchPropertyParser($name);
      $search = new \AnySrc\MarkdownBlog\PageSearch($collection);
      $collection = $search->search($queryparser, 5, true);
      $collection->sort(array(array('property'=>'searchscore', 'direction'=>'ASC')));
      $page = $collection->getFirstPage();
   }
   else
   {
      $page = $collection->getPageAuto($name);
   }

   if($page===null && $ff===false && strpos($name, "::")!==0)
   {
      $choice = Stdout::readprompt("Page not found. Create? (y/n)");
      if($choice=="y")
      {
         $page = $collection->createPage($name);
      }
      else
      {
         $oi->errln('Aborted.');
         return;
      }
   }
   elseif($page===null)
   {
      $oi->errln('Page not found.');
      return;
   }

   $oi->write('Edit ');
   $oi->pageln($page);

   $editor = System::getFirstAvailableCommand(array(System::getEnv("EDITOR"), "nano", "vim", "vi"));
   if($editor===null)
   {
      $oi->errln("No compatible editor found.");
      $oi->errln("Please set \$EDITOR environment variable.");
      return;
   }

   $oi->writeln("Using <ch>".$editor."</ch> as editor");
   $oi->writeln("Launch...");
   sleep(1);

   System::ttyenvexec($editor, $page->getAbsoluteFilename());

   $oi->writeln("Done editing ");
   $oi->pageln($page);
})
->setDescription('Edit or create a page')
->addOption('findfirst', 'f', InputOption::VALUE_NONE, 'If set, find the best matching post and start editing')
->addArgument('name', InputArgument::REQUIRED, 'Filename, searchpattern, hashcode');


//--> Delete page
$app->register('delete')->setCode(function(II $ii, OI $oi) use($collection)
{
   $name = $ii->getArgument('name');
   $page = $collection->getPageAuto($name);

   if($page instanceof \AnySrc\MarkdownBlog\Page)
   {
      $oi->write("Page found: ");
      $oi->pageln($page);
      $oi->writeln();

      $choice = Stdout::readprompt("Really delete? (y/n)");
      if($choice=="y")
      {
         $page->delete();
         $oi->writeln("Page deleted.");
      }
      else
      {
         $oi->errln("Aborted.");
      }
   }
   else
   {
      $oi->errln("No page found.");
   }
})
->setDescription('Delete a page')
->addArgument('name', InputArgument::REQUIRED, 'Filename or hashcode');


//--> Folders
$app->register('folders')->setCode(function(II $ii, OI $oi) use($collection)
{
   $folders = new AnySrc\RecursiveRegexGlob($collection->getBaseFolder(), null, "/.*/", false, true);
   $folders->sortAsc();

   $oi->titleln($folders->count()." folder".($folders->count()==1 ? "" : "s")." found.");
   $oi->writeln();

   foreach($folders as $folder)
   {
      $mdfolder = substr($folder, strlen($collection->getBaseFolder()));
      $oi->file("~/".$mdfolder." ");
      $oi->write($collection->getFolderPages($mdfolder)->getCount()." pages inside");
      if(is_file($folder.DIRECTORY_SEPARATOR."folder.yml"))
      {
         $oi->writeln(", folder.yml");
      }
      else
      {
         $oi->writeln();
      }
   }
})
->setDescription('List all folders');


//--> State changes
$app->register('changestate')->setCode(function(II $ii, OI $oi) use($collection)
{
   $pages = $ii->getArgument('pages');
   $enabled = ($ii->getOption('enable') ? true : ($ii->getOption('disable') ? false : null));
   $visible = ($ii->getOption('show') ? true : ($ii->getOption('hide') ? false : null));

   if($enabled!==null)
   {
      $oi->writeln('Change state to <ch>'.($enabled===true ? 'enabled' : 'disabled').'</ch>');
   }

   if($visible!==null)
   {
      $oi->writeln('Change visibility to <ch>'.($visible===true ? 'visible' : 'hidden').'</ch>');
   }

   if($enabled===null && $visible===null)
   {
      $oi->errln('Please specify changes flags.');
      return;
   }

   $oi->writeln();

   foreach($pages as $pagename)
   {
      $page = $collection->getPageAuto($pagename);
      if($page instanceof \AnySrc\MarkdownBlog\Page)
      {
         if($enabled!==null)
         {
            $page->setIsDisabled(($enabled===false));
         }
         if($visible!==null)
         {
            $page->setIsVisible(($visible===true));
         }
         $page->write();
         $oi->pageln($page);
      }
      else
      {
         $oi->err('Page not found: ');
         $oi->writeln($pagename);
      }
   }

   $oi->writeln();
   $oi->writeln('Done!');
})
->setDescription('Change page states')
->addArgument('pages', InputArgument::IS_ARRAY, 'List of filenames or hashes')
->addOption('enable', null, InputOption::VALUE_NONE, 'Enable the pages')
->addOption('disable', null, InputOption::VALUE_NONE, 'Disable the pages')
->addOption('hide', null, InputOption::VALUE_NONE, 'Hide pages in lists')
->addOption('show', null, InputOption::VALUE_NONE, 'Show pages in lists');


//--> Upload URL
$app->register('upload')->setCode(function(II $ii, OI $oi) use($cfg)
{
   $oi->writeln("Upload URL: <ch>".$cfg->getPath('hostconfig/cmdhost', '/')."session/upload</ch>");
})
->setDescription('Print the upload URL');


//--> Load plugins
if($cfg->getPath('pluginsystem/enabled', false)===true)
{
   $plugins = $cfg->getPath('pluginsystem/load', array());
   $pman = new \AnySrc\MarkdownBlog\PluginManager($plugins, 'Backend');
   $pman->register($app);
}


//--> Run application
$app->run(null, new OI());
