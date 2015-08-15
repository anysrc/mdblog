
$(document).ready(function()
{

   var change = function(cls)
   {
      $('.status').addClass('hidden');
      $('.status-'+cls).removeClass('hidden');
   }

   var parsehash = function()
   {
      change('start');
      var hash = location.hash;
      if(hash.length>1)
      {
         change('loading');
         var tan = hash.substring(1);

         $.ajax({
            method:'POST',
            url:Config.loginurl,
            data: { tan:tan },
            complete: function(response)
            {
               if(response.responseJSON.success)
               {
                  change('success');
                  window.setTimeout(function()
                  {
                     location.href = Config.rooturl;
                  }, 500);
               }
               else
               {
                  change('failure');
               }
            }
         });

      }
      else
      {
         change('notfound');
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

