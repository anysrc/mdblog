
$(document).ready(function()
{

   var parsehash = function()
   {
      var hash = location.hash;
      if(hash.length>1)
      {
         var tan = hash.substring(1);
         console.log(tan);

         $.ajax({
            method:'POST',
            url:Config.loginurl,
            data: { tan:tan },
            complete: function(response)
            {
               if(response.responseJSON.success)
               {
                  $('.result').html('<p>Login erfolgreich!</p>');
                  window.setTimeout(function()
                  {
                     location.href = Config.rooturl;
                  }, 500);
               }
               else
               {
                  $('.result').html('<p>Login fehlgeschlagen!</p>');
               }
            }
         });

      }
      else
      {
         $('.result').html('<p>Keine TAN gefunden!</p>');
      }
   }

   if("onhashchange" in window)
   {
      $(window).bind('hashchange', function()
      {
         parsehash();
      });
   }

   parsehash();

});

