#!/usr/bin/env php
<?php

define("DIR_ABSOLUTEBASE", __DIR__.DIRECTORY_SEPARATOR);

//--> Init script
header("Content-Type: text/plain; charset=UTF-8", true);
require __DIR__."/vendor/autoload.php";
use \Symfony\Component\HttpFoundation\Request;
use AnySrc\Shell\Stdout;
use AnySrc\Shell\Args;
use AnySrc\MarkdownBlog\PageCollection;
use AnySrc\MyApplication;
use AnySrc\Shell\System;


//--> Initialize application
$app = new MyApplication();


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


//--> Parse arguments
unset($argv[0]);
$args = Args::fromArgv($argv);


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


//--> TAN for login
$app->get('newtan', function() use($tan, $cfg)
{
   $tstr = $tan->newLoginTan();
   Stdout::writel("New Tan: [highlight]".$tstr."[/highlight]");
   Stdout::writel("Login URL: [highlight]".$cfg->getPath('hostconfig/cmdhost', '/')."session/login#".$tstr."[/highlight]");
   return "";
});


//--> Clear all sessions
$app->get('cleartokens', function() use($tan)
{
   Stdout::writel("Clear all TANs and open sessions...");
   $tan->clear();
   Stdout::writel("Done!");
   return "";
});


//--> List all sessions
$app->get('sessions', function() use($tan)
{
   Stdout::writel("Display unused TANs and current sessions.");
   Stdout::writel("Keys are stored as hash.");
   Stdout::nl();

   $unusedtans = $tan->getTans();
   Stdout::writel("[head]Unused TANs (hashed, not plaintext)[/head]");
   if(is_array($unusedtans) && count($unusedtans)>0)
   {
      foreach($unusedtans as $unusedtan => $taninfo)
      {
         Stdout::writel("[highlight]".$unusedtan."[/highlight]");
         Stdout::writel("   Timeout: ".$taninfo['timeout']);
      }
   }
   else
   {
      Stdout::errl("No unused TANs found.");
   }

   Stdout::nl();
   Stdout::writel("[head]Current sessions (hashed, not plaintext)[/head]");

   $currentsessions = $tan->getSessions();
   if(is_array($currentsessions) && count($currentsessions))
   {
      foreach($currentsessions as $currentsession => $sessioninfo)
      {
         Stdout::writel("[highlight]".$currentsession."[/highlight]");
         Stdout::writel("   IP: ".$sessioninfo['ip']);
         Stdout::writel("   Timeout: ".$sessioninfo['timeout']);
      }
   }
   else
   {
      Stdout::errl("No sessions found.");
   }

   return "";
});


//--> Find posts
$app->get('find/{pattern}', function($pattern) use($collection)
{
   $properties = array("title", "name", "tags");
   $result = $collection->findPages($properties, $pattern);

   if($result->getCount()>0)
   {
      Stdout::writel("[head]Found ".$result->getCount()." page(s):[/head]");
      Stdout::nl();

      $result->sortAsc("name");
      foreach($result as $page)
      {
         Stdout::pagenamel($page);
      }
   }
   else
   {
      Stdout::errl("Nothing found.");
   }

   return "";
})
->assert('pattern', '.*');


//--> Latest posts
$app->get('latest/{num}', function($num) use($collection)
{
   $result = $collection->sortDesc('postdate')->getPagesByRange($num)->sortAsc('postdate');

   if($result->getCount()>0)
   {
      Stdout::writel("[head]List last ".$result->getCount()." page(s):[/head]");
      Stdout::nl();

      foreach($result as $page)
      {
         Stdout::pagenamel($page);
      }
   }
   else
   {
      Stdout::errl("Nothing found.");
   }

   return "";
})
->assert('num', '^[0-9]+$')
->value('num', 10);


$app->get('latest', function() use($app)
{
   $subRequest = Request::create('/latest/10', 'GET');
   return $app->handle($subRequest, \Symfony\Component\HttpKernel\HttpKernelInterface::SUB_REQUEST);
});


//--> List posts
$app->get('list', function() use($collection)
{
   Stdout::writel("[head]Found ".$collection->getCount()." page(s):[/head]");
   Stdout::nl();

   $collection->sortAsc("name");
   foreach($collection as $page)
   {
      Stdout::pagenamel($page);
   }

   Stdout::nl();
   Stdout::writel("Flags: [error]-d-[/error]isabled, ".
      "[error]-h-[/error]idden, [error]-p-[/error]rivate category");

   return "";
});


//--> Search for page and edit first
$app->get('writefind/{name}', function($name) use($app, $collection)
{
   $properties = array("hash", "name", "title", "tags");
   $result = $collection->findPagesFirst($properties, $name);

   if($result instanceof \AnySrc\MarkdownBlog\Page)
   {
      // Open editor
      $subRequest = Request::create('/write/'.$result->getHash(), 'GET');
      return $app->handle($subRequest, \Symfony\Component\HttpKernel\HttpKernelInterface::SUB_REQUEST);
   }
   else
   {
      Stdout::errl("Page not found");
   }

   return "";
})
->assert('name', '.*');


//--> Edit post
$app->get('write/{name}', function($name) use($collection)
{
   $page = $collection->getPageAuto($name);

   if($page===null && strpos($name, "::")!==0)
   {
      $choice = Stdout::readprompt("Page not found. Create? (y/n)");
      if($choice=="y")
      {
         $page = $collection->createPage($name);
      }
      else
      {
         Stdout::errl("Aborted.");
         exit(1);
      }
   }
   elseif($page===null)
   {
      Stdout::errl("Page not found.");
      exit(1);
   }

   Stdout::write("Edit ");
   Stdout::pagenamel($page);

   $editor = System::getFirstAvailableCommand(array(System::getEnv("EDITOR"), "nano", "vim", "vi"));
   if($editor===null)
   {
      Stdout::errl("No compatible editor found.");
      Stdout::errl("Please set \$EDITOR environment variable.");
      exit(1);
   }

   Stdout::writel("Using [highlight]".$editor."[/highlight] as editor");
   Stdout::writel("Launch...");
   sleep(1);

   System::ttyenvexec($editor, $page->getAbsoluteFilename());

   Stdout::write("Done editing ");
   Stdout::pagenamel($page);
   return "";
})
->assert('name', '.*');


$app->get('delete/{name}', function($name) use($collection)
{
   $page = $collection->getPageAuto($name);
   if($page instanceof \AnySrc\MarkdownBlog\Page)
   {
      Stdout::writel("Page found.");
      Stdout::pagenamel($page);
      Stdout::nl();

      $choice = Stdout::readprompt("Really delete? (y/n)");
      if($choice=="y")
      {
         $page->delete();
         Stdout::writel("Page deleted.");
      }
      else
      {
         Stdout::writel("Aborted.");
      }
   }
   else
   {
      Stdout::errl("No page found.");
   }

   return "";
})
->assert('name', '.*');


$app->get('folders', function() use($app, $collection)
{
   $folders = new AnySrc\RecursiveRegexGlob($collection->getBaseFolder(), null, "/.*/", false, true);
   $folders->sortAsc();

   Stdout::writel("[head]".$folders->count()." folders found.[/head]");
   Stdout::nl();

   foreach($folders as $folder)
   {
      $mdfolder = substr($folder, strlen($collection->getBaseFolder()));
      Stdout::write("[file]~/".$mdfolder."[/file] ");
      Stdout::write($collection->getFolderPages($mdfolder)->getCount()." pages inside");
      if(is_file($folder.DIRECTORY_SEPARATOR."folder.yml"))
      {
         Stdout::writel(", folder.yml");
      }
      else
      {
         Stdout::nl();
      }
   }
   return "";
});


$app->get('set/{mode}/{file}', function($mode, $file) use($collection)
{
   $page = $collection->getPageAuto($file);
   if(is_null($page))
   {
      Stdout::errl("Page not found.");
      exit(1);
   }

   $change = null;
   if($mode=="enable" || $mode=="disable")
   {
      $page->setIsDisabled(($mode=="disable"));
      $change = "disabled: [highlight]".($mode=="disable" ? "true" : "false")."[/highlight]";
   }
   elseif($mode=="show" || $mode=="hide")
   {
      $page->setIsVisible(($mode=="show"));
      $change = "visible: [highlight]".($mode=="show" ? "true" : "false")."[/highlight]";
   }
   else
   {
      throw new \Exception("Invalid mode");
   }

   Stdout::pagenamel($page);
   Stdout::writel($change);

   $page->write();
   return "";
})
->assert('file', '.*')
->assert('mode', '[^\s]+');


$app->get('upload', function() use ($cfg)
{
   Stdout::writel("Upload URL: [highlight]".$cfg->getPath('hostconfig/cmdhost', '/')."session/upload[/highlight]");
   return "";
});


$app->get('version', function()
{
   Stdout::writel("anysrc mdblog version: [highlight]".AnySrc\MarkdownBlog\Version::getVersion()."[/highlight]");
   return "";
});


//--> Unknown action
$app->error(function(\Exception $e, $code) use ($app)
{
   Stdout::nl();
   if($code==404)
   {
      Stdout::errl("Option not found. ".$e->getMessage());
   }
   else
   {
      Stdout::errl("ERROR: ".$e->getMessage());
   }

   // Core help
   $commands = array(
       array(
           "title" => "Login Session",
           "commands" => array(
               "newtan" => "Generate a new login TAN",
               "cleartokens" => "Invalidate all login sessions and TANs",
               "sessions" => "Display unused TANs and current sessions",
           ),
       ),
       array(
           "title" => "Find",
           "commands" => array(
               "list" => "List all posts",
               "folders" => "List all folders",
               "find <pattern>" => "Find posts by search pattern",
               "latest 10" => "Display newest 10 pages",
           ),
       ),
       array(
           "title" => "Compose",
           "commands" => array(
               "write <::hash / some/file>" => "Create/Edit post by name or hash code",
               "writefind <pattern>" => "Edit the first search result",
               "delete <::hash / some/file>" => "Delete post by name or hash code",
           ),
       ),
       array(
           "title" => "Post Settings",
           "commands" => array(
               "set enable <::hash / some/file>" => "Enable post by name or hash code",
               "set disable <::hash / some/file>" => "Disable post by name or hash code",
               "set show <::hash / some/file>" => "Show post in list by name or hash code",
               "set hide <::hash / some/file>" => "Hide post in list by name or hash code",
           ),
       ),
       array(
           "title" => "Other",
           "commands" => array(
               "upload" => "Display upload url",
               "version" => "Print version code",
           ),
       ),
   );

   // Plugin help
   if(isset($app['pluginmanager']))
   {
      $mgr = $app['pluginmanager'];
      foreach($mgr->getPluginKeys() as $plugin)
      {
         $pi = $mgr->getPluginByName($plugin);
         if($pi instanceof \AnySrc\MarkdownBlog\BackendPluginBase)
         {
            $hitems = $pi->getHelp();
            if(count($hitems)>0)
            {
               $commands[] = array(
                   "title" => $pi->getDisplayName(),
                   "commands" => $hitems,
               );
            }
         }
      }
   }

   // Print help
   foreach($commands as $section)
   {
      Stdout::nl();
      Stdout::writel("[head]".$section['title']."[/head]");
      $maxlength = max(array_map('strlen', array_keys($section['commands'])))+2;
      foreach($section['commands'] as $cmd => $text)
      {
         Stdout::write("[highlight]".str_pad($cmd, $maxlength, " ")."[/highlight] ");
         Stdout::writel($text);
      }
   }

   return "";
});


//--> Load plugins
if($cfg->getPath('pluginsystem/enabled', false)===true)
{
   $plugins = $cfg->getPath('pluginsystem/load', array());
   $pman = new \AnySrc\MarkdownBlog\PluginManager($plugins, 'Backend');
   $pman->register($app);
}


//--> Launch silex with cmd args
$argstr = "/".$args->get_arg_range(0, null, true, "/", false, true);

$app->run(Request::create($argstr));
Stdout::nl();
