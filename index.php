<?php

define("DIR_ABSOLUTEBASE", __DIR__.DIRECTORY_SEPARATOR);
define("DIR_RELATIVEBASE", substr(DIR_ABSOLUTEBASE, strlen(rtrim(preg_replace('/'.preg_quote(DIRECTORY_SEPARATOR, '/').'www$/', "", realpath($_SERVER['DOCUMENT_ROOT'])), DIRECTORY_SEPARATOR)))).DIRECTORY_SEPARATOR;


//--> Initialize Silex
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AnySrc\MarkdownBlog\PageCollection;
use AnySrc\MarkdownBlog\Pager;

require __DIR__."/vendor/autoload.php";
$app = new \AnySrc\MyApplication();


//--> Globals
if(!is_file(__DIR__."/config/global.yml"))
{
   die(__DIR__."/config/global.yml not found");
}

$app['gcfg'] = new \AnySrc\MarkdownBlog\GlobalConfig();
$app['gcfg']->loadFile(__DIR__."/config/global.yml", 'global');

//--> Debugging
if($app['gcfg']->getPath('debug/enabled', false)===true &&
      in_array($_SERVER['REMOTE_ADDR'], $app['gcfg']->getPath('debug/debughosts', array())))
{
   error_reporting(E_ALL);
   ini_set("display_errors", 1);
   $app['debug'] = true;
}


//--> User defined configs
if(is_file(__DIR__."/config/user.yml"))
{
   $app['gcfg']->loadFile(__DIR__."/config/user.yml", 'user');
}


//--> Force HTTPS
if($app['gcfg']->getPath('hostconfig/forcehttps', false)===true &&
      (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS']!="on"))
{
   header("Location: https://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]);
   exit();
}


//--> Trusted hosts
$trustedhosts = $app['gcfg']->getPath('hostconfig/trustedhosts', array());
if($app['gcfg']->getPath('hostconfig/checktrustedhost', false)===true &&
      is_array($trustedhosts) && count($trustedhosts))
{
   if(!in_array($_SERVER['HTTP_HOST'], $trustedhosts))
   {
      die("This is not a trusted domain. Please add the current domain to global.yml.");
   }
}


//--> Silex Providers
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app->register(new Silex\Provider\SessionServiceProvider(), array(
   'session.storage.options' => array(
      'name' => 'ANYSRC_SESSION',
   ),
));

$app->register(new Silex\Provider\TwigServiceProvider(), array(
   'twig.loader' => new \Twig_Loader_Filesystem(),
));

$app['twig.loader']->addPath(__DIR__.'/view/'.$app['gcfg']->getPath('core/template', 'core'), 'core');
$app['twig.loader']->addPath(__DIR__.'/view/'.$app['gcfg']->getPath('site/template'), 'view');
$app['twig.loader']->addPath(__DIR__.'/content', 'content');

$parsedown = new AnySrc\ParsedownExtensions();
$parsedown->setGalleryBase($app, DIR_ABSOLUTEBASE."upload".DIRECTORY_SEPARATOR, DIR_ABSOLUTEBASE);

$twigext = new AnySrc\TwigExtensions();
$twigext->setParsedown($parsedown);
$app['twig']->addExtension($twigext);


//--> Template YAML config
$tplcfgfile = __DIR__.'/view/'.$app['gcfg']->getPath('site/template')."/template.yml";
$app['tplcfg'] = new AnySrc\MarkdownBlog\GlobalConfig(false);
if(is_file($tplcfgfile))
{
   $app['tplcfg']->loadFile($tplcfgfile, 'tpl');
}

/*
if(is_file(__DIR__."/config/user.yml"))
{
   $app['tplcfg']->loadFile(__DIR__."/config/user.yml", 'user');
}
*/


//--> Before middleware
$app->before(function() use($app)
{
   $changed = false;

   // Add initial trusted host
   $trustedhosts = $app['gcfg']->getPath('hostconfig/trustedhosts', array());
   if(count($trustedhosts)<1)
   {
      $app['gcfg']->setPath('global', 'hostconfig/checktrustedhost', true);
      $app['gcfg']->appendArray('global', 'hostconfig/trustedhosts', $_SERVER['HTTP_HOST']);
      $changed=true;
   }

   // Add cmd host if not exist
   if($app['gcfg']->getPath('hostconfig/cmdhost', null)===null)
   {
      $app['gcfg']->setPath('global', 'hostconfig/cmdhost', $app->url('root'));
      $changed=true;
   }

   // Save changes to global.yml
   if($changed===true)
   {
      $app['gcfg']->saveChanges();
   }
});


//--> Error handler
$app->error(function (\Exception $e, $code) use($app)
{
   return $app->render('@core/exception.html.twig', array(
       "exception" => $e,
       "type" => get_class($e),
       "code" => $code,
       "debug" => $app['debug'],
   ));
});


//--> Tan System
$tan = new AnySrc\Security\Tan(__DIR__."/config/tokens.yml");

$app['loginstatus'] = false;
$app['currentsession'] = null;
if($app['session']->has('currenttan'))
{
   $s = $tan->isSessionExists($app['session']->get('currenttan'));
   $app['loginstatus'] = $s;
   if($s===true)
   {
      $tan->extendSessionTimeout($app['session']->get('currenttan'));
      $app['currentsession'] = $tan->getSession($app['session']->get('currenttan'));
   }
   else
   {
      $app['session']->remove('currenttan');
   }
}

//--> Scan blog posts
$collection = new PageCollection($app);
$collection->loadByRegexGlob(new AnySrc\RecursiveRegexGlob(__DIR__."/post", '/.+\.md$/'));
if($app['loginstatus']===false)
{
   $collection = $collection
      ->getPagesByProperty("isdisabled", false)
      ->getPagesByFolderConfigProperty('private', false);
}




/**
 * Router Actions
 */


$app->get('/', function() use($app)
{
   return $app->redirect($app->url('page', array('mode'=>'list')));
})
->bind('root');



$app->get('/{mode}/{folder}', function(Request $request, $mode, $folder) use($app, $collection)
{
   // Hide invisible pages
   $filteredcollection = $collection;
   if($app['loginstatus']!==true)
   {
      $filteredcollection = $filteredcollection->getPagesByProperty("isvisible", true);
   }

   $listcollection = $filteredcollection;

   //--> Build query string
   $query = $request->get('q', '');

   if($mode=="list" && !empty($folder))
   {
      $query.=' folder:"'.$folder.'"';
   }

   if($mode=="tag" && !empty($folder))
   {
      $query.=' tag:"'.$folder.'" ';
   }

   if($app['loginstatus']===true && $mode=="status" && !empty($folder))
   {
      $query.=' state:"'.$folder.'" ';
   }

   $query = trim($query);
   $queryparser = new \AnySrc\SearchPropertyParser($query);
   $ignorehiddeninparent = count($queryparser->getByProperty('folder'))<1 && !empty($query);

   //--> Do search
   $search = new \AnySrc\MarkdownBlog\PageSearch($filteredcollection);
   $filteredcollection = $search->search($queryparser, 2, $ignorehiddeninparent);
   $patternsearch = $search->getContainscontents() || $search->getContainstitles() || $search->getContainspatterns();

   //--> Abort if result is empty
   if($patternsearch===false && !($filteredcollection instanceof PageCollection && $filteredcollection->getCount()>0))
   {
      return $app->abort(404, 'Kategorie nicht gefunden');
   }

   //--> RSS Output
   if($request->get('rss')!==null)
   {
      $filteredcollection->sortDesc('postdate');
      $pubdate = $filteredcollection->getFirstPage()->getPostDate()->format('r');

      return $app->render('@core/rss.xml.twig', array(
          'pubDate' => $pubdate,
          'collection' => $filteredcollection,
      ));

   }
   else
   {

      $foldercfg = $filteredcollection->getFolderConfig($filteredcollection->getBaseFolder().$folder);

      // Sort
      if($patternsearch===true)
      {
         $filteredcollection->sort(array(array('property'=>'searchscore', 'direction'=>'ASC')));
      }
      else
      {
         $filteredcollection->sort($foldercfg['folder']['sort']);
      }

      // Paging
      $itemsperpage = 5;
      if(isset($foldercfg['folder']['itemsperpage']))
      {
         $itemsperpage = $foldercfg['folder']['itemsperpage'];
      }

      $pager = new Pager($itemsperpage);
      $page = $request->get('page', 1);
      $page = $pager->page($filteredcollection, $page);
      $items = $pager->calc($filteredcollection, $page);
      $min = $pager->min();
      $max = $pager->max($filteredcollection);

      // Render template
      return $app->render($foldercfg['folder']['template']['list'], array(
          "allcollection" => $collection,
          "listcollection" => $listcollection,
          "filteredcollection" => $filteredcollection,
          "pages" => $items,
          "prevpage" => ($page>$min ? ($page-1) : null),
          "currentpage" => $page,
          "nextpage" => ($page<$max ? ($page+1) : null),
          "currentfolder" => $folder,
          "currentmode" => $mode,
          "lastpage" => $max,
          "query" => (empty($query) ? null : $query),
          "showquerydocs" => ($request->get('q')!==null && empty($query)),
          "scoresearch" => $patternsearch,
      ));

   }

})
->bind('page')
->value('folder', null)
->assert('folder', '.*')
->assert('mode', '^list|tag|status$')
->after(function (Request $request, Response $response)
{
   if($request->get('rss')!==null) {
      $response->headers->set('Content-Type', 'application/rss+xml');
   }
});



$app->get('/post/{name}', function($name) use($app, $collection)
{
   if($collection->isPageExist($name))
   {
      $p = $collection->getPageByName($name);
      $foldercfg = $collection->getFolderConfig($collection->getBaseFolder().$p->getNameFolder());

      // Render template
      return $app->render($foldercfg['folder']['template']['single'], array(
          "allcollection" => $collection,
          "listcollection" => $collection,
          "page" => $p,
      ));
   }
   return $app->abort(404, "Blog post nicht gefunden.");
})
->assert('name', '.*')
->bind('post');



$app->get('/::{hash}', function($hash) use($app, $collection)
{
   $page = $collection->getPageByHash($hash);
   if(!is_null($page))
   {
      return $app->redirect($app->url('post', array('name'=>$page->getName())));
   }
   return $app->abort(404);
})
->assert('hash', '^[a-f0-9]+$')
->bind('hash');



$app->get('/md/{name}', function($name) use($app, $collection)
{
   if($collection->isPageExist($name))
   {
      $p = $collection->getPageByName($name);
      return new \Symfony\Component\HttpFoundation\Response($p->getRaw(), 200, array("Content-Type"=>"text/plain; charset=UTF-8"));
   }
   return $app->abort(404, "Blog post nicht gefunden.");
})
->assert('name', '.*')
->bind('md');



$app->get('/session/login', function() use($app, $tan)
{
   return $app->render('@core/login.html.twig');
});



$app->post('/session/loginquery', function(Request $request) use($app, $tan)
{
   $tanstr = $request->get('tan', '');
   if($app['loginstatus']!==true)
   {
      $sesstan = $tan->login($tanstr);
      if(!is_null($sesstan))
      {
         $app['session']->set('currenttan', $sesstan);
         return $app->json(array("success"=>true));
      }
   }
   return $app->json(array("success"=>false));
})
->bind('loginquery');



$app->get('/session/logout', function() use($app, $tan)
{
   if($app['loginstatus']===true)
   {
      $tanstr = $app['session']->get('currenttan');
      $tan->logout($tanstr);
      $app['session']->remove('currenttan');
   }
   return $app->redirect($app->url('root'));
})
->bind('logout');



$app->get('/session/upload', function() use($app, $tan)
{
   if($app['gcfg']->getPath('fileupload/enabled', false)===false)
   {
      throw new \Exception("Web based file upload is not enabled.");
   }

   if($app['loginstatus']!==true)
   {
      throw new \Exception("Please log in to use the web based upload.");
   }

   return $app->render('@core/upload.html.twig');
})
->bind('upload');



$app->post('/session/upload', function(Request $req) use($app)
{
   if($app['gcfg']->getPath('fileupload/enabled', false)===false || $app['loginstatus']!==true)
   {
      return $app->error(403);
   }

   $maxsize = $app['gcfg']->getPath('fileupload/maxfilemb', 0)*1024*1024;

   $ufiles = $req->files->get('files');
   $files = array();

   foreach($ufiles as $file)
   {  /* @var $file Symfony\Component\HttpFoundation\File\UploadedFile */
      if($file->isValid())
      {
         $size = $file->getSize();
         $orgname = $file->getClientOriginalName();
         $name = date("Ymd-His")."-".preg_replace('/[^0-9A-Za-z_\-\.ßüäöÜÄÖ€,]/', "-", $orgname);

         if($size>$maxsize || preg_match($app['gcfg']->getPath('fileupload/denyfilemask', '/.*/'), $name)===1)
         {
            unlink($file->getFileInfo()->getRealPath());
            $files[] = array(
                "name" => $name,
                "size" => $size,
                "error" => ($size>$maxsize ? "File is too big" : "File is not allowed"),
            );
         }
         else
         {
            $file->move(DIR_ABSOLUTEBASE.'upload/', $name);
            $files[] = array(
                "name" => $name,
                "size" => $size,
            );
         }
      }
   }

   return $app->json(array("files"=>$files));
})
->bind('doupload');



$app->get('/json/info', function() use($app)
{
   $result = array(
      "loginstatus" => $app['loginstatus'],
      "realtimeeditor" => $app['gcfg']->getPath('realtimeeditor', null),
   );
   return $app->json(array("success"=>true,"result"=>$result));
})
->bind('json-info');



$app->get('/json/ismodified', function(Request $request) use($app, $collection)
{
   $name = $request->get('name');
   $time = ((int)$request->get('time'));
   $result = false;
   if($collection->isPageExist($name))
   {
      $p = $collection->getPageByName($name);
      $ptime = ((int)$p->getFileModificationTime()->format('U'));
      if($ptime>$time)
      {
         $result = true;
      }
   }
   return $app->json(array("success"=>true, "result"=>$result));
})
->bind('json-ismodified');



$app->run();
