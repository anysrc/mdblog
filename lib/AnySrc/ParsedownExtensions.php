<?php

namespace AnySrc;

/**
 * Markdown parser extensions
 */
class ParsedownExtensions extends \PerryFlynn\ParsedownExtraExtensions
{

   const NOTFOUNDIMG = "data:image/png;base64,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";

   private $app = null;
   private $browserbase = null;
   private $gallerybase = null;
   private $thumbbase = null;

   public function __construct()
   {
      parent::__construct();
      $this->BlockTypes['/'] = array('Gallery');
      $this->gallerybase = __DIR__;
      $this->browserbase = '/';
   }

   public function setGalleryBase(MyApplication $app, $gallerydir, $browserbase)
   {
      $this->app = $app;
      $this->gallerybase = $gallerydir;
      $this->thumbbase = $this->gallerybase.DIRECTORY_SEPARATOR."_thumbnails".DIRECTORY_SEPARATOR;
      $this->browserbase = $browserbase;
   }

   protected function blockTable($Line, array $Block = null)
   {
      $result = parent::blockTable($Line, $Block);
      if(is_array($result))
      {
         $result['element']['attributes']['class'] = "table table-bordered table-hover";
      }
      return $result;
   }

   #
   # Fenced Code

   protected function blockGallery($Line)
   {
      if (preg_match('/^(['.preg_quote($Line['text'][0], '/').']{3,})[ ]*$/', $Line['text'], $matches))
      {
         $Block = array(
            'files' => array(),
            'char' => $Line['text'][0],
            'element' => array(
               'name' => 'div',
               'text' => "",
            ),
         );

         return $Block;
      }
   }

   protected function blockGalleryContinue($Line, $Block)
   {
      if (isset($Block['complete']))
      {
         return;
      }

      if (isset($Block['interrupted']))
      {
         $Block['element']['text'] .= "\n";
         unset($Block['interrupted']);
      }

      if (preg_match('/^'.preg_quote($Block['char'], '/').'{3,}[ ]*$/', $Line['text']))
      {
         $Block['complete'] = true;
         return $Block;
      }

      if(strpos($Line['body'], "#")!==0)
      {
         $files = array($this->gallerybase.DIRECTORY_SEPARATOR.$Line['body']);
         if(!is_file($files[0]))
         {
            $files = glob($this->gallerybase.DIRECTORY_SEPARATOR.$Line['body']);
         }

         $doclength = strlen($this->browserbase);

         if(!is_dir($this->thumbbase))
         {
            mkdir($this->thumbbase, 0777, true);
         }

         $width = $this->app['gcfg']->getPath('parsedowngallery/thumbnailwidth', 200);
         $height = $this->app['gcfg']->getPath('parsedowngallery/thumbnailheight', 200);

         if(is_array($files) && count($files)>0)
         {
            foreach($files as $file)
            {
               if(is_file($file) && preg_match('/\.(gif|jpg|png)$/', $file)===1)
               {
                  $thumbfile = $this->thumbbase.sha1_file($file).".png";
                  if(!is_file($thumbfile))
                  {
                     $img = new \abeautifulsite\SimpleImage($file);
                     $img->thumbnail($width, $height)->save($thumbfile);
                  }
                  $relfile = "/".substr(realpath($file), $doclength);
                  $relthumbfile = "/".substr(realpath($thumbfile), $doclength);

                  $Block['files'][] = array(
                     "original"=>$relfile,
                     "thumbnail"=>$relthumbfile,
                     "basename" => basename($file),
                  );
               }
            }
         }
      }

      return $Block;
   }

   protected function blockGalleryComplete($Block)
   {
      $html = $this->app['twig']->render('@core/parsedowngallery.html.twig', array(
         "files" => $Block['files']
      ));

      $Block['element']['text'] .= $html;
      return $Block;
   }

    protected function inlineImage($Excerpt)
    {
       $doclength = strlen($this->browserbase);
       $data = parent::inlineImage($Excerpt);

       if(is_array($data))
       {
          $src = $data['element']['attributes']['src'];
          if(strpos($src, "data:")!==0 && strpos($src, "http://")!==0 && strpos($src, "https://")!==0)
          {
             $file = $this->gallerybase.DIRECTORY_SEPARATOR.$src;
             if(is_file($file))
             {
                $relfile = "/".substr(realpath($file), $doclength);
                $data['element']['attributes']['src'] = $relfile;
             }
             elseif(strpos($src, "/")!==0)
             {
                $data['element']['attributes']['src'] = self::NOTFOUNDIMG;
             }
          }
       }

       return $data;
    }

}
