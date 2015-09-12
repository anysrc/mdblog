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
   $oi->writeln("Flags: <ce>-d-</ce>isabled, <ce>-h-</ce>idden, <ce>-p-</ce>rivate category");
})
->setDescription('List or find pages')
->addOption('top', 't', InputOption::VALUE_OPTIONAL, 'Number of listed pages')
->addOption('search', 's', InputOption::VALUE_OPTIONAL, 'Search pattern');


//--> Run application
$app->run(null, new OI());
